<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class DepotSeeder extends Seeder
{
    private array $depotNames = [
        'Dépôt principal',
        'Atelier arrière',
        'Réserve',
        'Stock secondaire',
        'Magasin vitrine',
    ];

    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $count = fake()->numberBetween(2, 3);
            $names = array_slice($this->depotNames, 0, $count);

            $depots = collect($names)->map(
                fn ($name) => Depot::factory()->for($shop)->create(['name' => $name])
            );

            // Assigner les techniciens à au moins un dépôt
            $shop->users()->role('technicien')->get()->each(function ($tech) use ($depots) {
                $tech->depots()->sync(
                    $depots->random(fake()->numberBetween(1, $depots->count()))->pluck('id')
                );
            });

            // L'admin a accès à tous les dépôts
            $shop->users()->role('admin')->first()?->depots()->sync($depots->pluck('id'));
        });
    }
}
