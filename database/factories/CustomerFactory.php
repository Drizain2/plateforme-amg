<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    private static string $locale = 'fr_FR';

    public function definition(): array
    {
        $faker = \Faker\Factory::create(self::$locale);

        return [
            'shop_id' => Shop::factory(),
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->optional(0.5)->address(),
            'notes' => null,
        ];
    }
}
