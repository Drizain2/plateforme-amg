<?php

use App\Models\Depot;
use App\Models\Part;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\User;
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
    app()->instance('current_depot', $this->depot);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des dépôts', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.depots.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Depot/Index'));
});

test('un technicien ne peut pas voir la liste des dépôts', function () {
    $response = $this->actingAs($this->technicien)->get(route('stock.depots.index'));

    $response->assertForbidden();
});

// ── Création ───────────────────────────────────────────────────────────────

test('un admin peut créer un dépôt', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.depots.store'), [
        'name' => 'Dépôt Central',
        'address' => '12 rue de la Paix, Dakar',
    ]);

    $response->assertRedirect(route('stock.depots.index'));
    $this->assertDatabaseHas('depots', [
        'shop_id' => $this->shop->id,
        'name' => 'Dépôt Central',
    ]);
});

test('la limite de dépôts du plan bloque la création', function () {
    $plan = Plan::factory()->create(['max_depots' => 1]);
    $this->shop->update(['plan_id' => $plan->id]);
    $this->shop->refresh();

    // 1 dépôt déjà créé dans beforeEach, la limite est atteinte
    $response = $this->actingAs($this->admin)->post(route('stock.depots.store'), [
        'name' => 'Dépôt en trop',
        'address' => 'Quelque part',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('depots', ['name' => 'Dépôt en trop']);
});

test('un technicien ne peut pas créer un dépôt', function () {
    $response = $this->actingAs($this->technicien)->post(route('stock.depots.store'), [
        'name' => 'Tentative',
        'address' => 'Adresse',
    ]);

    $response->assertForbidden();
});

// ── Modification ───────────────────────────────────────────────────────────

test('un admin peut modifier un dépôt', function () {
    $response = $this->actingAs($this->admin)->put(route('stock.depots.update', $this->depot), [
        'name' => 'Nom modifié',
        'address' => 'Nouvelle adresse',
    ]);

    $response->assertRedirect();
    expect($this->depot->fresh()->name)->toBe('Nom modifié');
});

// ── Suppression ────────────────────────────────────────────────────────────

test('un admin peut supprimer un dépôt sans stock', function () {
    $depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->delete(route('stock.depots.destroy', $depot));

    $response->assertRedirect();
    $this->assertDatabaseMissing('depots', ['id' => $depot->id]);
});

test('un dépôt avec stock est désactivé plutôt que supprimé', function () {
    $depot = Depot::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
    // Le middleware BootTenantScope lit depot_active_id pour rebinder current_depot ;
    // on pointe l'admin sur ce dépôt pour que HasDepotScope voie le bon stock.
    $this->admin->update(['depot_active_id' => $depot->id]);

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $depot->id,
        'part_id' => $part->id,
        'quantity' => 5,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('stock.depots.destroy', $depot));

    $response->assertRedirect();
    $this->assertDatabaseHas('depots', ['id' => $depot->id, 'is_active' => false]);
});

// ── Utilisateurs attachés ──────────────────────────────────────────────────

test('un admin peut attacher un technicien à un dépôt', function () {
    $technicien2 = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('stock.depots.attach-user', $this->depot), [
            'user_id' => $technicien2->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('depot_user', [
        'depot_id' => $this->depot->id,
        'user_id' => $technicien2->id,
    ]);
});

test('un admin peut détacher un technicien d\'un dépôt', function () {
    $this->depot->users()->attach($this->technicien->id);

    $response = $this->actingAs($this->admin)
        ->delete(route('stock.depots.detach-user', [$this->depot, $this->technicien]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('depot_user', [
        'depot_id' => $this->depot->id,
        'user_id' => $this->technicien->id,
    ]);
});
