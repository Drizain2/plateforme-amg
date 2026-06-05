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

    private static array $phoneModels = [
        'iPhone 13', 'iPhone 14', 'Galaxy S22', 'Galaxy A53',
        'Redmi Note 11', 'P30 Pro', 'Pixel 7', 'Xperia 1',
    ];

    private static array $partTypes = [
        'LCD', 'OLED', 'Batterie', 'Vitre arrière',
        'Nappe', 'Connecteur de charge', 'Caméra',
    ];

    public function definition(): array
    {
        $brand = fake()->randomElement(self::$brands);
        $model = fake()->randomElement(self::$phoneModels);
        $type = fake()->randomElement(self::$partTypes);
        $name = "{$brand} {$model} {$type}";
        $sku = 'PART-'.fake()->unique()->regexify('[A-Z0-9]{8}');

        return [
            'shop_id' => Shop::factory(),
            'category_id' => Categorie::factory(),
            'supplier_id' => Supplier::factory(),
            'name' => $name,
            'sku' => $sku,
            'brand_compat' => fake()->boolean(60) ? [fake()->randomElement(self::$brands)] : null,
            'unit_price' => fake()->numberBetween(10, 150),
            'sell_price' => fake()->numberBetween(20, 200),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
