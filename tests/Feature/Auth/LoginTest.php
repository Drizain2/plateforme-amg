<?php

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('un utilisateur rattaché à un shop actif est redirigé vers le dashboard', function () {
    $shop = Shop::factory()->create(['is_active' => true]);
    $user = User::factory()->create(['shop_id' => $shop->id]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('un utilisateur rattaché à un shop désactivé ne peut pas se connecter', function () {
    $shop = Shop::factory()->create(['is_active' => false]);
    $user = User::factory()->create(['shop_id' => $shop->id]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('un opérateur plateforme sans shop peut se connecter et est redirigé vers l\'administration', function () {
    $platformAdmin = User::factory()->create(['shop_id' => null]);
    $platformAdmin->assignRole('super_admin');

    $response = $this->post('/login', [
        'email' => $platformAdmin->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.plans.index'));
    $this->assertAuthenticatedAs($platformAdmin);
});
