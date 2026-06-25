<?php

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->service = app(PermissionService::class);
});

test('un plan sans module désactivé ne restreint aucune permission', function () {
    $plan = Plan::factory()->create(['disabled_modules' => []]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();

    expect($this->service->has($admin, 'tickets.view'))->toBeTrue();
    expect($this->service->effectivePermissions($admin))->toContain('tickets.view');
});

test('un plan qui désactive le module tickets retire les permissions tickets.* mais garde le reste', function () {
    $plan = Plan::factory()->create(['disabled_modules' => ['tickets']]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();

    expect($this->service->has($admin, 'tickets.view'))->toBeFalse();

    $effective = $this->service->effectivePermissions($admin);

    foreach ($effective as $permission) {
        expect($permission)->not->toStartWith('tickets.');
    }

    expect($effective)->toContain('stock.view');
    expect($effective)->toContain('customers.view');
    expect($effective)->toContain('invoices.view');
});

test('un override de permission ne peut pas réactiver un module désactivé par le plan', function () {
    $plan = Plan::factory()->create(['disabled_modules' => ['tickets']]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $admin = User::factory()->for($shop)->admin()->create();

    $this->service->setOverride($admin, 'tickets.create', true);

    expect($this->service->has($admin, 'tickets.create'))->toBeFalse();
    expect($this->service->effectivePermissions($admin))->not->toContain('tickets.create');
});

test('le super_admin garde accès à tickets même si le plan de son shop désactive ce module', function () {
    $plan = Plan::factory()->create(['disabled_modules' => ['tickets']]);
    $shop = Shop::factory()->create(['plan_id' => $plan->id]);
    $superAdmin = User::factory()->for($shop)->create();
    $superAdmin->assignRole('super_admin');

    expect($this->service->has($superAdmin, 'tickets.view'))->toBeTrue();
    expect($this->service->effectivePermissions($superAdmin))->toContain('tickets.view');
});

test('le modèle Plan ne désactive jamais un domaine hors de la liste blanche', function () {
    $plan = Plan::factory()->make(['disabled_modules' => ['settings']]);

    expect($plan->disablesPermissionDomain('settings.manage'))->toBeFalse();
});
