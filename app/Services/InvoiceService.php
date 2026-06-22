<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /** @var array<int, string> */
    private array $marginWarnings = [];

    public function __construct(
        private StockService $stockService,
        private PermissionService $permissionService,
    ) {}

    /**
     * Récupère puis vide les alertes de marge négative accumulées par le
     * dernier appel à create()/addLine() (vente d'une pièce sous son CMP).
     *
     * @return array<int, string>
     */
    public function pullMarginWarnings(): array
    {
        $warnings = $this->marginWarnings;
        $this->marginWarnings = [];

        return $warnings;
    }

    private function checkMargin(string $label, float $unitPrice, StockDepot $stock): void
    {
        $cost = (float) $stock->avg_cost_price;

        if ($unitPrice < $cost) {
            $this->marginWarnings[] = sprintf(
                '"%s" vendue à %.2f alors que son coût moyen est de %.2f (perte de %.2f/unité).',
                $label,
                $unitPrice,
                $cost,
                $cost - $unitPrice,
            );
        }
    }

    public function createFromTicket(Ticket $ticket): Invoice
    {
        return DB::transaction(function () use ($ticket) {
            $invoice = Invoice::create([
                'ticket_id' => $ticket->id,
                'customer_id' => $ticket->customer_id,
                'status' => InvoiceStatus::Draft,
                'tax_rate' => 20.00,
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
            ]);

            // Ligne main d'oeuvre si diagnostic
            if ($ticket->estimated_price) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'type' => 'service',
                    'label' => 'Main d\'œuvre — '.$ticket->device->full_name,
                    'quantity' => 1,
                    'unit_price' => $ticket->estimated_price,
                ]);
            }

            // Lignes pièces consommées
            foreach ($ticket->parts as $tp) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'type' => 'part',
                    'label' => $tp->part->name,
                    'quantity' => $tp->quantity,
                    'unit_price' => $tp->unit_price,
                ]);
            }

            return $invoice->fresh(['lines']);
        });
    }

    /**
     * Crée une facture, en consommant le stock du dépôt courant pour
     * chaque ligne liée à une pièce (lines.*.part_id).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $by): Invoice
    {
        $customer = $this->resolveCustomer($data, $by);

        return DB::transaction(function () use ($data, $by, $customer) {
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'ticket_id' => $data['ticket_id'] ?? null,
                'status' => InvoiceStatus::Draft,
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
                'issued_at' => now(),
                'due_at' => $data['due_at'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $invoiceLine = InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'part_id' => $line['part_id'] ?? null,
                    'type' => $line['type'],
                    'label' => $line['label'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);

                if (! empty($line['part_id'])) {
                    $stock = StockDepot::where('part_id', $line['part_id'])->first();

                    if (! $stock) {
                        throw new InsufficientStockException('Aucun stock pour cette pièce dans ce dépôt.');
                    }

                    $this->stockService->consume($stock, $line['quantity'], null, $by, $invoice->id, $invoiceLine->id);
                    $this->checkMargin($line['label'], (float) $line['unit_price'], $stock);
                }
            }

            return $invoice->fresh(['lines']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(array $data, User $by): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        abort_unless($this->permissionService->has($by, 'customers.create'), 403, 'Permission requise : customers.create');

        return Customer::create([
            'name' => $data['customer_name'],
            'email' => $data['customer_email'] ?? null,
            'phone' => $data['customer_phone'] ?? null,
        ]);
    }

    public function transition(Invoice $invoice, InvoiceStatus $newStatus, User $by): void
    {
        if (! $invoice->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transition impossible : {$invoice->status->value} → {$newStatus->value}"
            );
        }

        DB::transaction(function () use ($invoice, $newStatus, $by) {
            $invoice->update([
                'status' => $newStatus,
                'paid_at' => $newStatus === InvoiceStatus::Paid ? now() : null,
            ]);

            if ($newStatus === InvoiceStatus::Cancelled) {
                $this->restockCancelledInvoice($invoice, $by);
            }
        });
    }

    /**
     * Remet en stock les pièces vendues par une facture annulée, à partir
     * des mouvements de sortie qu'elle a générés à sa création.
     */
    private function restockCancelledInvoice(Invoice $invoice, User $by): void
    {
        StockMovement::where('invoice_id', $invoice->id)
            ->where('type', 'out')
            ->get()
            ->each(function (StockMovement $movement) use ($invoice, $by) {
                $stock = StockDepot::withoutGlobalScopes()->find($movement->stock_id);

                if ($stock) {
                    $this->stockService->restock(
                        $stock,
                        $movement->quantity,
                        $by,
                        "Annulation facture {$invoice->number}",
                        $invoice->id,
                        $movement->unit_cost !== null ? (float) $movement->unit_cost : null,
                    );
                }
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addLine(Invoice $invoice, array $data, User $by): InvoiceLine
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new \InvalidArgumentException('Impossible de modifier une facture non brouillon.');
        }

        return DB::transaction(function () use ($invoice, $data, $by) {
            $line = InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'part_id' => $data['part_id'] ?? null,
                'type' => $data['type'],
                'label' => $data['label'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
            ]);

            if (! empty($data['part_id'])) {
                $stock = StockDepot::where('part_id', $data['part_id'])->first();

                if (! $stock) {
                    throw new InsufficientStockException('Aucun stock pour cette pièce dans ce dépôt.');
                }

                $this->stockService->consume($stock, $data['quantity'], null, $by, $invoice->id, $line->id);
                $this->checkMargin($data['label'], (float) $data['unit_price'], $stock);
            }

            return $line;
        });
    }

    /**
     * Supprime une ligne de facture brouillon en remettant en stock la
     * quantité éventuellement consommée pour cette ligne, au coût exact
     * appliqué lors de la consommation.
     */
    public function removeLine(InvoiceLine $line, User $by): void
    {
        if ($line->invoice->status !== InvoiceStatus::Draft) {
            throw new \InvalidArgumentException('Impossible de modifier une facture non brouillon.');
        }

        DB::transaction(function () use ($line, $by) {
            $movement = StockMovement::where('invoice_line_id', $line->id)
                ->where('type', 'out')
                ->first();

            if ($movement) {
                $stock = StockDepot::withoutGlobalScopes()->find($movement->stock_id);

                if ($stock) {
                    $this->stockService->restock(
                        $stock,
                        $movement->quantity,
                        $by,
                        "Suppression ligne facture {$line->invoice->number}",
                        null,
                        $movement->unit_cost !== null ? (float) $movement->unit_cost : null,
                    );
                }
            }

            $line->delete();
        });
    }
}
