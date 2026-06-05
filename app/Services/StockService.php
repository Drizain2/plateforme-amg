<?php
namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Depot;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function consume(Part $part, int $quantity, ?int $ticketId=null, User $by): void {
        if($part->quantity < $quantity) throw new InsufficientStockException("");// a completer

        DB::transaction(function () use($part,$quantity,$ticketId,$by) {
            $part->decrement('quantity',$quantity);

            StockMovement::create([
                'part_id' => $part->id,
                'quantity' => -$quantity,
                'type' => 'out',
                'reason' => 'vente',
                'shop_id' => $part->shop_id,
                'reference_ticket_id' => $ticketId ,
                'user_id'=> $by->id,
            ]);
            
            if($part->fresh()->is_critical)
            {
                // Notifier l'administrateur de stock
                $part->shop->notify(new LowStockNotification($part));
            }
        });
        
    }

    public function restock(Part $part, int $quantity, string $note="livraison"): void {
        DB::transaction(function () use($part,$quantity,$note) {
            $part->increment('quantity', $quantity);

            StockMovement::create([
                'part_id' => $part->id,
                'quantity' => $quantity,
                'type' => 'in',
                'reason' => 'reapprovisionnement',
                'note' => $note,
                'shop_id' => $part->shop_id,
            ]);

        });
        
    }

    public function transfer(Part $sourcePart,Depot $targetDepot, int $quantity,User $by): void
    {
        if ($sourcePart->quantity < $quantity) {
            throw new InsufficientStockException("Stock insuffisant");
        }

        DB::transaction(function () use ($sourcePart, $targetDepot, $quantity, $by) {
            // Retrait de la source
            $sourcePart->decrement('quantity', $quantity);

            StockMovement::create([
                'part_id' => $sourcePart->id,
                'quantity' => -$quantity,
                'type' => 'out',
                'reason' => 'transfert',
                'note' => "Transfert vers {$targetDepot->name}",
                'shop_id' => $sourcePart->shop_id,
                'user_id' => $by->id,
                'depot_id' => $sourcePart->depot_id,
            ]);

            // Ajout vers la destination
            $targetPart = $sourcePart->replicate();
            $targetPart->depot_id = $targetDepot->id;
            $targetPart->save();

            StockMovement::create([
                'part_id' => $targetPart->id,
                'quantity' => $quantity,
                'type' => 'in',
                'reason' => 'transfert',
                'note' => "Transfert depuis {$sourcePart->depot->name}",
                'shop_id' => $targetPart->shop_id,
                'user_id' => $by->id,
                'depot_id' => $targetPart->depot_id,
            ]);
        });
    }

    public function adjustment(Part $part, int $newQuantity, string $note, User $by): void
    {
        $diff = $newQuantity - $part->quantity;
        
        DB::transaction(function () use ($part, $newQuantity,$diff, $note, $by) {
            $part->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'part_id' => $part->id,
                'quantity' => abs($diff), // différence positive ou négative
                'type' => 'adjustment',
                'reason' => 'ajustement', // ou 'inventaire'
                'note' => $note,
                'shop_id' => $part->shop_id,
                'user_id' => $by->id,
            ]);
        });
    }
}