<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = now()->subDay();

        return [
            'shop_id' => Shop::factory(),
            'plan_id' => Plan::factory(),
            'billing_period' => BillingPeriod::Monthly,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonth(),
            'status' => SubscriptionStatus::Active,
            'gateway' => 'manual',
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
    }

    public function annual(): static
    {
        return $this->state([
            'billing_period' => BillingPeriod::Annual,
            'ends_at' => now()->addYear(),
        ]);
    }

    public function trial(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Trial,
            'ends_at' => now()->addDays(14),
        ]);
    }
}
