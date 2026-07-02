<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Device;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    private static array $types = ['smartphone', 'tablette', 'ordinateur', 'console', 'montre'];

    private static array $brands = ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'OnePlus', 'Sony', 'Google'];

    private static array $models = [
        'iPhone 13', 'iPhone 14 Pro', 'Galaxy S22', 'Galaxy A53',
        'Redmi Note 11', 'P30 Pro', 'Pixel 7', 'Xperia 1 IV',
    ];

    public function definition(): array
    {
        $shop = Shop::factory();

        return [
            'shop_id' => $shop,
            'customer_id' => Customer::factory()->state(['shop_id' => $shop]),
            'type' => fake()->randomElement(self::$types),
            'brand' => fake()->randomElement(self::$brands),
            'model' => fake()->randomElement(self::$models),
            'serial_number' => fake()->optional(0.6)->regexify('[A-Z0-9]{12}'),
            'color' => fake()->optional(0.5)->safeColorName(),
            'condition_in' => fake()->optional(0.7)->sentence(),
        ];
    }
}
