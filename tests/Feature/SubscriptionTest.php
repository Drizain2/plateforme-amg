<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->plan = Plan::factory()->create(['price' => 15000, 'name' => 'Pro']);
    $this->shop = Shop::factory()->create(['plan_id' => $this->plan->id]);
    $this->admin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
    $this->user = User::factory()->create(['shop_id' => $this->shop->id]);
    $this->user->assignRole('technicien');

    app()->instance('current_shop', $this->shop);
});

// ── Accès ─────────────────────────────────────────────────────────────────────

test('un admin peut voir sa page abonnement', function () {
    $this->actingAs($this->admin)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Subscription/Index'));
});

test('un technicien ne peut pas voir la page abonnement', function () {
    $this->actingAs($this->user)
        ->get(route('subscription.index'))
        ->assertForbidden();
});

test('un utilisateur non authentifié est redirigé vers le login', function () {
    $this->get(route('subscription.index'))
        ->assertRedirect(route('login'));
});

// ── Affichage données ─────────────────────────────────────────────────────────

test('la page affiche les paiements de l\'atelier', function () {
    Payment::factory()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('payments.data', 1)
        );
});

test('la page n\'affiche pas les paiements d\'un autre atelier', function () {
    app()->forgetInstance('current_shop');
    $autreShop = Shop::factory()->create(['plan_id' => $this->plan->id]);
    Payment::factory()->create([
        'shop_id' => $autreShop->id,
        'plan_id' => $this->plan->id,
    ]);
    app()->instance('current_shop', $this->shop);

    $this->actingAs($this->admin)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('payments.data', 0));
});

test('la page affiche l\'abonnement actif', function () {
    $sub = Subscription::factory()->active()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('subscription.id', $sub->id)
        );
});

// ── Souscription ───────────────────────────────────────────────────────────────

test('un admin peut soumettre une demande de souscription mensuelle', function () {
    $this->actingAs($this->admin)
        ->post(route('subscription.subscribe'), [
            'plan_id' => $this->plan->id,
            'billing_period' => 'monthly',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', [
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'billing_period' => 'monthly',
        'amount' => 15000,
        'status' => PaymentStatus::Pending->value,
    ]);
});

test('un admin peut soumettre une demande de souscription annuelle', function () {
    $this->actingAs($this->admin)
        ->post(route('subscription.subscribe'), [
            'plan_id' => $this->plan->id,
            'billing_period' => 'annual',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', [
        'shop_id' => $this->shop->id,
        'billing_period' => 'annual',
        'amount' => 150000, // 15000 * 10
    ]);
});

test('un plan gratuit s\'active directement sans paiement', function () {
    $planGratuit = Plan::factory()->create(['price' => 0, 'name' => 'Starter']);
    $this->shop->update(['plan_id' => $planGratuit->id]);
    app()->instance('current_shop', $this->shop->fresh());

    $this->actingAs($this->admin)
        ->post(route('subscription.subscribe'), [
            'plan_id' => $planGratuit->id,
            'billing_period' => 'monthly',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseHas('subscriptions', [
        'shop_id' => $this->shop->id,
        'plan_id' => $planGratuit->id,
        'status' => 'active',
    ]);
});

test('le billing_period est obligatoire', function () {
    $this->actingAs($this->admin)
        ->post(route('subscription.subscribe'), [
            'plan_id' => $this->plan->id,
        ])
        ->assertInvalid(['billing_period']);
});

test('le plan_id doit exister', function () {
    $this->actingAs($this->admin)
        ->post(route('subscription.subscribe'), [
            'plan_id' => 99999,
            'billing_period' => 'monthly',
        ])
        ->assertInvalid(['plan_id']);
});
