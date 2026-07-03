<?php

use App\Models\Categorie;
use App\Models\Depot;
use App\Models\Part;
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

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des catégories', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.categories.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Stock/Categories/Index'));
});

test('un technicien sans permission stock ne peut pas voir les catégories', function () {
    $response = $this->actingAs($this->technicien)->get(route('stock.categories.index'));

    $response->assertForbidden();
});

// ── Création ───────────────────────────────────────────────────────────────

test('un admin peut créer une catégorie', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.categories.store'), [
        'name' => 'Écrans LCD',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('categories', [
        'shop_id' => $this->shop->id,
        'name' => 'Écrans LCD',
    ]);
});

test('la création sans nom est rejetée', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.categories.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('un technicien ne peut pas créer une catégorie', function () {
    $response = $this->actingAs($this->technicien)->post(route('stock.categories.store'), [
        'name' => 'Tentative',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('categories', ['name' => 'Tentative']);
});

// ── Modification ───────────────────────────────────────────────────────────

test('un admin peut modifier une catégorie', function () {
    $categorie = Categorie::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Ancien Nom']);

    $response = $this->actingAs($this->admin)->put(route('stock.categories.update', $categorie), [
        'name' => 'Nouveau Nom',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    expect($categorie->fresh()->name)->toBe('Nouveau Nom');
});

test('un technicien ne peut pas modifier une catégorie', function () {
    $categorie = Categorie::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->technicien)->put(route('stock.categories.update', $categorie), [
        'name' => 'Tentative',
    ]);

    $response->assertForbidden();
});

// ── Suppression ────────────────────────────────────────────────────────────

test('un admin peut supprimer une catégorie sans articles', function () {
    $categorie = Categorie::factory()->create(['shop_id' => $this->shop->id, 'name' => 'À supprimer']);

    $response = $this->actingAs($this->admin)->delete(route('stock.categories.destroy', $categorie));

    $response->assertRedirect();
    $this->assertDatabaseMissing('categories', ['id' => $categorie->id]);
});

test('une catégorie avec articles est désactivée plutôt que supprimée', function () {
    $categorie = Categorie::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
    Part::factory()->create(['shop_id' => $this->shop->id, 'category_id' => $categorie->id]);

    $response = $this->actingAs($this->admin)->delete(route('stock.categories.destroy', $categorie));

    $response->assertRedirect();
    $this->assertDatabaseHas('categories', ['id' => $categorie->id, 'is_active' => false]);
});

test('un technicien ne peut pas supprimer une catégorie', function () {
    $categorie = Categorie::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->technicien)->delete(route('stock.categories.destroy', $categorie));

    $response->assertForbidden();
    $this->assertDatabaseHas('categories', ['id' => $categorie->id]);
});

// ── Isolation multi-tenant ─────────────────────────────────────────────────

test("les catégories d'un autre atelier ne sont pas accessibles", function () {
    $autreShop = Shop::factory()->create();

    // Désactiver le current_shop pour que HasShopScope::creating ne l'override pas
    app()->forgetInstance('current_shop');
    $categorieAutre = Categorie::factory()->create(['shop_id' => $autreShop->id, 'name' => 'Catégorie Étrangère']);
    app()->instance('current_shop', $this->shop);

    $response = $this->actingAs($this->admin)->put(route('stock.categories.update', $categorieAutre), [
        'name' => 'Intrusion',
    ]);

    $response->assertNotFound();
    $this->assertDatabaseMissing('categories', ['name' => 'Intrusion']);
});
