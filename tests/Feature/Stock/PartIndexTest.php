<?php

use App\Models\Depot;
use App\Models\Part;
use App\Models\Shop;
use App\Models\StockDepot;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depotA = Depot::factory()->create(['shop_id' => $this->shop->id]);
    $this->depotB = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->user = User::factory()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depotA->id,
    ]);
    $this->user->assignRole('admin');
});

test('seules les pièces ayant du stock dans le dépôt actif apparaissent', function () {
    $partInDepotA = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'part_id' => $partInDepotA->id,
        'depot_id' => $this->depotA->id,
        'quantity' => 5,
    ]);

    $partInDepotB = Part::factory()->create(['shop_id' => $this->shop->id]);
    StockDepot::factory()->create([
        'shop_id' => $this->shop->id,
        'part_id' => $partInDepotB->id,
        'depot_id' => $this->depotB->id,
        'quantity' => 5,
    ]);

    $partWithoutStock = Part::factory()->create(['shop_id' => $this->shop->id]);

    $response = $this->actingAs($this->user)->get(route('stock.parts.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('Stock/Parts/Index')
        ->has('parts.data', 1)
        ->where('parts.data.0.id', $partInDepotA->id)
    );

    $ids = collect($response->viewData('page')['props']['parts']['data'])->pluck('id');

    expect($ids)->not->toContain($partInDepotB->id);
    expect($ids)->not->toContain($partWithoutStock->id);
});
