<?php

use App\Models\Depot;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create(['name' => 'Atelier Test']);
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
        'name' => 'Admin Test',
        'email' => 'admin@atelier.test',
    ]);

    $this->technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
        'name' => 'Tech Test',
        'email' => 'tech@atelier.test',
    ]);

    app()->instance('current_shop', $this->shop);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un utilisateur authentifié peut accéder à la page des paramètres', function () {
    $response = $this->actingAs($this->admin)->get(route('settings.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->has('shop')
        ->has('profile')
    );
});

// ── Profil personnel ────────────────────────────────────────────────────────

test('tout utilisateur peut modifier son propre nom et email', function () {
    $response = $this->actingAs($this->technicien)->put(route('settings.profile'), [
        'name' => 'Nouveau Nom',
        'email' => 'nouveau@example.com',
    ]);

    $response->assertRedirect();
    expect($this->technicien->fresh()->name)->toBe('Nouveau Nom');
    expect($this->technicien->fresh()->email)->toBe('nouveau@example.com');
});

test('un email déjà utilisé par un autre compte est rejeté', function () {
    $response = $this->actingAs($this->technicien)->put(route('settings.profile'), [
        'name' => 'Tech Test',
        'email' => 'admin@atelier.test',
    ]);

    $response->assertSessionHasErrors('email');
});

// ── Mot de passe ────────────────────────────────────────────────────────────

test('un utilisateur peut changer son mot de passe', function () {
    // Le mot de passe par défaut de la factory est 'password'
    $response = $this->actingAs($this->admin)->put(route('settings.password'), [
        'current_password' => 'password',
        'password' => 'NouveauMotDePasse123!',
        'password_confirmation' => 'NouveauMotDePasse123!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('les mots de passe non correspondants sont rejetés', function () {
    $response = $this->actingAs($this->admin)->put(route('settings.password'), [
        'password' => 'MotDePasse1!',
        'password_confirmation' => 'AutreMotDePasse2!',
    ]);

    $response->assertSessionHasErrors('password');
});

// ── Paramètres de l'atelier ────────────────────────────────────────────────

test('un admin peut modifier les paramètres de l\'atelier', function () {
    $response = $this->actingAs($this->admin)->post(route('settings.shop'), [
        'name' => 'Atelier Modifié',
        'email' => 'contact@atelier-modifie.test',
        'tax_rate' => 18,
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($this->shop->fresh()->name)->toBe('Atelier Modifié');
    expect($this->shop->fresh()->email)->toBe('contact@atelier-modifie.test');
});

test('un technicien ne peut pas modifier les paramètres de l\'atelier', function () {
    $response = $this->actingAs($this->technicien)->post(route('settings.shop'), [
        'name' => 'Tentative',
        'email' => 'tentative@example.com',
        'tax_rate' => 20,
    ]);

    $response->assertForbidden();
});

// ── Changement de plan ──────────────────────────────────────────────────────

test('un admin peut changer le plan de l\'atelier', function () {
    $nouveauPlan = Plan::factory()->create([
        'is_active' => true,
        'max_users' => 10,
        'max_depots' => 5,
    ]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $nouveauPlan));

    $response->assertRedirect();
    expect($this->shop->fresh()->plan_id)->toBe($nouveauPlan->id);
});

test('choisir un plan inactif retourne une erreur', function () {
    $planInactif = Plan::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $planInactif));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($this->shop->fresh()->plan_id)->not->toBe($planInactif->id);
});

test('un downgrade bloqué par les limites retourne une erreur', function () {
    // Créer 3 utilisateurs pour l'atelier
    User::factory()->technicien()->count(2)->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    // L'atelier a maintenant 3 users (admin + 2 techniciens)

    $planRestrictif = Plan::factory()->create([
        'is_active' => true,
        'max_users' => 2,
        'max_depots' => 5,
    ]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $planRestrictif));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($this->shop->fresh()->plan_id)->not->toBe($planRestrictif->id);
});
