<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'plan_id' => Plan::factory(),
            'billing_period' => BillingPeriod::Monthly,
            'amount' => 15000,
            'currency' => 'XOF',
            'reference' => 'PAY-'.now()->year.'-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => PaymentStatus::Pending,
            'gateway' => 'manual',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => PaymentStatus::Pending]);
    }

    public function validated(): static
    {
        return $this->state([
            'status' => PaymentStatus::Validated,
            'validated_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => PaymentStatus::Rejected,
            'rejected_reason' => 'Virement non reçu',
            'rejected_at' => now(),
        ]);
    }
}
