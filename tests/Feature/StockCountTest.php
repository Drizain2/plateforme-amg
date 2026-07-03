<?php

use App\Enums\StockCountStatus;
use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockDepot;
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

    $this->technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    // Pièce en stock pour les tests d'inventaire
    $this->part = Part::factory()->create(['shop_id' => $this->shop->id]);
    $this->stock = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'part_id' => $this->part->id,
        'quantity' => 10,
        'alert_quantity' => 0,
    ]);

    app()->instance('current_shop', $this->shop);
    app()->instance('current_depot', $this->depot);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des inventaires', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.counts.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Stock/Counts/Index'));
});

test('un technicien ne peut pas démarrer un inventaire', function () {
    $response = $this->actingAs($this->technicien)->post(route('stock.counts.store'), []);

    $response->assertForbidden();
});

// ── Démarrage ──────────────────────────────────────────────────────────────

test('un admin peut démarrer un inventaire pour le dépôt actif', function () {
    $response = $this->actingAs($this->admin)->post(route('stock.counts.store'), [
        'note' => 'Inventaire mensuel',
    ]);

    $response->assertRedirect();

    $count = StockCount::withoutGlobalScopes()->latest()->first();
    expect($count)->not->toBeNull();
    expect($count->depot_id)->toBe($this->depot->id);
    expect($count->status)->toBe(StockCountStatus::Draft);

    // Les lignes sont initialisées avec la quantité attendue actuelle
    expect($count->lines)->toHaveCount(1);
    expect($count->lines->first()->expected_quantity)->toBe(10);
});

// ── Saisie des quantités ────────────────────────────────────────────────────

test('un admin peut saisir les quantités comptées', function () {
    $stockCount = StockCount::withoutGlobalScopes()->create([
        'depot_id' => $this->depot->id,
        'user_id' => $this->admin->id,
        'status' => StockCountStatus::Draft,
        'started_at' => now(),
    ]);

    $line = StockCountLine::create([
        'stock_count_id' => $stockCount->id,
        'stock_depot_id' => $this->stock->id,
        'expected_quantity' => 10,
        'unit_cost' => 20.00,
    ]);

    $response = $this->actingAs($this->admin)->put(route('stock.counts.update', $stockCount), [
        'lines' => [
            ['id' => $line->id, 'counted_quantity' => 8, 'note' => 'Deux unités manquantes'],
        ],
    ]);

    $response->assertRedirect();
    expect($line->fresh()->counted_quantity)->toBe(8);
    expect($line->fresh()->note)->toBe('Deux unités manquantes');
});

// ── Validation ─────────────────────────────────────────────────────────────

test('la validation d\'un inventaire ajuste le stock aux quantités comptées', function () {
    Notification::fake();

    $stockCount = StockCount::withoutGlobalScopes()->create([
        'depot_id' => $this->depot->id,
        'user_id' => $this->admin->id,
        'status' => StockCountStatus::Draft,
        'started_at' => now(),
    ]);

    StockCountLine::create([
        'stock_count_id' => $stockCount->id,
        'stock_depot_id' => $this->stock->id,
        'expected_quantity' => 10,
        'counted_quantity' => 7,
        'unit_cost' => 20.00,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('stock.counts.validate', $stockCount));

    $response->assertRedirect();
    expect($stockCount->fresh()->status)->toBe(StockCountStatus::Validated);
    expect($stockCount->fresh()->validated_at)->not->toBeNull();
    expect($this->stock->fresh()->quantity)->toBe(7);
});

test('valider un inventaire déjà validé retourne une erreur', function () {
    $stockCount = StockCount::withoutGlobalScopes()->create([
        'depot_id' => $this->depot->id,
        'user_id' => $this->admin->id,
        'status' => StockCountStatus::Validated,
        'started_at' => now(),
        'validated_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('stock.counts.validate', $stockCount));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('les lignes non comptées sont ignorées lors de la validation', function () {
    Notification::fake();

    $stockCount = StockCount::withoutGlobalScopes()->create([
        'depot_id' => $this->depot->id,
        'user_id' => $this->admin->id,
        'status' => StockCountStatus::Draft,
        'started_at' => now(),
    ]);

    // counted_quantity est null : ligne non comptée, le stock ne doit pas changer
    StockCountLine::create([
        'stock_count_id' => $stockCount->id,
        'stock_depot_id' => $this->stock->id,
        'expected_quantity' => 10,
        'counted_quantity' => null,
        'unit_cost' => 20.00,
    ]);

    $this->actingAs($this->admin)->post(route('stock.counts.validate', $stockCount));

    expect($this->stock->fresh()->quantity)->toBe(10);
});
