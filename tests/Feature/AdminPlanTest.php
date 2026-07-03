<?php

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    // Super admin : aucun shop_id, rôle super_admin
    $this->superAdmin = User::factory()->create([
        'shop_id' => null,
        'depot_active_id' => null,
    ]);
    $this->superAdmin->assignRole('super_admin');

    // Admin classique d'un atelier
    $this->shop = Shop::factory()->create();
    $this->admin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un super_admin peut voir la liste des plans', function () {
    $response = $this->actingAs($this->superAdmin)->get(route('admin.plans.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Admin/Plans/Index'));
});

test('un admin classique ne peut pas accéder à l\'interface admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.plans.index'));

    $response->assertForbidden();
});

test('un utilisateur non authentifié est redirigé vers le login', function () {
    $response = $this->get(route('admin.plans.index'));

    $response->assertRedirect(route('login'));
});

// ── Création ───────────────────────────────────────────────────────────────

test('un super_admin peut créer un plan', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('admin.plans.store'), [
        'name' => 'Plan Business',
        'slug' => 'business',
        'description' => 'Plan business personnalisé',
        'price' => 15000,
        'max_users' => 10,
        'max_depots' => 3,
        'sort_order' => 99,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('plans', [
        'name' => 'Plan Business',
        'slug' => 'business',
        'price' => 15000,
    ]);
});

test('la création sans nom est rejetée', function () {
    $response = $this->actingAs($this->superAdmin)->post(route('admin.plans.store'), [
        'slug' => 'sans-nom',
        'price' => 0,
        'sort_order' => 0,
    ]);

    $response->assertSessionHasErrors('name');
});

test('un slug en doublon est rejeté', function () {
    Plan::factory()->create(['slug' => 'existant']);

    $response = $this->actingAs($this->superAdmin)->post(route('admin.plans.store'), [
        'name' => 'Nouveau Plan',
        'slug' => 'existant',
        'price' => 0,
        'sort_order' => 0,
    ]);

    $response->assertSessionHasErrors('slug');
});

// ── Modification ───────────────────────────────────────────────────────────

test('un super_admin peut modifier un plan', function () {
    $plan = Plan::factory()->create(['name' => 'Ancien Nom', 'slug' => 'ancien-slug']);

    $response = $this->actingAs($this->superAdmin)->put(route('admin.plans.update', $plan), [
        'name' => 'Nom Modifié',
        'slug' => 'ancien-slug',
        'price' => 20000,
        'sort_order' => 2,
    ]);

    $response->assertRedirect();
    expect($plan->fresh()->name)->toBe('Nom Modifié');
    expect($plan->fresh()->price)->toBe(20000);
});

// ── Suppression ────────────────────────────────────────────────────────────

test('un super_admin peut supprimer un plan sans ateliers', function () {
    $plan = Plan::factory()->create();

    $response = $this->actingAs($this->superAdmin)->delete(route('admin.plans.destroy', $plan));

    $response->assertRedirect();
    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

test('un plan utilisé par des ateliers est désactivé plutôt que supprimé', function () {
    $plan = Plan::factory()->create(['is_active' => true]);
    Shop::factory()->create(['plan_id' => $plan->id]);

    $response = $this->actingAs($this->superAdmin)->delete(route('admin.plans.destroy', $plan));

    $response->assertRedirect();
    $this->assertDatabaseHas('plans', ['id' => $plan->id, 'is_active' => false]);
});
