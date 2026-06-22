<?php

use App\Models\Depot;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();
    $this->depot = Depot::factory()->create(['shop_id' => $this->shop->id]);

    $this->technicien = User::factory()->technicien()->create([
        'shop_id' => $this->shop->id,
        'depot_active_id' => $this->depot->id,
        'name' => 'Jean Tech',
        'email' => 'jean.tech@example.com',
    ]);
});

test('un technicien peut accéder à la page de ses paramètres personnels', function () {
    $response = $this->actingAs($this->technicien)->get(route('settings.edit'));

    $response->assertOk();
});

test('un technicien peut modifier son propre profil', function () {
    $response = $this->actingAs($this->technicien)->put(route('settings.profile'), [
        'name' => 'Jean Technicien',
        'email' => 'jean.technicien@example.com',
    ]);

    $response->assertSessionHas('success');
    expect($this->technicien->refresh()->name)->toBe('Jean Technicien');
    expect($this->technicien->email)->toBe('jean.technicien@example.com');
});

test('un technicien peut changer son propre mot de passe avec le bon mot de passe actuel', function () {
    $response = $this->actingAs($this->technicien)->put(route('settings.password'), [
        'current_password' => 'password',
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ]);

    $response->assertSessionHas('success');
    expect(Hash::check('nouveau-mot-de-passe', $this->technicien->refresh()->password))->toBeTrue();
});

test('changer son mot de passe échoue si le mot de passe actuel est incorrect', function () {
    $response = $this->actingAs($this->technicien)->put(route('settings.password'), [
        'current_password' => 'mauvais-mot-de-passe',
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ]);

    $response->assertSessionHasErrors('current_password');
});

test('un technicien ne peut pas modifier les paramètres de l\'atelier', function () {
    $response = $this->actingAs($this->technicien)->post(route('settings.shop'), [
        'name' => 'Atelier piraté',
        'email' => 'pirate@example.com',
        'tax_rate' => 0,
    ]);

    $response->assertForbidden();
    expect($this->shop->refresh()->name)->not->toBe('Atelier piraté');
});
