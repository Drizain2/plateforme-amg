<?php

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->superAdmin = User::factory()->create([
        'shop_id' => null,
        'depot_active_id' => null,
    ]);
    $this->superAdmin->assignRole('super_admin');

    $this->shop = Shop::factory()->create(['is_active' => true]);
    $this->shopAdmin = User::factory()->admin()->create(['shop_id' => $this->shop->id]);
});

test('un super_admin peut démarrer une impersonation', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.impersonate', $this->shop))
        ->assertRedirect(route('dashboard'));

    expect(Auth::id())->toBe($this->shopAdmin->id)
        ->and(session('impersonating_user_id'))->toBe($this->superAdmin->id);
});

test('l\'impersonation redirige vers le dashboard de l\'atelier', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.impersonate', $this->shop))
        ->assertRedirect(route('dashboard'));
});

test('arrêter l\'impersonation restaure le super_admin', function () {
    $this->actingAs($this->superAdmin)
        ->withSession(['impersonating_user_id' => $this->superAdmin->id]);

    Auth::loginUsingId($this->shopAdmin->id);

    $this->actingAs($this->shopAdmin)
        ->withSession(['impersonating_user_id' => $this->superAdmin->id])
        ->post(route('admin.impersonation.stop'))
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::id())->toBe($this->superAdmin->id)
        ->and(session()->has('impersonating_user_id'))->toBeFalse();
});

test('arrêter sans impersonation active retourne 403', function () {
    $this->actingAs($this->shopAdmin)
        ->post(route('admin.impersonation.stop'))
        ->assertForbidden();
});

test('impersonate un atelier sans admin retourne 404', function () {
    $shopWithoutAdmin = Shop::factory()->create(['is_active' => true]);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.impersonate', $shopWithoutAdmin))
        ->assertNotFound();
});

test('un admin classique ne peut pas démarrer une impersonation', function () {
    $this->actingAs($this->shopAdmin)
        ->post(route('admin.impersonate', $this->shop))
        ->assertForbidden();
});

test('un visiteur non authentifié ne peut pas démarrer une impersonation', function () {
    $this->post(route('admin.impersonate', $this->shop))
        ->assertRedirect(route('login'));
});
