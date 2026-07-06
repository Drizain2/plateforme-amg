<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('trial:remind-expiry')]
#[Description("Envoie des rappels email aux ateliers dont l'essai gratuit expire dans 3 ou 1 jour.")]
class SendTrialExpiryReminders extends Command
{
    public function handle(): int
    {
        $thresholds = [3, 1];
        $sent = 0;

        foreach ($thresholds as $days) {
            $target = now()->addDays($days)->toDateString();

            Shop::where('is_active', true)
                ->whereDate('trial_ends_at', $target)
                ->each(function (Shop $shop) use ($days, &$sent): void {
                    $admin = $shop->admin;

                    if ($admin instanceof User) {
                        $admin->notify(new TrialExpiringNotification($shop, $days));
                        $sent++;
                    }
                });
        }

        $this->info("Rappels d'essai envoyés : {$sent}");

        return self::SUCCESS;
    }
}
