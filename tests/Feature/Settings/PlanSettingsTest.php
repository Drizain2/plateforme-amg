<?php

use App\Models\Depot;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->currentPlan = Plan::factory()->create(['max_users' => 5, 'max_depots' => 5]);
    $this->shop = Shop::factory()->create(['plan_id' => $this->currentPlan->id]);
    $this->admin = User::factory()->for($this->shop)->admin()->create();
});

test('un admin peut changer l\'offre de son atelier', function () {
    $newPlan = Plan::factory()->create(['max_users' => 5, 'max_depots' => 5]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $newPlan));

    $response->assertSessionHas('success');
    expect($this->shop->refresh()->plan_id)->toBe($newPlan->id);
});

test('un admin ne peut pas passer à une offre désactivée', function () {
    $inactivePlan = Plan::factory()->create(['is_active' => false]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $inactivePlan));

    $response->assertSessionHas('error');
    expect($this->shop->refresh()->plan_id)->toBe($this->currentPlan->id);
});

test('un admin ne peut pas passer à une offre dont la limite d\'utilisateurs est déjà dépassée', function () {
    User::factory()->for($this->shop)->technicien()->count(3)->create();
    $restrictedPlan = Plan::factory()->create(['max_users' => 2, 'max_depots' => null]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $restrictedPlan));

    $response->assertSessionHas('error');
    expect($this->shop->refresh()->plan_id)->toBe($this->currentPlan->id);
});

test('un admin ne peut pas passer à une offre dont la limite de dépôts est déjà dépassée', function () {
    Depot::factory()->for($this->shop)->count(3)->create();
    $restrictedPlan = Plan::factory()->create(['max_users' => null, 'max_depots' => 2]);

    $response = $this->actingAs($this->admin)->put(route('settings.plan', $restrictedPlan));

    $response->assertSessionHas('error');
    expect($this->shop->refresh()->plan_id)->toBe($this->currentPlan->id);
});

test('un technicien ne peut pas changer l\'offre de l\'atelier', function () {
    $depot = Depot::factory()->for($this->shop)->create();
    $technicien = User::factory()->for($this->shop)->technicien()->create(['depot_active_id' => $depot->id]);
    $newPlan = Plan::factory()->create();

    $response = $this->actingAs($technicien)->put(route('settings.plan', $newPlan));

    $response->assertForbidden();
    expect($this->shop->refresh()->plan_id)->toBe($this->currentPlan->id);
});
