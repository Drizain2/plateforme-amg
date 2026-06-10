<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Depot;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function restock(StockDepot $stock, int $quantity, string $note = 'réapprovisionnement'): void
    {
        DB::transaction(function () use ($stock, $quantity, $note) {
            $stock->increment('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $stock->depot_id,
                'stock_id' => $stock->id,
                'type' => 'in',
                'quantity' => $quantity,
                'note' => $note,
            ]);
        });
    }

    public function consume(StockDepot $stock, int $quantity, ?int $ticketId, User $by): void
    {
        if ($stock->quantity < $quantity) {
            throw new InsufficientStockException($stock);
        }

        DB::transaction(function () use ($stock, $quantity, $ticketId, $by) {
            $stock->decrement('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $stock->depot_id,
                'stock_id' => $stock->id,
                'user_id' => $by->id,
                'ticket_id' => $ticketId,
                'type' => 'out',
                'quantity' => $quantity,
            ]);

            // Vérifier seuil après transaction
            if ($stock->fresh()->is_critical) {
                $admin = User::where('shop_id', $stock->shop_id)
                    ->role('admin')
                    ->first();

                $admin?->notify(new LowStockAlert($stock->load('depot')));
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
