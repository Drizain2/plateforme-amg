<?php

namespace App\Services;

use App\Enums\StockCountStatus;
use App\Models\Depot;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockDepot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockCountService
{
    public function __construct(private StockService $stockService) {}

    /**
     * Démarre un inventaire pour le dépôt donné : prend un instantané de la
     * quantité et du CMP courants de chaque ligne de stock du dépôt.
     */
    public function start(Depot $depot, User $by, ?string $note = null): StockCount
    {
        return DB::transaction(function () use ($depot, $by, $note) {
            $stockCount = StockCount::create([
                'depot_id' => $depot->id,
                'user_id' => $by->id,
                'status' => StockCountStatus::Draft,
                'note' => $note,
                'started_at' => now(),
            ]);

            $stocks = StockDepot::where('depot_id', $depot->id)->get();

            foreach ($stocks as $stock) {
                StockCountLine::create([
                    'stock_count_id' => $stockCount->id,
                    'stock_depot_id' => $stock->id,
                    'expected_quantity' => $stock->quantity,
                    'unit_cost' => $stock->avg_cost_price,
                ]);
            }

            return $stockCount->fresh(['lines']);
        });
    }

    /**
     * Enregistre les quantités comptées (sans toucher au stock). Peut être
     * appelé plusieurs fois tant que l'inventaire est en brouillon.
     *
     * @param  array<int, array{id: int, counted_quantity: int|null, note?: string|null}>  $lines
     */
    public function saveCounts(StockCount $stockCount, array $lines): void
    {
        if ($stockCount->status !== StockCountStatus::Draft) {
            throw new \InvalidArgumentException('Cet inventaire est déjà validé.');
        }

        DB::transaction(function () use ($stockCount, $lines) {
            foreach ($lines as $data) {
                $line = $stockCount->lines()->whereKey($data['id'])->first();

                if (! $line) {
                    continue;
                }

                $line->update([
                    'counted_quantity' => $data['counted_quantity'],
                    'note' => $data['note'] ?? null,
                ]);
            }
        });
    }

    /**
     * Valide l'inventaire : applique un ajustement de stock pour chaque
     * ligne comptée dont la quantité diffère de la quantité attendue. Les
     * lignes non comptées sont ignorées (aucune hypothèse n'est faite).
     */
    public function validate(StockCount $stockCount, User $by): void
    {
        if ($stockCount->status !== StockCountStatus::Draft) {
            throw new \InvalidArgumentException('Cet inventaire est déjà validé.');
        }

        DB::transaction(function () use ($stockCount, $by) {
            foreach ($stockCount->lines as $line) {
                if ($line->counted_quantity === null || $line->counted_quantity === $line->expected_quantity) {
                    continue;
                }

                $stock = StockDepot::withoutGlobalScopes()->find($line->stock_depot_id);

                if ($stock) {
                    $this->stockService->adjustment($stock, $line->counted_quantity, "Inventaire {$stockCount->number}", $by);
                }
            }

            $stockCount->update([
                'status' => StockCountStatus::Validated,
                'validated_at' => now(),
            ]);
        });
    }
}
