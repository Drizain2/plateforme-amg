<?php

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->admin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
});

test("un admin peut inviter un utilisateur avec le rôle gestionnaire", function () {
    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Nadia Gestionnaire',
        'email' => 'nadia@example.com',
        'role' => 'manager',
    ]);

    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect();

    $user = User::where('email', 'nadia@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('manager'))->toBeTrue();
});

test("le rôle obsolète gestionnaire n'est plus accepté", function () {
    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Test',
        'email' => 'test@example.com',
        'role' => 'gestionnaire',
    ]);

    $response->assertSessionHasErrors('role');
});
