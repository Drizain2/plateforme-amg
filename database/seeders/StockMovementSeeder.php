<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $admin = $shop->users()->role('admin')->first();

            StockDepot::all()->each(function (StockDepot $stock) use ($shop, $admin) {
                // 2-4 entrées initiales (réapprovisionnement)
                $restockCount = fake()->numberBetween(2, 4);
                for ($i = 0; $i < $restockCount; $i++) {
                    StockMovement::create([
                        'shop_id' => $shop->id,
                        'depot_id' => $stock->depot_id,
                        'stock_id' => $stock->id,
                        'user_id' => $admin?->id,
                        'type' => 'in',
                        'quantity' => fake()->numberBetween(5, 30),
                        'note' => 'Réapprovisionnement initial',
                    ]);
                }

                // 0-3 sorties (consommation)
                $outCount = fake()->numberBetween(0, 3);
                for ($i = 0; $i < $outCount; $i++) {
                    StockMovement::create([
                        'shop_id' => $shop->id,
                        'depot_id' => $stock->depot_id,
                        'stock_id' => $stock->id,
                        'user_id' => $admin?->id,
                        'type' => 'out',
                        'quantity' => fake()->numberBetween(1, 5),
                        'note' => null,
                    ]);
                }

                // 0-1 ajustement d'inventaire
                if (fake()->boolean(20)) {
                    StockMovement::create([
                        'shop_id' => $shop->id,
                        'depot_id' => $stock->depot_id,
                        'stock_id' => $stock->id,
                        'user_id' => $admin?->id,
                        'type' => 'adjustment',
                        'quantity' => fake()->numberBetween(1, 10),
                        'note' => 'Correction inventaire',
                    ]);
                }
            });
        });
    }
}
