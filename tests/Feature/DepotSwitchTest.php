<?php

use App\Models\Depot;
use App\Models\Shop;
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

// ── Sélection de dépôt (non-admins) ────────────────────────────────────────

test('un technicien avec un seul dépôt est redirigé vers le dashboard', function () {
    // Technicien n'a accès qu'au dépôt via depot_user (aucun)
    // → la méthode select() renvoie redirect si count <= 1
    $response = $this->actingAs($this->technicien)->get(route('depot.select'));

    $response->assertRedirect(route('dashboard'));
});

test('un technicien avec plusieurs dépôts voit la page de sélection', function () {
    $depot2 = Depot::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
    $this->depot->update(['is_active' => true]);
    $this->technicien->depots()->attach([$this->depot->id, $depot2->id]);

    $response = $this->actingAs($this->technicien)->get(route('depot.select'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Auth/DepotSelect')
        ->has('depots', 2)
    );
});

// ── Sauvegarde du dépôt sélectionné ────────────────────────────────────────

test('un technicien peut enregistrer un dépôt auquel il a accès', function () {
    $depot2 = Depot::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
    $this->technicien->depots()->attach($depot2->id);

    $response = $this->actingAs($this->technicien)->post(route('depot.save'), [
        'depot_id' => $depot2->id,
    ]);

    $response->assertRedirect();
    expect($this->technicien->fresh()->depot_active_id)->toBe($depot2->id);
});

test("un technicien ne peut pas sélectionner un dépôt auquel il n'a pas accès", function () {
    $depotAutre = Depot::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
    // Pas de ligne dans depot_user pour ce technicien + depotAutre

    $response = $this->actingAs($this->technicien)->post(route('depot.save'), [
        'depot_id' => $depotAutre->id,
    ]);

    $response->assertForbidden();
    expect($this->technicien->fresh()->depot_active_id)->toBe($this->depot->id);
});

test('un dépôt inexistant retourne une erreur de validation', function () {
    $response = $this->actingAs($this->technicien)->post(route('depot.save'), [
        'depot_id' => 99999,
    ]);

    $response->assertSessionHasErrors('depot_id');
});

// ── Changement de dépôt (admin) ────────────────────────────────────────────

test('un admin peut changer son dépôt actif', function () {
    $depot2 = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->post(route('depot.switch'), [
        'depot_id' => $depot2->id,
    ]);

    $response->assertRedirect();
    expect($this->admin->fresh()->depot_active_id)->toBe($depot2->id);
});

test("un admin ne peut pas passer sur un dépôt d'un autre atelier", function () {
    $autreShop = Shop::factory()->create();

    // Désactiver current_shop pour éviter que HasShopScope::creating n'override shop_id
    app()->forgetInstance('current_shop');
    $depotAutre = Depot::factory()->create(['shop_id' => $autreShop->id]);
    app()->instance('current_shop', $this->shop);

    $response = $this->actingAs($this->admin)->post(route('depot.switch'), [
        'depot_id' => $depotAutre->id,
    ]);

    // HasShopScope filtre le dépôt étranger → 404 (plutôt que 403 explicite du abort_unless)
    $response->assertNotFound();
});

test('un technicien ne peut pas utiliser la route switch', function () {
    $depot2 = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->technicien)->post(route('depot.switch'), [
        'depot_id' => $depot2->id,
    ]);

    $response->assertForbidden();
});
