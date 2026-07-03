<?php

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    // Super admin plateforme (pas de shop)
    $this->superAdmin = User::factory()->create([
        'shop_id' => null,
        'depot_active_id' => null,
    ]);
    $this->superAdmin->assignRole('super_admin');

    // Atelier + admin classique
    $this->plan = Plan::factory()->create(['price' => 15000]);
    $this->shop = Shop::factory()->create(['plan_id' => $this->plan->id]);
    $this->admin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
});

// ── Accès ─────────────────────────────────────────────────────────────────────

test('un super_admin peut voir la liste des paiements', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/Payments/Index'));
});

test('un admin classique ne peut pas accéder à la gestion des paiements', function () {
    app()->instance('current_shop', $this->shop);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertForbidden();
});

test('un utilisateur non authentifié est redirigé vers le login', function () {
    $this->get(route('admin.payments.index'))
        ->assertRedirect(route('login'));
});

// ── Approbation ───────────────────────────────────────────────────────────────

test('un super_admin peut approuver un paiement en attente', function () {
    $payment = Payment::factory()->pending()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.payments.approve', $payment))
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Validated);

    $this->assertDatabaseHas('subscriptions', [
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active->value,
    ]);
});

test('approuver un paiement déjà validé retourne une erreur 422', function () {
    $payment = Payment::factory()->validated()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.payments.approve', $payment))
        ->assertStatus(422);
});

// ── Rejet ─────────────────────────────────────────────────────────────────────

test('un super_admin peut rejeter un paiement en attente', function () {
    $payment = Payment::factory()->pending()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.payments.reject', $payment), [
            'reason' => 'Virement introuvable',
        ])
        ->assertRedirect();

    $fresh = $payment->fresh();
    expect($fresh->status)->toBe(PaymentStatus::Rejected)
        ->and($fresh->rejected_reason)->toBe('Virement introuvable');
});

test('rejeter un paiement nécessite un motif', function () {
    $payment = Payment::factory()->pending()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.payments.reject', $payment), [])
        ->assertInvalid(['reason']);
});

test('rejeter un paiement déjà rejeté retourne une erreur 422', function () {
    $payment = Payment::factory()->rejected()->create([
        'shop_id' => $this->shop->id,
        'plan_id' => $this->plan->id,
    ]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.payments.reject', $payment), [
            'reason' => 'Autre raison',
        ])
        ->assertStatus(422);
});

// ── Filtres ───────────────────────────────────────────────────────────────────

test('la liste peut être filtrée par statut', function () {
    Payment::factory()->pending()->create(['shop_id' => $this->shop->id, 'plan_id' => $this->plan->id]);
    Payment::factory()->validated()->create(['shop_id' => $this->shop->id, 'plan_id' => $this->plan->id]);

    $this->actingAs($this->superAdmin)
        ->get(route('admin.payments.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('payments.data', 1));
});
