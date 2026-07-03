<?php

use App\Enums\PurchaseStatus;
use App\Models\Depot;
use App\Models\Part;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\Supplier;
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

    $this->supplier = Supplier::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);

    $this->part = Part::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
        'unit_price' => 30.00,
        'sell_price' => 60.00,
    ]);

    app()->instance('current_shop', $this->shop);
    app()->instance('current_depot', $this->depot);
});

// ── Accès ──────────────────────────────────────────────────────────────────

test('un admin peut voir la liste des achats', function () {
    $response = $this->actingAs($this->admin)->get(route('purchases.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Purchases/Index'));
});

test('un technicien ne peut pas voir la liste des achats', function () {
    $response = $this->actingAs($this->technicien)->get(route('purchases.index'));

    $response->assertForbidden();
});

// ── Création ───────────────────────────────────────────────────────────────

test('un admin peut créer un achat avec un fournisseur et des lignes', function () {
    $response = $this->actingAs($this->admin)->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'tax_rate' => 20,
        'lines' => [
            [
                'part_id' => $this->part->id,
                'label' => $this->part->name,
                'quantity' => 10,
                'unit_price' => 30.00,
                'alert_quantity' => 2,
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('purchases', [
        'shop_id' => $this->shop->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseStatus::Draft->value,
    ]);
});

test('un numéro d\'achat unique ACH-YYYY-NNNNN est généré automatiquement', function () {
    $this->actingAs($this->admin)->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'tax_rate' => 20,
        'lines' => [
            [
                'part_id' => $this->part->id,
                'label' => $this->part->name,
                'quantity' => 5,
                'unit_price' => 30.00,
            ],
        ],
    ]);

    $purchase = Purchase::withoutGlobalScopes()
        ->where('shop_id', $this->shop->id)
        ->latest()
        ->first();

    expect($purchase->number)->toMatch('/^ACH-\d{4}-\d{5}$/');
});

test('la création sans ligne est rejetée', function () {
    $response = $this->actingAs($this->admin)->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'tax_rate' => 20,
        'lines' => [],
    ]);

    $response->assertSessionHasErrors('lines');
});

test('un technicien ne peut pas créer un achat', function () {
    $response = $this->actingAs($this->technicien)->post(route('purchases.store'), [
        'supplier_id' => $this->supplier->id,
        'tax_rate' => 20,
        'lines' => [
            [
                'part_id' => $this->part->id,
                'label' => $this->part->name,
                'quantity' => 1,
                'unit_price' => 30.00,
            ],
        ],
    ]);

    $response->assertForbidden();
});

// ── Transitions de statut ──────────────────────────────────────────────────

test('la réception d\'un achat incrémente le stock', function () {
    Notification::fake();

    $purchase = Purchase::withoutGlobalScopes()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseStatus::Draft,
        'tax_rate' => 20,
        'ordered_at' => now(),
    ]);

    PurchaseLine::create([
        'purchase_id' => $purchase->id,
        'part_id' => $this->part->id,
        'label' => $this->part->name,
        'quantity' => 8,
        'unit_price' => 30.00,
        'alert_quantity' => 2,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('purchases.transition', $purchase), [
            'status' => PurchaseStatus::Received->value,
        ]);

    $response->assertRedirect();
    expect($purchase->fresh()->status)->toBe(PurchaseStatus::Received);
    expect($purchase->fresh()->received_at)->not->toBeNull();

    $stock = StockDepot::withoutGlobalScopes()
        ->where('part_id', $this->part->id)
        ->where('depot_id', $this->depot->id)
        ->first();
    expect($stock)->not->toBeNull();
    expect($stock->quantity)->toBe(8);
});

test('un achat reçu peut être marqué comme payé', function () {
    $purchase = Purchase::withoutGlobalScopes()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseStatus::Received,
        'tax_rate' => 20,
        'ordered_at' => now(),
        'received_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('purchases.transition', $purchase), [
            'status' => PurchaseStatus::Paid->value,
        ]);

    $response->assertRedirect();
    expect($purchase->fresh()->status)->toBe(PurchaseStatus::Paid);
    expect($purchase->fresh()->paid_at)->not->toBeNull();
});

test('une transition invalide retourne une erreur sans changer le statut', function () {
    $purchase = Purchase::withoutGlobalScopes()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseStatus::Received,
        'tax_rate' => 20,
        'ordered_at' => now(),
    ]);

    // Received → Draft est une transition invalide
    $response = $this->actingAs($this->admin)
        ->post(route('purchases.transition', $purchase), [
            'status' => PurchaseStatus::Draft->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($purchase->fresh()->status)->toBe(PurchaseStatus::Received);
});

test('un technicien ne peut pas réceptionner un achat', function () {
    $purchase = Purchase::withoutGlobalScopes()->create([
        'shop_id' => $this->shop->id,
        'depot_id' => $this->depot->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseStatus::Draft,
        'tax_rate' => 20,
        'ordered_at' => now(),
    ]);

    $response = $this->actingAs($this->technicien)
        ->post(route('purchases.transition', $purchase), [
            'status' => PurchaseStatus::Received->value,
        ]);

    $response->assertForbidden();
});
