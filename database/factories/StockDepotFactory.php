<?php

namespace Database\Factories;

use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockDepot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockDepot>
 */
class StockDepotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'part_id' => Part::factory(),
            'depot_id' => Depot::factory(),
            'quantity' => fake()->numberBetween(0, 100),
            'alert_quantity' => fake()->numberBetween(1, 20),
        ];
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(0, 5),
            'alert_quantity' => fake()->numberBetween(5, 10),
        ]);
    }
}
