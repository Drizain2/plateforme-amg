<?php

namespace Database\Factories;

use App\Models\Plan;
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
        $facker = \Faker\Factory::create('fr_FR');
        $name = $facker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'phone' => $facker->phoneNumber(),
            'email' => $facker->companyEmail(),
            'address' => $facker->address(),
            'plan_id' => Plan::inRandomOrder()->value('id') ?? Plan::factory(),
            'trial_ends_at' => now()->addDays(14),
            'is_active' => true,
        ];
    }

    public function starter(): static
    {
        return $this->state(['plan_id' => Plan::where('slug', 'starter')->value('id') ?? Plan::factory()]);
    }

    public function pro(): static
    {
        return $this->state(['plan_id' => Plan::where('slug', 'pro')->value('id') ?? Plan::factory()]);
    }

    public function withTrial(): static
    {
        return $this->state(['trial_ends_at' => now()->addDays(14)]);
    }
}
