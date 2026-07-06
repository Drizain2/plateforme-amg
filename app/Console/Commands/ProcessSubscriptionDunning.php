<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionOverdueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:process-dunning')]
#[Description('Relances post-expiration : email à J+3, suspension à J+7 (sauf paiement en attente).')]
class ProcessSubscriptionDunning extends Command
{
    public function handle(): int
    {
        $notified = 0;
        $suspended = 0;

        // J+3 → email de relance
        // J+7 → email de suspension + désactivation du shop
        foreach ([3, 7] as $days) {
            $target = now()->subDays($days)->toDateString();

            Subscription::with(['shop', 'plan'])
                ->whereDate('ends_at', $target)
                ->where('status', SubscriptionStatus::Active->value)
                ->whereHas('shop', fn ($q) => $q->where('is_active', true))
                ->each(function (Subscription $subscription) use ($days, &$notified, &$suspended): void {
                    // Ne pas agir si un paiement est déjà en attente de validation.
                    $hasPending = Payment::where('shop_id', $subscription->shop_id)
                        ->where('status', PaymentStatus::Pending)
                        ->exists();

                    if ($hasPending) {
                        return;
                    }

                    if ($days === 7) {
                        $subscription->shop->update(['is_active' => false]);
                        $subscription->update(['status' => SubscriptionStatus::Suspended]);
                        $suspended++;
                    }

                    $admin = $subscription->shop->users()->role('admin')->first();

                    if ($admin instanceof User) {
                        $admin->notify(new SubscriptionOverdueNotification($subscription, $days));
                        $notified++;
                    }
                });
        }

        $this->info("Relances envoyées : {$notified}, ateliers suspendus : {$suspended}.");

        return self::SUCCESS;
    }
}
