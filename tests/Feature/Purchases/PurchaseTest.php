<?php

use App\Models\Depot;
use App\Models\Part;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);
    $this->supplier = Supplier::factory()->create(['shop_id' => $this->shop->id]);
    $this->part = Part::factory()->create(['shop_id' => $this->shop->id, 'unit_price' => 10]);

    $this->admin = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $this->admin->assignRole('admin');
});

function purchasePayload(array $overrides = []): array
{
    return array_merge([
        'supplier_id' => null,
        'tax_rate' => 0,
        'lines' => [],
    ], $overrides);
}

test('créer un achat ne modifie pas le stock', function () {
    $response = $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 5, 'unit_price' => 12],
        ],
    ]));

    $purchase = Purchase::latest('id')->first();
    $response->assertRedirect(route('purchases.show', $purchase->id));

    expect($purchase->status->value)->toBe('draft');
    expect(StockDepot::where('part_id', $this->part->id)->where('depot_id', $this->depot->id)->exists())->toBeFalse();
});

test('marquer un achat comme reçu incrémente le stock et trace le mouvement', function () {
    $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 5, 'unit_price' => 12],
        ],
    ]));

    $purchase = Purchase::latest('id')->first();

    $response = $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), [
        'status' => 'received',
    ]);

    $response->assertSessionHas('success');

    $stock = StockDepot::where('part_id', $this->part->id)->where('depot_id', $this->depot->id)->first();
    expect($stock)->not->toBeNull();
    expect($stock->quantity)->toBe(5);

    $movement = StockMovement::where('purchase_id', $purchase->id)->where('type', 'in')->first();
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(5);

    expect($this->part->refresh()->unit_price)->toBe('12.00');
    expect($purchase->refresh()->status->value)->toBe('received');
});

test('le seuil d\'alerte précisé à l\'achat est appliqué au stock créé à la réception', function () {
    $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 5, 'unit_price' => 12, 'alert_quantity' => 8],
        ],
    ]));
    $purchase = Purchase::latest('id')->first();

    $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), ['status' => 'received']);

    $stock = StockDepot::where('part_id', $this->part->id)->where('depot_id', $this->depot->id)->first();
    expect($stock->alert_quantity)->toBe(8);
});

test('un nouveau seuil d\'alerte précisé à un second achat met à jour le stock existant', function () {
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'part_id' => $this->part->id,
        'depot_id' => $this->depot->id,
        'quantity' => 2,
        'alert_quantity' => 3,
    ]);

    $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 5, 'unit_price' => 12, 'alert_quantity' => 15],
        ],
    ]));
    $purchase = Purchase::latest('id')->first();

    $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), ['status' => 'received']);

    $stock = StockDepot::where('part_id', $this->part->id)->where('depot_id', $this->depot->id)->first();
    expect($stock->alert_quantity)->toBe(15);
    expect($stock->quantity)->toBe(7);
});

test('marquer un achat reçu comme payé ne modifie pas le stock', function () {
    $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 3, 'unit_price' => 10],
        ],
    ]));
    $purchase = Purchase::latest('id')->first();

    $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), ['status' => 'received']);

    $stockBefore = StockDepot::where('part_id', $this->part->id)->first()->quantity;

    $response = $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), ['status' => 'paid']);

    $response->assertSessionHas('success');
    expect($purchase->refresh()->status->value)->toBe('paid');
    expect($purchase->paid_at)->not->toBeNull();
    expect(StockDepot::where('part_id', $this->part->id)->first()->quantity)->toBe($stockBefore);
});

test('un achat reçu ne peut pas être annulé', function () {
    $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 2, 'unit_price' => 10],
        ],
    ]));
    $purchase = Purchase::latest('id')->first();

    $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), ['status' => 'received']);

    $response = $this->actingAs($this->admin)->post(route('purchases.transition', $purchase->id), ['status' => 'cancelled']);

    $response->assertSessionHas('error');
    expect($purchase->refresh()->status->value)->toBe('received');
});

test('un utilisateur sans la permission purchases.create ne peut pas créer un achat', function () {
    $user = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);

    $response = $this->actingAs($user)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]));

    $response->assertForbidden();
    expect(Purchase::count())->toBe(0);
});

test('un utilisateur sans la permission purchases.receive ne peut pas marquer un achat reçu', function () {
    $this->actingAs($this->admin)->post(route('purchases.store'), purchasePayload([
        'supplier_id' => $this->supplier->id,
        'lines' => [
            ['part_id' => $this->part->id, 'label' => $this->part->name, 'quantity' => 1, 'unit_price' => 10],
        ],
    ]));
    $purchase = Purchase::latest('id')->first();

    $user = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $user->givePermissionTo('purchases.create');

    $response = $this->actingAs($user)->post(route('purchases.transition', $purchase->id), ['status' => 'received']);

    $response->assertForbidden();
    expect($purchase->refresh()->status->value)->toBe('draft');
});
