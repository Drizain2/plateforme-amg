<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Enums\SubscriptionStatus;

#[Signature('app:activate-pending-subscriptions')]
#[Description('Command description')]
class ActivatePendingSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        Subscription::where('status', SubscriptionStatus::Pending->value)
        ->where('starts_at', '<=', now())
        ->each(function (Subscription $subscription) {
            $subscription->shop->subscriptions()
                ->where('status', SubscriptionStatus::Active->value)
                ->where('id', '!=', $subscription->id)
                ->update(['status' => SubscriptionStatus::Cancelled->value, 'cancelled_at' => now()]);

            $subscription->update(['status' => SubscriptionStatus::Active->value]);
            $subscription->shop->update(['plan_id' => $subscription->plan_id]);
        });
    }
}
