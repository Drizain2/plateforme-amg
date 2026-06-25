<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\StockDepot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private StockService $stockService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'status' => PurchaseStatus::Draft,
                'tax_rate' => $data['tax_rate'],
                'notes' => $data['notes'] ?? null,
                'ordered_at' => now(),
            ]);

            foreach ($data['lines'] as $line) {
                PurchaseLine::create([
                    'purchase_id' => $purchase->id,
                    'part_id' => $line['part_id'],
                    'label' => $line['label'],
                    'quantity' => $line['quantity'],
                    'alert_quantity' => $line['alert_quantity'] ?? null,
                    'unit_price' => $line['unit_price'],
                ]);
            }

            return $purchase->fresh(['lines']);
        });
    }

    public function transition(Purchase $purchase, PurchaseStatus $newStatus, User $by): void
    {
        if (! $purchase->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transition impossible : {$purchase->status->value} → {$newStatus->value}"
            );
        }

        DB::transaction(function () use ($purchase, $newStatus, $by) {
            $purchase->update([
                'status' => $newStatus,
                'received_at' => $newStatus === PurchaseStatus::Received ? now() : $purchase->received_at,
                'paid_at' => $newStatus === PurchaseStatus::Paid ? now() : null,
            ]);

            if ($newStatus === PurchaseStatus::Received) {
                $this->receiveStock($purchase, $by);
            }
        });
    }

    private function receiveStock(Purchase $purchase, User $by): void
    {
        foreach ($purchase->lines as $line) {
            $stock = StockDepot::withoutGlobalScopes()->firstOrCreate(
                ['part_id' => $line->part_id, 'depot_id' => $purchase->depot_id],
                ['quantity' => 0, 'alert_quantity' => $line->alert_quantity ?? 0]
            );

            if ($line->alert_quantity !== null && ! $stock->wasRecentlyCreated) {
                $stock->update(['alert_quantity' => $line->alert_quantity]);
            }

            $this->stockService->restock(
                $stock,
                $line->quantity,
                $by,
                "Réception achat {$purchase->number}",
                null,
                (float) $line->unit_price,
                $purchase->id,
            );
        }
    }
}
