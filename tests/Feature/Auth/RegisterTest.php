<?php

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->plan = Plan::factory()->create(['is_active' => true]);
});

test('la page d\'inscription est accessible avec la liste des offres', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('un visiteur peut créer un atelier avec une offre et est connecté automatiquement', function () {
    $response = $this->post('/register', [
        'shop_name' => 'Atelier Test',
        'name' => 'Jean Dupont',
        'email' => 'jean@atelier-test.fr',
        'phone' => '0612345678',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'plan_id' => $this->plan->id,
    ]);

    $response->assertRedirect(route('verification.notice'));

    $shop = Shop::where('email', 'jean@atelier-test.fr')->first();
    expect($shop)->not->toBeNull();
    expect($shop->plan_id)->toBe($this->plan->id);
    expect($shop->is_active)->toBeTruthy();

    $user = User::where('email', 'jean@atelier-test.fr')->first();
    expect($user)->not->toBeNull();
    expect($user->shop_id)->toBe($shop->id);
    expect($user->hasRole('admin'))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('l\'inscription échoue si l\'offre n\'est pas active', function () {
    $inactive = Plan::factory()->create(['is_active' => false]);

    $response = $this->post('/register', [
        'shop_name' => 'Atelier Test',
        'name' => 'Jean Dupont',
        'email' => 'jean@atelier-test.fr',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'plan_id' => $inactive->id,
    ]);

    $response->assertSessionHasErrors('plan_id');
});

test('l\'inscription échoue si l\'email est déjà utilisé', function () {
    User::factory()->create(['email' => 'jean@atelier-test.fr']);

    $response = $this->post('/register', [
        'shop_name' => 'Atelier Test',
        'name' => 'Jean Dupont',
        'email' => 'jean@atelier-test.fr',
        'password' => 'mot-de-passe-sur',
        'password_confirmation' => 'mot-de-passe-sur',
        'plan_id' => $this->plan->id,
    ]);

    $response->assertSessionHasErrors('email');
});
