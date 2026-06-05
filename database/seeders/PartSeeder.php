<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Part;
use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    private array $partTypes = ['LCD', 'OLED', 'Batterie', 'Vitre arrière', 'Nappe', 'Connecteur de charge', 'Caméra', 'Haut-parleur'];

    private array $brands = ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'OnePlus', 'Oppo', 'Google', 'Sony'];

    private array $models = ['iPhone 13', 'iPhone 14', 'Galaxy S22', 'Galaxy A53', 'Redmi Note 11', 'P30 Pro', 'Pixel 7', 'Xperia 1'];

    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $categories = Categorie::all();
            $suppliers = Supplier::all();

            $usedSkus = [];

            for ($i = 0; $i < 20; $i++) {
                $brand = fake()->randomElement($this->brands);
                $model = fake()->randomElement($this->models);
                $type = fake()->randomElement($this->partTypes);

                // SKU unique par shop
                do {
                    $sku = 'PART-'.strtoupper(fake()->bothify('??######'));
                } while (in_array($sku, $usedSkus));

                $usedSkus[] = $sku;

                $costPrice = fake()->numberBetween(10, 150);

                Part::create([
                    'shop_id' => $shop->id,
                    'category_id' => $categories->random()->id,
                    'supplier_id' => fake()->boolean(80) ? $suppliers->random()->id : null,
                    'name' => "{$brand} {$model} {$type}",
                    'sku' => $sku,
                    'brand_compat' => fake()->boolean(60) ? [$brand] : null,
                    'unit_price' => $costPrice,
                    'sell_price' => (int) ($costPrice * fake()->randomFloat(1, 1.2, 2.5)),
                    'is_active' => true,
                ]);
            }
        });
    }
}
