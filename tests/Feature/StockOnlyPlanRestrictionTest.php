<?php

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->plan = Plan::factory()->create(['disabled_modules' => ['tickets']]);
    $this->shop = Shop::factory()->create(['plan_id' => $this->plan->id]);
    $this->admin = User::factory()->for($this->shop)->admin()->create();
});

test('un admin d\'un atelier stock-only ne peut pas accéder à la liste des tickets', function () {
    $response = $this->actingAs($this->admin)->get(route('tickets.index'));

    $response->assertForbidden();
});

test('un admin d\'un atelier stock-only peut toujours accéder au stock, aux clients et aux factures', function () {
    $this->actingAs($this->admin)->get(route('stock.parts.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('customers.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('invoices.index'))->assertOk();
});

test('un admin d\'un atelier stock-only ne voit pas la permission tickets.view partagée à Inertia', function () {
    $response = $this->actingAs($this->admin)->get(route('stock.parts.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('auth.permissions', fn ($permissions) => $permissions->doesntContain('tickets.view'))
    );
});
