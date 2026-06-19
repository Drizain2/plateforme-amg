<?php

use App\Models\Customer;
use App\Models\Depot;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->part = Part::factory()->create(['shop_id' => $this->shop->id, 'sell_price' => 50]);
    $this->stock = StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'part_id' => $this->part->id,
        'depot_id' => $this->depot->id,
        'quantity' => 10,
        'alert_quantity' => 2,
    ]);

    $this->customer = Customer::create(['shop_id' => $this->shop->id, 'name' => 'Jean Client']);

    $this->admin = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $this->admin->assignRole('admin');
});

function invoiceStockPayload(array $overrides = []): array
{
    return array_merge([
        'customer_id' => null,
        'tax_rate' => 20,
        'lines' => [],
    ], $overrides);
}

test('facturer une ligne liée à une pièce décrémente le stock et trace le mouvement', function () {
    $response = $this->actingAs($this->admin)->post(route('invoices.store'), invoiceStockPayload([
        'customer_id' => $this->customer->id,
        'lines' => [
            ['type' => 'part', 'label' => $this->part->name, 'quantity' => 3, 'unit_price' => 50, 'part_id' => $this->part->id],
        ],
    ]));

    $invoice = Invoice::latest('id')->first();

    $response->assertRedirect(route('invoices.show', $invoice->id));

    expect($this->stock->refresh()->quantity)->toBe(7);

    $movement = StockMovement::where('invoice_id', $invoice->id)->where('type', 'out')->first();
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(3);
    expect($movement->stock_id)->toBe($this->stock->id);
});

test('un stock insuffisant empêche la création de la facture', function () {
    $response = $this->actingAs($this->admin)->post(route('invoices.store'), invoiceStockPayload([
        'customer_id' => $this->customer->id,
        'lines' => [
            ['type' => 'part', 'label' => $this->part->name, 'quantity' => 50, 'unit_price' => 50, 'part_id' => $this->part->id],
        ],
    ]));

    $response->assertSessionHas('error');
    expect(Invoice::count())->toBe(0);
    expect($this->stock->refresh()->quantity)->toBe(10);
});

test('annuler une facture issue du stock restocke les pièces vendues', function () {
    $this->actingAs($this->admin)->post(route('invoices.store'), invoiceStockPayload([
        'customer_id' => $this->customer->id,
        'lines' => [
            ['type' => 'part', 'label' => $this->part->name, 'quantity' => 4, 'unit_price' => 50, 'part_id' => $this->part->id],
        ],
    ]));

    $invoice = Invoice::latest('id')->first();
    expect($this->stock->refresh()->quantity)->toBe(6);

    $response = $this->actingAs($this->admin)->post(route('invoices.transition', $invoice->id), [
        'status' => 'cancelled',
    ]);

    $response->assertSessionHas('success');
    expect($this->stock->refresh()->quantity)->toBe(10);

    $inMovement = StockMovement::where('invoice_id', $invoice->id)->where('type', 'in')->first();
    expect($inMovement)->not->toBeNull();
    expect($inMovement->quantity)->toBe(4);
});

test('créer une facture sans client existant crée un nouveau client à la volée', function () {
    $response = $this->actingAs($this->admin)->post(route('invoices.store'), invoiceStockPayload([
        'customer_name' => 'Client comptoir',
        'customer_phone' => '0102030405',
        'lines' => [
            ['type' => 'service', 'label' => 'Main d\'œuvre', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]));

    $invoice = Invoice::latest('id')->first();
    $response->assertRedirect(route('invoices.show', $invoice->id));

    expect(Customer::where('name', 'Client comptoir')->exists())->toBeTrue();
    expect($invoice->customer->name)->toBe('Client comptoir');
});

test('un utilisateur sans la permission customers.create ne peut pas créer un client à la volée', function () {
    $user = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
    ]);
    $user->givePermissionTo('invoices.create');

    $response = $this->actingAs($user)->post(route('invoices.store'), invoiceStockPayload([
        'customer_name' => 'Client comptoir',
        'lines' => [
            ['type' => 'service', 'label' => 'Main d\'œuvre', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]));

    $response->assertForbidden();
    expect(Invoice::count())->toBe(0);
});
