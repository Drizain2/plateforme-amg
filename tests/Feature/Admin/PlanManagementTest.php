<?php

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->platformAdmin = User::factory()->create(['shop_id' => null]);
    $this->platformAdmin->assignRole('super_admin');
});

test('un opérateur plateforme peut accéder à la liste des offres', function () {
    $response = $this->actingAs($this->platformAdmin)->get(route('admin.plans.index'));

    $response->assertOk();
});

test('un utilisateur non connecté ne peut pas accéder à l\'administration', function () {
    $response = $this->get(route('admin.plans.index'));

    $response->assertRedirect(route('login'));
});

test('un super_admin rattaché à un shop ne peut pas accéder à l\'administration des offres', function () {
    $shop = Shop::factory()->create();
    $shopSuperAdmin = User::factory()->create(['shop_id' => $shop->id]);
    $shopSuperAdmin->assignRole('super_admin');

    $response = $this->actingAs($shopSuperAdmin)->get(route('admin.plans.index'));

    $response->assertForbidden();
});

test('un admin de shop ne peut pas accéder à l\'administration des offres', function () {
    $shop = Shop::factory()->create();
    $admin = User::factory()->create(['shop_id' => $shop->id]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.plans.index'));

    $response->assertForbidden();
});

test('un opérateur plateforme peut créer une offre', function () {
    $response = $this->actingAs($this->platformAdmin)->post(route('admin.plans.store'), [
        'name' => 'Premium',
        'slug' => 'premium',
        'description' => 'Offre premium',
        'price' => 49900,
        'max_users' => 15,
        'max_depots' => null,
        'features' => ['Support prioritaire', 'API access'],
        'sort_order' => 4,
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();
    expect(Plan::where('slug', 'premium')->exists())->toBeTrue();
});

test('un opérateur plateforme peut mettre à jour une offre', function () {
    $plan = Plan::factory()->create(['name' => 'Ancien nom']);

    $response = $this->actingAs($this->platformAdmin)->put(route('admin.plans.update', $plan), [
        'name' => 'Nouveau nom',
        'slug' => $plan->slug,
        'price' => $plan->price,
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();
    expect($plan->refresh()->name)->toBe('Nouveau nom');
});

test('supprimer une offre utilisée par un atelier la désactive au lieu de la supprimer', function () {
    $plan = Plan::factory()->create(['is_active' => true]);
    Shop::factory()->create(['plan_id' => $plan->id]);

    $response = $this->actingAs($this->platformAdmin)->delete(route('admin.plans.destroy', $plan));

    $response->assertSessionHas('success');
    expect(Plan::find($plan->id))->not->toBeNull();
    expect($plan->refresh()->is_active)->toBeFalsy();
});

test('supprimer une offre non utilisée la supprime définitivement', function () {
    $plan = Plan::factory()->create();

    $response = $this->actingAs($this->platformAdmin)->delete(route('admin.plans.destroy', $plan));

    $response->assertSessionHas('success');
    expect(Plan::find($plan->id))->toBeNull();
});
