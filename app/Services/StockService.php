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
    /**
     * Réceptionne du stock et recalcule le coût moyen pondéré (CMP) du dépôt :
     * nouveauCMP = (qté actuelle × CMP actuel + qté reçue × coût unitaire) / qté totale.
     */
    public function restock(StockDepot $stock, int $quantity, User $by, string $note = 'réapprovisionnement', ?int $invoiceId = null, ?float $unitCost = null, ?int $purchaseId = null): void
    {
        DB::transaction(function () use ($stock, $quantity, $by, $note, $invoiceId, $unitCost, $purchaseId) {
            $locked = $this->lockStock($stock);

            $cost = $unitCost ?? (float) $locked->avg_cost_price;
            $newQuantity = $locked->quantity + $quantity;
            $newAvgCost = $newQuantity > 0
                ? (($locked->quantity * (float) $locked->avg_cost_price) + ($quantity * $cost)) / $newQuantity
                : 0;

            $locked->update([
                'quantity' => $newQuantity,
                'avg_cost_price' => $newAvgCost,
            ]);

            // Le prix d'achat du catalogue n'est mis à jour qu'à la réception
            // d'un achat réel (purchase_id présent), pas lors d'un réassort
            // suite à l'annulation d'une facture.
            if ($unitCost !== null && $purchaseId !== null) {
                $locked->part->update(['unit_price' => $unitCost]);
            }

            StockMovement::create([
                'depot_id' => $locked->depot_id,
                'stock_id' => $locked->id,
                'user_id' => $by->id,
                'invoice_id' => $invoiceId,
                'purchase_id' => $purchaseId,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $cost,
                'note' => $note,
            ]);
        });
    }

    public function consume(StockDepot $stock, int $quantity, ?int $ticketId, User $by, ?int $invoiceId = null, ?int $invoiceLineId = null): void
    {
        DB::transaction(function () use ($stock, $quantity, $ticketId, $by, $invoiceId, $invoiceLineId) {
            $locked = $this->lockStock($stock);

            if ($locked->quantity < $quantity) {
                throw new InsufficientStockException("Stock insuffisant (disponible : {$locked->quantity}, demandé : {$quantity}).");
            }

            $cost = $locked->avg_cost_price;

            $locked->decrement('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $locked->depot_id,
                'stock_id' => $locked->id,
                'user_id' => $by->id,
                'ticket_id' => $ticketId,
                'invoice_id' => $invoiceId,
                'invoice_line_id' => $invoiceLineId,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_cost' => $cost,
            ]);

            if ($locked->fresh()->is_critical) {
                $admins = User::where('shop_id', $locked->shop_id)
                    ->role(['admin', 'super_admin'])
                    ->get();

                Notification::send($admins, new LowStockAlert($locked->load('part', 'depot')));
            }
        });
    }

    public function transfer(StockDepot $source, Depot $targetDepot, int $quantity, User $by): void
    {
        if ($source->depot_id === $targetDepot->id) {
            throw new \InvalidArgumentException('Le dépôt source et destination sont identiques.');
        }

        DB::transaction(function () use ($source, $targetDepot, $quantity, $by) {
            $lockedSource = $this->lockStock($source);

            if ($lockedSource->quantity < $quantity) {
                throw new InsufficientStockException('Stock insuffisant pour le transfert.');
            }

            $transferCost = (float) $lockedSource->avg_cost_price;

            $lockedSource->decrement('quantity', $quantity);

            StockMovement::create([
                'depot_id' => $lockedSource->depot_id,
                'stock_id' => $lockedSource->id,
                'user_id' => $by->id,
                'type' => 'transfer_out',
                'quantity' => $quantity,
                'unit_cost' => $transferCost,
                'transfer_depot_id' => $targetDepot->id,
                'note' => "Transfert vers {$targetDepot->name}",
            ]);

            $destination = StockDepot::withoutGlobalScopes()->firstOrCreate(
                ['part_id' => $lockedSource->part_id, 'depot_id' => $targetDepot->id],
                ['alert_quantity' => $lockedSource->alert_quantity, 'avg_cost_price' => 0]
            );
            $lockedDestination = $this->lockStock($destination);

            // Le coût transféré s'intègre au CMP du dépôt destinataire, comme
            // un réassort classique.
            $newQuantity = $lockedDestination->quantity + $quantity;
            $newAvgCost = $newQuantity > 0
                ? (($lockedDestination->quantity * (float) $lockedDestination->avg_cost_price) + ($quantity * $transferCost)) / $newQuantity
                : 0;

            $lockedDestination->update([
                'quantity' => $newQuantity,
                'avg_cost_price' => $newAvgCost,
            ]);

            StockMovement::create([
                'depot_id' => $targetDepot->id,
                'stock_id' => $lockedDestination->id,
                'user_id' => $by->id,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'unit_cost' => $transferCost,
                'transfer_depot_id' => $lockedSource->depot_id,
                'note' => "Transfert depuis {$lockedSource->depot->name}",
            ]);
        });
    }

    public function adjustment(StockDepot $stock, int $newQuantity, string $note, User $by): void
    {
        DB::transaction(function () use ($stock, $newQuantity, $note, $by) {
            $locked = $this->lockStock($stock);
            $diff = $newQuantity - $locked->quantity;

            $locked->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'depot_id' => $locked->depot_id,
                'stock_id' => $locked->id,
                'user_id' => $by->id,
                'type' => 'adjustment',
                'quantity' => abs($diff),
                'unit_cost' => $locked->avg_cost_price,
                'note' => $note,
            ]);
        });
    }

    /**
     * Recharge la ligne de stock avec un verrou de ligne (SELECT ... FOR UPDATE)
     * pour empêcher deux opérations concurrentes de lire/écrire la même
     * quantité ou le même CMP sans se voir l'une l'autre.
     */
    private function lockStock(StockDepot $stock): StockDepot
    {
        return StockDepot::withoutGlobalScopes()->lockForUpdate()->findOrFail($stock->id);
    }
}
