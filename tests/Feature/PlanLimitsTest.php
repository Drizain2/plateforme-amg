<?php

use App\Models\Depot;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('un atelier ne peut pas créer plus de dépôts que la limite de son offre', function () {
    $plan = Plan::factory()->create(['max_depots' => 1]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();
    Depot::factory()->for($shop)->create();

    $response = $this->actingAs($admin)->post(route('stock.depots.store'), [
        'name' => 'Dépôt secondaire',
        'address' => 'Quelque part',
    ]);

    $response->assertSessionHas('error');
    expect($shop->depots()->count())->toBe(1);
});

test('un atelier peut créer un dépôt si la limite de son offre n\'est pas atteinte', function () {
    $plan = Plan::factory()->create(['max_depots' => 2]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();
    Depot::factory()->for($shop)->create();

    $response = $this->actingAs($admin)->post(route('stock.depots.store'), [
        'name' => 'Dépôt secondaire',
        'address' => 'Quelque part',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');
    expect($shop->depots()->count())->toBe(2);
});

test('un atelier avec une offre à dépôts illimités peut en créer autant que voulu', function () {
    $plan = Plan::factory()->create(['max_depots' => null]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();
    Depot::factory()->for($shop)->count(5)->create();

    $response = $this->actingAs($admin)->post(route('stock.depots.store'), [
        'name' => 'Dépôt secondaire',
        'address' => 'Quelque part',
    ]);

    $response->assertSessionHas('success');
    expect($shop->depots()->count())->toBe(6);
});

test('un atelier ne peut pas inviter plus d\'utilisateurs que la limite de son offre', function () {
    $plan = Plan::factory()->create(['max_users' => 1]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Nouveau membre',
        'email' => 'nouveau@atelier-test.fr',
        'role' => 'technicien',
    ]);

    $response->assertSessionHas('error');
    expect(User::where('email', 'nouveau@atelier-test.fr')->exists())->toBeFalse();
});

test('un atelier peut inviter un utilisateur si la limite de son offre n\'est pas atteinte', function () {
    $plan = Plan::factory()->create(['max_users' => 2]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Nouveau membre',
        'email' => 'nouveau@atelier-test.fr',
        'role' => 'technicien',
    ]);

    $response->assertSessionHasNoErrors();
    expect(User::where('email', 'nouveau@atelier-test.fr')->exists())->toBeTrue();
});
