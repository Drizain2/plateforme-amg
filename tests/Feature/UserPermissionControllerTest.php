<?php

use App\Models\Depot;
use App\Models\Shop;
use App\Models\ShopUserPermission;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $this->technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    app()->instance('current_shop', $this->shop);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la page des permissions d\'un utilisateur', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('users.permissions.index', $this->technicien));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Users/Permissions')
        ->where('targetUser.id', $this->technicien->id)
        ->has('allPermissions')
        ->has('effectivePerms')
        ->has('overrides')
    );
});

test('un technicien ne peut pas accéder à la page des permissions', function () {
    $response = $this->actingAs($this->technicien)
        ->get(route('users.permissions.index', $this->admin));

    $response->assertForbidden();
});

// ── Grant ──────────────────────────────────────────────────────────────────

test('un admin peut accorder une permission hors rôle à un technicien', function () {
    // invoices.edit n'est pas dans le rôle technicien par défaut
    $response = $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'invoices.edit',
            'action' => 'grant',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('shop_user_permissions', [
        'shop_id' => $this->shop->id,
        'user_id' => $this->technicien->id,
        'permission' => 'invoices.edit',
        'granted' => true,
    ]);
});

test('un grant rend la permission effective immédiatement via PermissionService', function () {
    $service = app(PermissionService::class);

    // invoices.edit n'est pas dans le rôle technicien par défaut
    expect($service->has($this->technicien, 'invoices.edit'))->toBeFalse();

    $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'invoices.edit',
            'action' => 'grant',
        ]);

    expect($service->has($this->technicien->fresh(), 'invoices.edit'))->toBeTrue();
});

// ── Revoke ─────────────────────────────────────────────────────────────────

test('un admin peut révoquer une permission du rôle d\'un technicien', function () {
    // tickets.view est dans le rôle technicien par défaut
    $response = $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'tickets.view',
            'action' => 'revoke',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('shop_user_permissions', [
        'user_id' => $this->technicien->id,
        'permission' => 'tickets.view',
        'granted' => false,
    ]);
});

test('un revoke retire la permission de l\'effectif même si le rôle la possède', function () {
    $service = app(PermissionService::class);

    expect($service->has($this->technicien, 'tickets.view'))->toBeTrue();

    $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'tickets.view',
            'action' => 'revoke',
        ]);

    expect($service->has($this->technicien->fresh(), 'tickets.view'))->toBeFalse();
});

// ── Reset individuel ────────────────────────────────────────────────────────

test('un admin peut réinitialiser un override individuel', function () {
    ShopUserPermission::create([
        'shop_id' => $this->shop->id,
        'user_id' => $this->technicien->id,
        'permission' => 'invoices.create',
        'granted' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'invoices.create',
            'action' => 'reset',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseMissing('shop_user_permissions', [
        'user_id' => $this->technicien->id,
        'permission' => 'invoices.create',
    ]);
});

// ── Reset complet ───────────────────────────────────────────────────────────

test('un admin peut réinitialiser toutes les permissions au rôle', function () {
    ShopUserPermission::create([
        'shop_id' => $this->shop->id,
        'user_id' => $this->technicien->id,
        'permission' => 'invoices.create',
        'granted' => true,
    ]);
    ShopUserPermission::create([
        'shop_id' => $this->shop->id,
        'user_id' => $this->technicien->id,
        'permission' => 'tickets.view',
        'granted' => false,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('users.permissions.reset', $this->technicien));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('shop_user_permissions', [
        'user_id' => $this->technicien->id,
    ]);
});

// ── Validation ─────────────────────────────────────────────────────────────

test('une permission inexistante est rejetée', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'permission.qui.nexiste.pas',
            'action' => 'grant',
        ]);

    $response->assertSessionHasErrors('permission');
});

test('une action invalide est rejetée', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $this->technicien), [
            'permission' => 'tickets.view',
            'action' => 'give',
        ]);

    $response->assertSessionHasErrors('action');
});

// ── Isolation multi-tenant ─────────────────────────────────────────────────

test("un admin ne peut pas modifier les permissions d'un utilisateur d'un autre atelier", function () {
    $autreShop = Shop::factory()->create();
    $userAutre = User::factory()->technicien()->create(['shop_id' => $autreShop->id]);

    $response = $this->actingAs($this->admin)
        ->post(route('users.permissions.update', $userAutre), [
            'permission' => 'tickets.view',
            'action' => 'grant',
        ]);

    $response->assertForbidden();
});

test("un admin ne peut pas voir les permissions d'un utilisateur d'un autre atelier", function () {
    $autreShop = Shop::factory()->create();
    $userAutre = User::factory()->technicien()->create(['shop_id' => $autreShop->id]);

    $response = $this->actingAs($this->admin)
        ->get(route('users.permissions.index', $userAutre));

    $response->assertForbidden();
});
