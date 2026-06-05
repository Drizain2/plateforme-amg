<?php

namespace Database\Factories;

use App\Models\Depot;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'depot_id' => Depot::factory(),
            'stock_id' => StockDepot::factory(),
            'user_id' => null,
            'ticket_id' => null,
            'type' => fake()->randomElement(['in', 'out', 'adjustment']),
            'quantity' => fake()->numberBetween(1, 50),
            'transfer_depot_id' => null,
            'note' => fake()->optional(0.5)->sentence(),
        ];
    }

    public function entree(): static
    {
        return $this->state(['type' => 'in']);
    }

    public function sortie(): static
    {
        return $this->state(['type' => 'out']);
    }

    public function adjustment(): static
    {
        return $this->state(['type' => 'adjustment']);
    }

    public function byUser(): static
    {
        return $this->state(['user_id' => User::factory()]);
    }
}
