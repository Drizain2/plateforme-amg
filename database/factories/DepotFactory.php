<?php

namespace Database\Factories;

use App\Models\Depot;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Depot>
 */
class DepotFactory extends Factory
{
    private static array $depotNames = [
        'Dépôt principal', 'Atelier arrière', 'Réserve',
        'Stock secondaire', 'Magasin vitrine', 'Entrepôt central',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->randomElement(self::$depotNames),
            'address' => fake()->optional(0.7)->address(),
            'phone' => fake()->optional(0.5)->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
