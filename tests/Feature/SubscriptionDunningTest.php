<?php

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionOverdueNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->plan = Plan::factory()->create(['price' => 15000]);
    $this->shop = Shop::factory()->create(['plan_id' => $this->plan->id, 'is_active' => true]);
    $this->admin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
});

// ── J+3 : email de relance ─────────────────────────────────────────────────

test('la commande envoie une relance à J+3 après expiration', function () {
    Subscription::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'ends_at' => now()->subDays(3),
    ]);

    $this->artisan('subscriptions:process-dunning')->assertSuccessful();

    Notification::assertSentTo($this->admin, SubscriptionOverdueNotification::class, function ($n) {
        return $n->daysOverdue === 3;
    });
});

test('la commande n\'envoie pas de relance à J+10', function () {
    Subscription::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'ends_at' => now()->subDays(10),
    ]);

    $this->artisan('subscriptions:process-dunning')->assertSuccessful();

    Notification::assertNothingSent();
});

// ── J+7 : suspension ──────────────────────────────────────────────────────

test('la commande suspend le shop à J+7 et envoie un email', function () {
    $subscription = Subscription::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'ends_at' => now()->subDays(7),
    ]);

    $this->artisan('subscriptions:process-dunning')->assertSuccessful();

    expect($this->shop->fresh()->is_active)->toBeFalse();
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Suspended);

    Notification::assertSentTo($this->admin, SubscriptionOverdueNotification::class, function ($n) {
        return $n->daysOverdue === 7;
    });
});

// ── Guard : paiement en attente ───────────────────────────────────────────

test('la commande ne suspend pas si un paiement est en attente', function () {
    $subscription = Subscription::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'ends_at' => now()->subDays(7),
    ]);

    Payment::factory()->pending()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->artisan('subscriptions:process-dunning')->assertSuccessful();

    expect($this->shop->fresh()->is_active)->toBeTrue();
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    Notification::assertNothingSent();
});

test('la commande ne relance pas si un paiement est en attente à J+3', function () {
    Subscription::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'ends_at' => now()->subDays(3),
    ]);

    Payment::factory()->pending()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->artisan('subscriptions:process-dunning')->assertSuccessful();

    Notification::assertNothingSent();
});

// ── Guard : shop déjà suspendu ────────────────────────────────────────────

test('la commande ignore les shops déjà suspendus', function () {
    $this->shop->update(['is_active' => false]);

    Subscription::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'ends_at' => now()->subDays(3),
    ]);

    $this->artisan('subscriptions:process-dunning')->assertSuccessful();

    Notification::assertNothingSent();
});
