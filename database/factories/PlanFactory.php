<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(1000, 50000),
            'max_users' => fake()->numberBetween(1, 20),
            'max_depots' => fake()->numberBetween(1, 5),
            'features' => [fake()->word(), fake()->word(), fake()->word()],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
