<?php

use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockCount;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->partA = Part::factory()->create(['shop_id' => $this->shop->id]);
    $this->partB = Part::factory()->create(['shop_id' => $this->shop->id]);

    $this->stockA = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'part_id' => $this->partA->id,
        'depot_id' => $this->depot->id,
        'quantity' => 10,
        'avg_cost_price' => 100,
    ]);
    $this->stockB = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'part_id' => $this->partB->id,
        'depot_id' => $this->depot->id,
        'quantity' => 5,
        'avg_cost_price' => 50,
    ]);

    $this->admin = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $this->admin->assignRole('admin');
});

test('démarrer un inventaire crée une ligne par pièce du dépôt avec un instantané de la quantité et du CMP', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.counts.store'));

    $stockCount = StockCount::latest('id')->first();
    $response->assertRedirect(route('stock.counts.show', $stockCount->id));

    expect($stockCount->status->value)->toBe('draft');
    expect($stockCount->lines)->toHaveCount(2);

    $lineA = $stockCount->lines->firstWhere('stock_depot_id', $this->stockA->id);
    expect($lineA->expected_quantity)->toBe(10);
    expect((float) $lineA->unit_cost)->toBe(100.0);
    expect($lineA->counted_quantity)->toBeNull();
});

test('enregistrer un comptage met à jour les lignes sans toucher au stock', function () {
    $this->actingAs($this->admin)->post(route('stock.counts.store'));
    $stockCount = StockCount::latest('id')->first();
    $lineA = $stockCount->lines->firstWhere('stock_depot_id', $this->stockA->id);

    $response = $this->actingAs($this->admin)->put(route('stock.counts.update', $stockCount->id), [
        'lines' => [
            ['id' => $lineA->id, 'counted_quantity' => 8, 'note' => 'carton manquant'],
        ],
    ]);

    $response->assertSessionHas('success');
    expect($lineA->refresh()->counted_quantity)->toBe(8);
    expect($this->stockA->refresh()->quantity)->toBe(10);
});

test('valider un inventaire applique les écarts comptés en ajustement de stock', function () {
    $this->actingAs($this->admin)->post(route('stock.counts.store'));
    $stockCount = StockCount::latest('id')->first();
    $lineA = $stockCount->lines->firstWhere('stock_depot_id', $this->stockA->id);
    $lineB = $stockCount->lines->firstWhere('stock_depot_id', $this->stockB->id);

    $this->actingAs($this->admin)->put(route('stock.counts.update', $stockCount->id), [
        'lines' => [
            ['id' => $lineA->id, 'counted_quantity' => 8],
            ['id' => $lineB->id, 'counted_quantity' => 5],
        ],
    ]);

    $response = $this->actingAs($this->admin)->post(route('stock.counts.validate', $stockCount->id));

    $response->assertSessionHas('success');
    expect($stockCount->refresh()->status->value)->toBe('validated');

    // Écart sur A (10 -> 8) : ajustement appliqué
    expect($this->stockA->refresh()->quantity)->toBe(8);
    $movement = StockMovement::where('stock_id', $this->stockA->id)->where('type', 'adjustment')->first();
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(2);

    // Pas d'écart sur B (5 -> 5) : aucun mouvement créé
    expect($this->stockB->refresh()->quantity)->toBe(5);
    expect(StockMovement::where('stock_id', $this->stockB->id)->where('type', 'adjustment')->exists())->toBeFalse();
});

test('valider un inventaire ignore les lignes non comptées', function () {
    $this->actingAs($this->admin)->post(route('stock.counts.store'));
    $stockCount = StockCount::latest('id')->first();

    $response = $this->actingAs($this->admin)->post(route('stock.counts.validate', $stockCount->id));

    $response->assertSessionHas('success');
    expect($this->stockA->refresh()->quantity)->toBe(10);
    expect($this->stockB->refresh()->quantity)->toBe(5);
    expect(StockMovement::where('type', 'adjustment')->exists())->toBeFalse();
});

test('un inventaire déjà validé ne peut pas être validé une seconde fois', function () {
    $this->actingAs($this->admin)->post(route('stock.counts.store'));
    $stockCount = StockCount::latest('id')->first();

    $this->actingAs($this->admin)->post(route('stock.counts.validate', $stockCount->id));

    $response = $this->actingAs($this->admin)->post(route('stock.counts.validate', $stockCount->id));

    $response->assertSessionHas('error');
});

test('un utilisateur sans la permission stock.count ne peut pas démarrer un inventaire', function () {
    $user = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($user)->post(route('stock.counts.store'));

    $response->assertForbidden();
    expect(StockCount::count())->toBe(0);
});
