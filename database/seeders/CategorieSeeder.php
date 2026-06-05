<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    private array $categories = [
        'Écran',
        'Batterie',
        'Connecteur de charge',
        'Caméra',
        'Haut-parleur',
        'Microphone',
        'Nappe',
        'Châssis',
        'Vitre arrière',
    ];

    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            foreach ($this->categories as $name) {
                Categorie::create([
                    'shop_id' => $shop->id,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }
        });
    }
}
