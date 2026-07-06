<?php

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\TrialExpiringNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ── Commande trial:remind-expiry ──────────────────────────────────────────────

test('la commande envoie un rappel à J-3 avant fin d\'essai', function () {
    Notification::fake();

    $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(3)]);
    $admin = User::factory()->admin()->create(['shop_id' => $shop->id]);

    $this->artisan('trial:remind-expiry')->assertSuccessful();

    Notification::assertSentTo($admin, TrialExpiringNotification::class, function ($n) {
        return $n->daysLeft === 3;
    });
});

test('la commande envoie un rappel à J-1 avant fin d\'essai', function () {
    Notification::fake();

    $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(1)]);
    $admin = User::factory()->admin()->create(['shop_id' => $shop->id]);

    $this->artisan('trial:remind-expiry')->assertSuccessful();

    Notification::assertSentTo($admin, TrialExpiringNotification::class, function ($n) {
        return $n->daysLeft === 1;
    });
});

test('la commande n\'envoie pas de rappel si l\'essai expire dans 10 jours', function () {
    Notification::fake();

    $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    User::factory()->admin()->create(['shop_id' => $shop->id]);

    $this->artisan('trial:remind-expiry')->assertSuccessful();

    Notification::assertNothingSent();
});

test('la commande n\'envoie pas de rappel si l\'atelier est inactif', function () {
    Notification::fake();

    $shop = Shop::factory()->create([
        'trial_ends_at' => now()->addDays(3),
        'is_active' => false,
    ]);
    User::factory()->admin()->create(['shop_id' => $shop->id]);

    $this->artisan('trial:remind-expiry')->assertSuccessful();

    Notification::assertNothingSent();
});

// ── Accès bloqué après expiration de l'essai ─────────────────────────────────

test('un atelier dont l\'essai est expiré est bloqué sur le dashboard', function () {
    // Plan payant explicite — les plans seedés par migration ont price=0 (gratuits)
    $plan = Plan::factory()->create(['price' => 15000]);
    $shop = Shop::factory()->create([
        'plan_id' => $plan->id,
        'trial_ends_at' => now()->subDay(),
        'is_active' => true,
    ]);
    $user = User::factory()->admin()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('settings.edit', ['tab' => 'plan']));
});

test('un atelier dont l\'essai est actif accède normalement', function () {
    $plan = Plan::factory()->create(['price' => 15000]);
    $shop = Shop::factory()->create([
        'plan_id' => $plan->id,
        'trial_ends_at' => now()->addDays(7),
        'is_active' => true,
    ]);
    $user = User::factory()->admin()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
