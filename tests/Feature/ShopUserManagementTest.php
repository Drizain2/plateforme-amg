<?php

use App\Models\Depot;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    app()->instance('current_shop', $this->shop);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des utilisateurs', function () {
    $response = $this->actingAs($this->admin)->get(route('users.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Users/Index'));
});

test('un technicien ne peut pas voir la liste des utilisateurs', function () {
    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($technicien)->get(route('users.index'));

    $response->assertForbidden();
});

// ── Création ───────────────────────────────────────────────────────────────

test('un admin peut inviter un utilisateur avec le rôle gestionnaire', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Nadia Gestionnaire',
        'email' => 'nadia@example.com',
        'role' => 'manager',
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect();

    $user = User::where('email', 'nadia@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('manager'))->toBeTrue();
});

test('un admin peut inviter un technicien', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Ali Technicien',
        'email' => 'ali@example.com',
        'role' => 'technicien',
    ]);

    $response->assertRedirect();
    $user = User::where('email', 'ali@example.com')->first();
    expect($user->hasRole('technicien'))->toBeTrue();
    expect($user->shop_id)->toBe($this->shop->id);
});

test('un admin peut inviter une caissière', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Fatou Caissière',
        'email' => 'fatou@example.com',
        'role' => 'caissiere',
    ]);

    $response->assertRedirect();
    expect(User::where('email', 'fatou@example.com')->first()->hasRole('caissiere'))->toBeTrue();
});

test("un email déjà utilisé dans l'atelier est rejeté", function () {
    User::factory()->create([
        'shop_id' => $this->shop->id,
        'email' => 'doublon@example.com',
    ]);

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Autre',
        'email' => 'doublon@example.com',
        'role' => 'technicien',
    ]);

    $response->assertSessionHasErrors('email');
});

test("le rôle obsolète gestionnaire n'est plus accepté", function () {
    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Test',
        'email' => 'test@example.com',
        'role' => 'gestionnaire',
    ]);

    $response->assertSessionHasErrors('role');
});

test("la limite d'utilisateurs du plan bloque l'invitation", function () {
    Notification::fake();

    $plan = Plan::factory()->create(['max_users' => 1]);
    $this->shop->update(['plan_id' => $plan->id]);
    $this->shop->refresh();

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'En trop',
        'email' => 'entrop@example.com',
        'role' => 'technicien',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('users', ['email' => 'entrop@example.com']);
});

// ── Modification ───────────────────────────────────────────────────────────

test("un admin peut changer le rôle d'un utilisateur", function () {
    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)->put(route('users.update', $technicien), [
        'role' => 'caissiere',
    ]);

    $response->assertRedirect();
    expect($technicien->fresh()->hasRole('caissiere'))->toBeTrue();
    expect($technicien->fresh()->hasRole('technicien'))->toBeFalse();
});

test("un admin peut modifier le nom d'un utilisateur", function () {
    $user = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)->put(route('users.update', $user), [
        'name' => 'Nouveau Nom',
    ]);

    $response->assertRedirect();
    expect($user->fresh()->name)->toBe('Nouveau Nom');
});

test("un admin ne peut pas modifier un utilisateur d'un autre atelier", function () {
    $autreShop = Shop::factory()->create();
    $userAutre = User::factory()->technicien()->create(['shop_id' => $autreShop->id]);

    $response = $this->actingAs($this->admin)->put(route('users.update', $userAutre), [
        'name' => 'Tentative',
    ]);

    $response->assertForbidden();
});

test('un admin ne peut pas se modifier lui-même via ce controller', function () {
    $response = $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
        'name' => 'Nouveau Nom',
    ]);

    $response->assertForbidden();
});

// ── Suppression ────────────────────────────────────────────────────────────

test('un admin peut supprimer un technicien sans tickets', function () {
    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('users.destroy', $technicien));

    $response->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $technicien->id]);
});

test('la suppression du dernier admin par lui-même est bloquée', function () {
    // authorizeUser interdit de modifier son propre compte via ce controller
    $response = $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin));

    $response->assertForbidden();
});

test('un utilisateur avec tickets créés est désactivé plutôt que supprimé', function () {
    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    Ticket::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'created_by' => $technicien->id,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('users.destroy', $technicien));

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $technicien->id, 'is_active' => false]);
});

test("un admin ne peut pas supprimer un utilisateur d'un autre atelier", function () {
    $autreShop = Shop::factory()->create();
    $userAutre = User::factory()->technicien()->create(['shop_id' => $autreShop->id]);

    $response = $this->actingAs($this->admin)->delete(route('users.destroy', $userAutre));

    $response->assertForbidden();
});

// ── Activation / désactivation ─────────────────────────────────────────────

test('un admin peut désactiver un technicien', function () {
    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->post(route('users.toggle-active', $technicien));

    $response->assertRedirect();
    expect((bool) $technicien->fresh()->is_active)->toBeFalse();
});

test('désactiver son propre compte est bloqué', function () {
    $response = $this->actingAs($this->admin)->post(route('users.toggle-active', $this->admin));

    $response->assertForbidden();
});

// ── Réinitialisation du mot de passe ───────────────────────────────────────

test('un admin peut envoyer un email de réinitialisation', function () {
    Notification::fake();

    $technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)->post(route('users.reset-password', $technicien));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});
