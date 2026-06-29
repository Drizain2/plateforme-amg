<?php

use App\Models\Categorie;
use App\Models\Depot;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);
    $this->category = Categorie::factory()->create(['shop_id' => $this->shop->id]);
});

test('un manager avec la permission stock.create peut créer une pièce', function () {
    $manager = User::factory()->create(['shop_id' => $this->shop->id, 'depot_active_id' => $this->depot->id]);
    $manager->assignRole('manager');

    $response = $this->actingAs($manager)->post(route('stock.parts.store'), [
        'name' => 'Écran iPhone 12',
        'category_id' => $this->category->id,
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect();
});

test('un manager avec la permission stock.create peut créer un fournisseur', function () {
    $manager = User::factory()->create(['shop_id' => $this->shop->id]);
    $manager->assignRole('manager');

    $response = $this->actingAs($manager)->post(route('stock.suppliers.store'), [
        'name' => 'Fournisseur Test',
    ]);

    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('suppliers', ['name' => 'Fournisseur Test', 'shop_id' => $this->shop->id]);
});

test('un super_admin peut créer et modifier un fournisseur', function () {
    $superAdmin = User::factory()->create(['shop_id' => $this->shop->id]);
    $superAdmin->assignRole('super_admin');

    $response = $this->actingAs($superAdmin)->post(route('stock.suppliers.store'), [
        'name' => 'Fournisseur SA',
    ]);
    $response->assertSessionDoesntHaveErrors();

    $supplier = Supplier::where('name', 'Fournisseur SA')->first();

    $response = $this->actingAs($superAdmin)->put(route('stock.suppliers.update', $supplier), [
        'name' => 'Fournisseur SA Modifié',
    ]);
    $response->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Fournisseur SA Modifié']);
});

test('une caissière sans permission stock ne peut pas supprimer un fournisseur', function () {
    $cashier = User::factory()->create(['shop_id' => $this->shop->id]);
    $cashier->assignRole('caissiere');

    $supplier = Supplier::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($cashier)->delete(route('stock.suppliers.destroy', $supplier));

    $response->assertForbidden();
    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
});

test('une caissière sans permission stock ne peut pas créer de catégorie', function () {
    $cashier = User::factory()->create(['shop_id' => $this->shop->id]);
    $cashier->assignRole('caissiere');

    $response = $this->actingAs($cashier)->post(route('stock.categories.store'), [
        'name' => 'Nouvelle catégorie',
    ]);

    $response->assertForbidden();
});
