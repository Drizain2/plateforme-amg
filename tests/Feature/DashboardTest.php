<?php

use App\Enums\PurchaseStatus;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Device;
use App\Models\Part;
use App\Models\Plan;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeTicket(Shop $shop, Depot $depot, array $overrides = []): Ticket
{
    $customer = Customer::create(['shop_id' => $shop->id, 'name' => 'Client Test']);
    $device = Device::create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'type' => 'smartphone',
        'brand' => 'Samsung',
        'model' => 'Galaxy',
    ]);

    return Ticket::create(array_merge([
        'shop_id' => $shop->id,
        'depot_id' => $depot->id,
        'customer_id' => $customer->id,
        'device_id' => $device->id,
        'description' => 'Écran cassé',
    ], $overrides));
}

test('un admin sur un plan complet voit tickets, stock, revenu et achats', function () {
    $shop = Shop::factory()->create();
    $depot = Depot::factory()->create(['shop_id' => $shop->id]);
    $admin = User::factory()->create(['shop_id' => $shop->id, 'depot_active_id' => $depot->id]);
    $admin->assignRole('admin');

    makeTicket($shop, $depot);
    $part = Part::factory()->create(['shop_id' => $shop->id]);
    StockDepot::factory()->low()->create(['shop_id' => $shop->id, 'depot_id' => $depot->id, 'part_id' => $part->id]);

    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    Purchase::create([
        'shop_id' => $shop->id,
        'depot_id' => $depot->id,
        'supplier_id' => $supplier->id,
        'status' => PurchaseStatus::Draft->value,
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->has('stats.tickets_open')
        ->has('stats.low_stock_count')
        ->has('stats.revenue_month')
        ->has('stats.purchases_pending_count')
        ->has('charts.tickets_by_day')
        ->has('recent.tickets')
        ->has('recent.low_stock')
    );
});

test('un technicien ne voit que les données tickets, ni stock ni revenu ni achats', function () {
    $shop = Shop::factory()->create();
    $depot = Depot::factory()->create(['shop_id' => $shop->id]);
    $technicien = User::factory()->create(['shop_id' => $shop->id, 'depot_active_id' => $depot->id]);
    $technicien->assignRole('technicien');

    makeTicket($shop, $depot);
    $part = Part::factory()->create(['shop_id' => $shop->id]);
    StockDepot::factory()->low()->create(['shop_id' => $shop->id, 'depot_id' => $depot->id, 'part_id' => $part->id]);

    $response = $this->actingAs($technicien)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->has('stats.tickets_open')
        ->missing('stats.low_stock_count')
        ->missing('stats.revenue_month')
        ->missing('stats.purchases_pending_count')
        ->missing('recent.low_stock')
        ->has('charts.tickets_by_day')
    );
});

test('une caissière voit le stock en lecture mais ni revenu ni achats', function () {
    $shop = Shop::factory()->create();
    $depot = Depot::factory()->create(['shop_id' => $shop->id]);
    $caissiere = User::factory()->create(['shop_id' => $shop->id, 'depot_active_id' => $depot->id]);
    $caissiere->assignRole('caissiere');

    $response = $this->actingAs($caissiere)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('stats.low_stock_count')
        ->missing('stats.revenue_month')
        ->missing('stats.purchases_pending_count')
        ->has('recent.low_stock')
    );
});

test('un admin sur un plan stock-only ne voit aucune donnée ticket et voit les widgets de substitution stock', function () {
    $plan = Plan::factory()->create(['disabled_modules' => ['tickets']]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $depot = Depot::factory()->create(['shop_id' => $shop->id]);
    $admin = User::factory()->create(['shop_id' => $shop->id, 'depot_active_id' => $depot->id]);
    $admin->assignRole('admin');

    StockMovement::factory()->create(['shop_id' => $shop->id, 'depot_id' => $depot->id]);
    $part = Part::factory()->create(['shop_id' => $shop->id]);
    StockDepot::factory()->create(['shop_id' => $shop->id, 'depot_id' => $depot->id, 'part_id' => $part->id, 'quantity' => 10]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->missing('stats.tickets_open')
        ->missing('charts.tickets_by_day')
        ->missing('recent.tickets')
        ->missing('alerts.overdue')
        ->has('charts.stock_movements_by_day')
        ->has('charts.stock_by_depot')
        ->has('stats.low_stock_count')
    );
});
