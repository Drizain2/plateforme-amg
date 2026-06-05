<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    private static array $suppliers = [
        'GSM Partner', 'Phone Parts Pro', 'iStock Europe',
        'Repair Supply', 'TechParts FR', 'MobilePro',
    ];
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id'   => Shop::factory(),
            'name'      => fake()->randomElement(self::$suppliers) . ' ' . fake()->companySuffix(),
            'email'     => fake()->optional(0.8)->companyEmail(),
            'phone'     => fake()->optional(0.8)->phoneNumber(),
            'address'   => fake()->optional(0.6)->address(),
            'is_active' => true,
        ];
    }
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
