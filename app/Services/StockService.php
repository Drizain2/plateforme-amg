<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Depot;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StockService
{
    public function restock(StockDepot $stock, int $quantity, User $by, string $note = 'réapprovisionnement', ?int $invoiceId = null, ?float $newUnitPrice = null, ?int $purchaseId = null): void
    {
        DB::transaction(function () use ($stock, $quantity, $by, $note, $invoiceId, $newUnitPrice, $purchaseId) {
            $stock->increment('quantity', $quantity);

            if ($newUnitPrice !== null) {
                $stock->part->update(['unit_price' => $newUnitPrice]);
            }

            StockMovement::create([
                'depot_id' => $stock->depot_id,
                'stock_id' => $stock->id,
                'user_id' => $by->id,
                'invoice_id' => $invoiceId,
                'purchase_id' => $purchaseId,
                'type' => 'in',
                'quantity' => $quantity,
                'note' => $note,
            ]);
        });
    }

    public function consume(StockDepot $stock, int $quantity, ?int $ticketId, User $by, ?int $invoiceId = null): void
    {
        if ($stock->quantity < $quantity) {
            throw new InsufficientStockException("Stock insuffisant (disponible : {$stock->quantity}, demandé : {$quantity}).");
        }

        DB::transaction(function () use ($stock, $quantity, $ticketId, $by, $invoiceId) {
            $stock->decrement('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $stock->depot_id,
                'stock_id' => $stock->id,
                'user_id' => $by->id,
                'ticket_id' => $ticketId,
                'invoice_id' => $invoiceId,
                'type' => 'out',
                'quantity' => $quantity,
            ]);

            // Vérifier seuil après transaction
            if ($stock->fresh()->is_critical) {
                $admins = User::where('shop_id', $stock->shop_id)
                    ->role(['admin', 'super_admin'])
                    ->get();

                Notification::send($admins, new LowStockAlert($stock->load('part', 'depot')));
            }
        });
    }

    public function transfer(StockDepot $source, Depot $targetDepot, int $quantity, User $by): void
    {
        if ($source->quantity < $quantity) {
            throw new InsufficientStockException('Stock insuffisant pour le transfert.');
        }

        if ($source->depot_id === $targetDepot->id) {
            throw new \InvalidArgumentException('Le dépôt source et destination sont identiques.');
        }

        DB::transaction(function () use ($source, $targetDepot, $quantity, $by) {
            $source->decrement('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $source->depot_id,
                'stock_id' => $source->id,
                'user_id' => $by->id,
                'type' => 'transfer_out',
                'quantity' => $quantity,
                'transfer_depot_id' => $targetDepot->id,
                'note' => "Transfert vers {$targetDepot->name}",
            ]);

            $destination = StockDepot::firstOrCreate(
                ['part_id' => $source->part_id, 'depot_id' => $targetDepot->id],
                ['alert_quantity' => $source->alert_quantity]
            );
            $destination->increment('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $targetDepot->id,
                'stock_id' => $destination->id,
                'user_id' => $by->id,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'transfer_depot_id' => $source->depot_id,
                'note' => "Transfert depuis {$source->depot->name}",
            ]);
        });
    }

    public function adjustment(StockDepot $stock, int $newQuantity, string $note, User $by): void
    {
        $diff = $newQuantity - $stock->quantity;

        DB::transaction(function () use ($stock, $newQuantity, $diff, $note, $by) {
            $stock->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'depot_id' => $stock->depot_id,
                'stock_id' => $stock->id,
                'user_id' => $by->id,
                'type' => 'adjustment',
                'quantity' => abs($diff),
                'note' => $note,
            ]);
        });
    }
}
