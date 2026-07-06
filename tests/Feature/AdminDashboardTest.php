<?php

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->superAdmin = User::factory()->create([
        'shop_id' => null,
        'depot_active_id' => null,
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('un super_admin peut accéder au tableau de bord', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Admin/Dashboard')
            ->has('kpis')
            ->has('kpis.mrr')
            ->has('kpis.arr')
            ->has('kpis.shops')
            ->has('kpis.conversionRate')
            ->has('kpis.pendingPaymentsCount')
            ->has('acquisition')
            ->has('pendingPayments')
            ->has('recentShops')
        );
});

test('le tableau de bord retourne 12 semaines d\'acquisition', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('acquisition', 12));
});

test('un admin classique ne peut pas accéder au tableau de bord admin', function () {
    $this->seed(RoleSeeder::class);
    $shop = Shop::factory()->create();
    $admin = User::factory()->admin()->create(['shop_id' => $shop->id]);

    app()->instance('current_shop', $shop);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('un visiteur non authentifié est redirigé vers le login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
