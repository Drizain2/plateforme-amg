<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockDepot;
use Illuminate\Database\Seeder;

class StockDepotSeeder extends Seeder
{
    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $parts = Part::all();
            $depots = Depot::all();

            foreach ($parts as $part) {
                foreach ($depots as $depot) {
                    // Chaque pièce est présente dans ~70% des dépôts
                    if (! fake()->boolean(70)) {
                        continue;
                    }

                    $alertQty = fake()->numberBetween(3, 8);
                    $isCritique = fake()->boolean(15); // 15% de stock critique

                    StockDepot::create([
                        'shop_id' => $shop->id,
                        'part_id' => $part->id,
                        'depot_id' => $depot->id,
                        'quantity' => $isCritique
                            ? fake()->numberBetween(0, $alertQty - 1)
                            : fake()->numberBetween($alertQty, 50),
                        'alert_quantity' => $alertQty,
                    ]);
                }
            }
        });
    }
}
