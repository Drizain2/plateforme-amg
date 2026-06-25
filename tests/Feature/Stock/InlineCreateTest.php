<?php

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
    ]);
});

test('créer une catégorie via fetch JSON renvoie la catégorie créée', function () {
    $response = $this->actingAs($this->admin)->postJson(route('stock.categories.store'), [
        'name' => 'Écrans',
    ]);

    $response->assertOk();
    $response->assertJsonPath('name', 'Écrans');
    expect($response->json('id'))->not->toBeNull();
});

test('créer un fournisseur via fetch JSON renvoie le fournisseur créé', function () {
    $response = $this->actingAs($this->admin)->postJson(route('stock.suppliers.store'), [
        'name' => 'GSM Partner',
    ]);

    $response->assertOk();
    $response->assertJsonPath('name', 'GSM Partner');
    expect($response->json('id'))->not->toBeNull();
});
