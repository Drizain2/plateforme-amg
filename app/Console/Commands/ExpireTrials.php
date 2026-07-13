<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('trial:expire')]
#[Description("Passe en 'Expired' les abonnements Trial dont la date de fin est dépassée.")]
class ExpireTrials extends Command
{
    public function handle(): int
    {
        $expired = Subscription::where('status', SubscriptionStatus::Trial)
            ->where('ends_at', '<', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $this->info("Essais expirés traités : {$expired}");

        return self::SUCCESS;
    }
}
