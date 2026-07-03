<?php

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRejected;
use App\Notifications\PaymentValidated;
use App\Notifications\SubscriptionExpiringNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ── PaymentReceived ───────────────────────────────────────────────────────────

test('PaymentReceived est envoyée via mail et database', function () {
    Notification::fake();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $shop = Shop::factory()->create();
    $payment = Payment::factory()->create(['shop_id' => $shop->id]);

    $superAdmin->notify(new PaymentReceived($payment));

    Notification::assertSentTo(
        $superAdmin,
        PaymentReceived::class,
        fn ($n) => in_array('mail', $n->via($superAdmin)) && in_array('database', $n->via($superAdmin))
    );
});

// ── PaymentValidated ─────────────────────────────────────────────────────────

test('PaymentValidated est envoyée via mail et database', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();
    $payment = Payment::factory()->create(['shop_id' => $user->shop_id]);
    $subscription = Subscription::factory()->create(['shop_id' => $user->shop_id]);

    $user->notify(new PaymentValidated($payment, $subscription));

    Notification::assertSentTo(
        $user,
        PaymentValidated::class,
        fn ($n) => in_array('mail', $n->via($user)) && in_array('database', $n->via($user))
    );
});

// ── PaymentRejected ───────────────────────────────────────────────────────────

test('PaymentRejected est envoyée via mail et database', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();
    $payment = Payment::factory()->create([
        'shop_id' => $user->shop_id,
        'rejected_reason' => 'Preuve de paiement illisible.',
    ]);

    $user->notify(new PaymentRejected($payment));

    Notification::assertSentTo(
        $user,
        PaymentRejected::class,
        fn ($n) => in_array('mail', $n->via($user)) && in_array('database', $n->via($user))
    );
});

// ── SubscriptionExpiringNotification ─────────────────────────────────────────

test('SubscriptionExpiringNotification est envoyée via mail et database', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'shop_id' => $user->shop_id,
        'plan_id' => $plan->id,
        'ends_at' => now()->addDays(7),
        'status' => 'active',
    ]);

    $user->notify(new SubscriptionExpiringNotification($subscription, 7));

    Notification::assertSentTo(
        $user,
        SubscriptionExpiringNotification::class,
        fn ($n) => in_array('mail', $n->via($user)) && in_array('database', $n->via($user))
    );
});

// ── Commande de rappel ────────────────────────────────────────────────────────

test('la commande de rappel envoie des notifications aux ateliers qui expirent dans 7 jours', function () {
    Notification::fake();

    $shop = Shop::factory()->create();
    $admin = User::factory()->admin()->create(['shop_id' => $shop->id]);
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'plan_id' => $plan->id,
        'ends_at' => now()->addDays(7)->toDateString(),
        'status' => 'active',
    ]);

    $this->artisan('subscriptions:remind-expiry')
        ->assertSuccessful();

    Notification::assertSentTo($admin, SubscriptionExpiringNotification::class);
});

test('la commande de rappel ne notifie pas les abonnements non actifs', function () {
    Notification::fake();

    $shop = Shop::factory()->create();
    $admin = User::factory()->admin()->create(['shop_id' => $shop->id]);
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'plan_id' => $plan->id,
        'ends_at' => now()->addDays(7)->toDateString(),
        'status' => 'expired',
    ]);

    $this->artisan('subscriptions:remind-expiry')
        ->assertSuccessful();

    Notification::assertNothingSent();
});
