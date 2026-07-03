<?php

use App\Models\Categorie;
use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);
    $this->depot2 = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $this->technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $this->categorie = Categorie::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);

    $this->supplier = Supplier::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);

    app()->instance('current_shop', $this->shop);
    app()->instance('current_depot', $this->depot);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des pièces', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.parts.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Stock/Parts/Index'));
});

test('un technicien ne peut pas voir la liste des pièces', function () {
    $response = $this->actingAs($this->technicien)->get(route('stock.parts.index'));

    $response->assertForbidden();
});

test('un admin peut voir la liste des mouvements', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.movements.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Stock/Movement/Index'));
});

// ── Pièces (CRUD) ──────────────────────────────────────────────────────────

test('un admin peut créer une pièce', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.parts.store'), [
        'name' => 'Écran iPhone 13 OLED',
        'sku' => 'SCR-IP13-001',
        'category_id' => $this->categorie->id,
        'supplier_id' => $this->supplier->id,
        'unit_price' => 45.00,
        'sell_price' => 89.00,
    ]);

    $response->assertRedirect(route('stock.parts.index'));
    $this->assertDatabaseHas('parts', [
        'shop_id' => $this->shop->id,
        'name' => 'Écran iPhone 13 OLED',
        'sku' => 'SCR-IP13-001',
    ]);
});

test('un admin peut modifier une pièce', function () {
    $part = Part::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->put(route('stock.parts.update', $part), [
        'name' => 'Nom modifié',
        'sell_price' => 99.00,
    ]);

    $response->assertRedirect();
    expect($part->fresh()->name)->toBe('Nom modifié');
    expect((float) $part->fresh()->sell_price)->toBe(99.0);
});

test('un admin peut supprimer une pièce sans mouvements', function () {
    $part = Part::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->admin)->delete(route('stock.parts.destroy', $part));

    $response->assertRedirect();
    $this->assertDatabaseMissing('parts', ['id' => $part->id]);
});

test('une pièce avec mouvements est désactivée plutôt que supprimée', function () {
    $part = Part::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);

    $stock = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 10,
    ]);

    StockMovement::create([
        'depot_id' => $this->depot->id,
        'stock_id' => $stock->id,
        'user_id' => $this->admin->id,
        'type' => 'in',
        'quantity' => 10,
        'unit_cost' => 20.00,
        'note' => 'test',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('stock.parts.destroy', $part));

    $response->assertRedirect();
    $this->assertDatabaseHas('parts', ['id' => $part->id, 'is_active' => false]);
});

test('un technicien ne peut pas créer une pièce', function () {
    $response = $this->actingAs($this->technicien)->post(route('stock.parts.store'), [
        'name' => 'Pièce test',
        'unit_price' => 10,
        'sell_price' => 20,
    ]);

    $response->assertForbidden();
});

test('le SKU doit être unique par atelier', function () {
    Part::factory()->create([
        'shop_id' => $this->shop->id,
        'sku' => 'SKU-UNIQUE',
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.parts.store'), [
        'name' => 'Autre pièce',
        'sku' => 'SKU-UNIQUE',
    ]);

    $response->assertSessionHasErrors('sku');
});

// ── Mouvements de stock ─────────────────────────────────────────────────────

test('un admin peut réapprovisionner une pièce (type: in)', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 5,
        'alert_quantity' => 0,
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.movements.store'), [
        'part_id' => $part->id,
        'depot_id' => $this->depot->id,
        'type' => 'in',
        'quantity' => 10,
        'note' => 'Réassort test',
    ]);

    $response->assertRedirect();
    $stock = StockDepot::where('part_id', $part->id)->where('depot_id', $this->depot->id)->first();
    expect($stock->quantity)->toBe(15);
});

test('un admin peut enregistrer une sortie de stock (type: out)', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 20,
        'alert_quantity' => 0,
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.movements.store'), [
        'part_id' => $part->id,
        'depot_id' => $this->depot->id,
        'type' => 'out',
        'quantity' => 5,
    ]);

    $response->assertRedirect();
    $stock = StockDepot::where('part_id', $part->id)->where('depot_id', $this->depot->id)->first();
    expect($stock->quantity)->toBe(15);
});

test('une sortie avec stock insuffisant retourne une erreur', function () {
    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 2,
        'alert_quantity' => 0,
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.movements.store'), [
        'part_id' => $part->id,
        'depot_id' => $this->depot->id,
        'type' => 'out',
        'quantity' => 10,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $stock = StockDepot::where('part_id', $part->id)->first();
    expect($stock->quantity)->toBe(2);
});

test('un admin peut faire un ajustement de stock', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 10,
        'alert_quantity' => 0,
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.movements.store'), [
        'part_id' => $part->id,
        'depot_id' => $this->depot->id,
        'type' => 'adjustment',
        'quantity' => 3,
        'note' => 'Inventaire physique',
    ]);

    $response->assertRedirect();

    // L'ajustement fixe la quantité à 3 (stock initial: 10, diff: -7)
    $stock = StockDepot::where('part_id', $part->id)->where('depot_id', $this->depot->id)->first();
    expect($stock->quantity)->toBe(3);
    $this->assertDatabaseHas('stock_movements', ['type' => 'adjustment']);
});

test('un admin peut transférer du stock entre dépôts', function () {
    Notification::fake();

    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    $source = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 10,
        'alert_quantity' => 0,
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.movements.transfer'), [
        'part_id' => $part->id,
        'stock_id' => $source->id,
        'from_depot_id' => $this->depot->id,
        'to_depot_id' => $this->depot2->id,
        'quantity' => 4,
    ]);

    $response->assertRedirect();
    expect($source->fresh()->quantity)->toBe(6);

    $destination = StockDepot::withoutGlobalScopes()
        ->where('part_id', $part->id)
        ->where('depot_id', $this->depot2->id)
        ->first();
    expect($destination)->not->toBeNull();
    expect($destination->quantity)->toBe(4);
});

// ── Alertes de stock ────────────────────────────────────────────────────────

test('la page des alertes liste les stocks sous le seuil critique', function () {
    $part = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $part->id,
        'quantity' => 1,
        'alert_quantity' => 5,
    ]);

    $response = $this->actingAs($this->admin)->get(route('stock.alerts'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Stock/Alerts')
        ->has('alerts')
    );
});
