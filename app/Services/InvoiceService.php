<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
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

    public function transition(Invoice $invoice, InvoiceStatus $newStatus): void
    {
        if (! $invoice->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transition impossible : {$invoice->status->value} → {$newStatus->value}"
            );
        }

        $invoice->update([
            'status' => $newStatus,
            'paid_at' => $newStatus === InvoiceStatus::Paid ? now() : null,
        ]);
    }

    public function addLine(Invoice $invoice, array $data): InvoiceLine
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new \InvalidArgumentException('Impossible de modifier une facture non brouillon.');
        }

        return InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'type' => $data['type'],
            'label' => $data['label'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
        ]);
    }

    public function removeLine(InvoiceLine $line): void
    {
        if ($line->invoice->status !== InvoiceStatus::Draft) {
            throw new \InvalidArgumentException('Impossible de modifier une facture non brouillon.');
        }

        $line->delete();
    }
}
