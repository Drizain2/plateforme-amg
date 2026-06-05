<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $facker = \Faker\Factory::create("fr_FR");
        $name = $facker->company();
        return [
            "name" => $name,
            "slug" => Str::slug($name). '-'.fake()->unique()->numberBetween(100, 999),
            "phone" => $facker->phoneNumber(),
            "email" => $facker->companyEmail(),
            "address" => $facker->address(),
            'plan' => fake()->randomElement(['starter', 'pro', 'enterprise']),
            'trial_ends_at' => fake()->optional(0.3)->dateTimeBetween('now', '+30 days'),
            "is_active" => true,
        ];
    }
    public function starter(): static
    {
        return $this->state(['plan' => 'starter']);
    }

    public function pro(): static
    {
        return $this->state(['plan' => 'pro']);
    }

    public function withTrial(): static
    {
        return $this->state(['trial_ends_at' => now()->addDays(14)]);
    }
}
