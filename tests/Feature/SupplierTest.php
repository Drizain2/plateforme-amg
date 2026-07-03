<?php

use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\Supplier;
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

test('un admin peut voir la liste des fournisseurs', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.suppliers.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Stock/Suppliers/Index'));
});

test('un technicien sans permission stock ne peut pas voir les fournisseurs', function () {
    $response = $this->actingAs($this->technicien)->get(route('stock.suppliers.index'));

    $response->assertForbidden();
});

// ── Création ───────────────────────────────────────────────────────────────

test('un admin peut créer un fournisseur', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.suppliers.store'), [
        'name' => 'GSM Partner Dakar',
        'email' => 'contact@gsmpartner.sn',
        'phone' => '+221 77 000 0000',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('suppliers', [
        'shop_id' => $this->shop->id,
        'name' => 'GSM Partner Dakar',
    ]);
});

test('la création sans nom est rejetée', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.suppliers.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('un technicien ne peut pas créer un fournisseur', function () {
    $response = $this->actingAs($this->technicien)->post(route('stock.suppliers.store'), [
        'name' => 'Tentative',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('suppliers', ['name' => 'Tentative']);
});

// ── Modification ───────────────────────────────────────────────────────────

test('un admin peut modifier un fournisseur', function () {
    $supplier = Supplier::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->put(route('stock.suppliers.update', $supplier), [
        'name' => 'Fournisseur Modifié',
        'email' => 'nouveau@example.com',
    ]);

    $response->assertRedirect();
    expect($supplier->fresh()->name)->toBe('Fournisseur Modifié');
});

test('un technicien ne peut pas modifier un fournisseur', function () {
    $supplier = Supplier::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->technicien)->put(route('stock.suppliers.update', $supplier), [
        'name' => 'Tentative',
    ]);

    $response->assertForbidden();
});

// ── Suppression ────────────────────────────────────────────────────────────

test('un admin peut supprimer un fournisseur sans articles', function () {
    $supplier = Supplier::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->delete(route('stock.suppliers.destroy', $supplier));

    $response->assertRedirect();
    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
});

test('un fournisseur avec articles est désactivé plutôt que supprimé', function () {
    $supplier = Supplier::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
    Part::factory()->create(['shop_id' => $this->shop->id, 'supplier_id' => $supplier->id]);

    $response = $this->actingAs($this->admin)->delete(route('stock.suppliers.destroy', $supplier));

    $response->assertRedirect();
    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);
});

test('un technicien ne peut pas supprimer un fournisseur', function () {
    $supplier = Supplier::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->technicien)->delete(route('stock.suppliers.destroy', $supplier));

    $response->assertForbidden();
    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
});

// ── Isolation multi-tenant ─────────────────────────────────────────────────

test("les fournisseurs d'un autre atelier ne sont pas accessibles", function () {
    $autreShop = Shop::factory()->create();

    // Désactiver le current_shop pour que HasShopScope::creating ne l'override pas
    app()->forgetInstance('current_shop');
    $supplierAutre = Supplier::factory()->create(['shop_id' => $autreShop->id]);
    app()->instance('current_shop', $this->shop);

    $response = $this->actingAs($this->admin)->put(route('stock.suppliers.update', $supplierAutre), [
        'name' => 'Intrusion',
    ]);

    $response->assertNotFound();
    $this->assertDatabaseMissing('suppliers', ['name' => 'Intrusion']);
});
