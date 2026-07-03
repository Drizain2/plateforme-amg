<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:remind-expiry')]
#[Description('Envoie des rappels email aux ateliers dont l\'abonnement expire dans 7 ou 1 jour.')]
class SendSubscriptionExpiryReminders extends Command
{
    public function handle(): int
    {
        $thresholds = [7, 1];
        $sent = 0;

        foreach ($thresholds as $days) {
            $target = now()->addDays($days)->toDateString();

            Subscription::with(['shop.users', 'plan'])
                ->whereDate('ends_at', $target)
                ->where('status', 'active')
                ->each(function (Subscription $subscription) use ($days, &$sent): void {
                    $admin = $subscription->shop->users()
                        ->role('admin')
                        ->first();

                    if ($admin instanceof User) {
                        $admin->notify(new SubscriptionExpiringNotification($subscription, $days));
                        $sent++;
                    }
                });
        }

        $this->info("Rappels envoyés : {$sent}");

        return self::SUCCESS;
    }
}
