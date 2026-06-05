<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Part;
use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    private static array $brands = [
        'Apple', 'Samsung', 'Xiaomi', 'Huawei',
        'OnePlus', 'Oppo', 'Google', 'Sony',
    ];

    private static array $models = [
        'iPhone 13', 'iPhone 14', 'Galaxy S22', 'Galaxy A53',
        'Redmi Note 11', 'P30 Pro', 'Pixel 7', 'Xperia 1',
    ];
    public function definition(): array
    {
        $brand   = fake()->randomElement(self::$brands);
        $model   = fake()->unique()->randomElement(self::$models);
        $name    = "{$brand} {$model} " . fake()->randomElement(['LCD', 'OLED', 'Battery', 'Back Glass']);
        $sku     = 'PART-' . fake()->unique()->regexify('[A-Z0-9]{8}');

        return [
            'shop_id'       => Shop::factory(),
            'category_id'   => Categorie::factory(),
            'supplier_id'   => Supplier::factory(),
            'name'          => $name,
            'sku'           => $sku,
            'reference'     => fake()->optional(0.4)->ean13(),
            'selling_price' => fake()->numberBetween(20, 200),
            'cost_price'    => fake()->optional(0.8)->numberBetween(10, 150),
            'quantity'      => fake()->numberBetween(0, 100),
            'reorder_point' => fake()->optional(0.5)->numberBetween(5, 20),
            'is_active'     => true,
        ];
    }
}
