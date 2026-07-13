<?php

use App\Console\Commands\ExpireTrials;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expires trial subscriptions whose ends_at is in the past', function (): void {
    // Abonnement Trial expiré (ends_at = hier)
    $expired = Subscription::factory()->trial()->create([
        'ends_at' => now()->subDay(),
    ]);

    // Abonnement Trial encore valide (ends_at = dans 5 jours)
    $active = Subscription::factory()->trial()->create([
        'ends_at' => now()->addDays(5),
    ]);

    $this->artisan(ExpireTrials::class)->assertSuccessful();

    expect($expired->fresh()->status)->toBe(SubscriptionStatus::Expired);
    expect($active->fresh()->status)->toBe(SubscriptionStatus::Trial);
});

it('does not touch non-trial subscriptions', function (): void {
    $active = Subscription::factory()->active()->create([
        'ends_at' => now()->subDay(), // actif mais expiré (dunning le gère)
    ]);

    $this->artisan(ExpireTrials::class)->assertSuccessful();

    expect($active->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('outputs the count of expired trials', function (): void {
    Subscription::factory()->trial()->create(['ends_at' => now()->subDay()]);
    Subscription::factory()->trial()->create(['ends_at' => now()->subDays(3)]);

    $this->artisan(ExpireTrials::class)
        ->expectsOutput('Essais expirés traités : 2')
        ->assertSuccessful();
});
