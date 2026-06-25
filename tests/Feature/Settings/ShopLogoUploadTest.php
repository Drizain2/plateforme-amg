<?php

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->shop = Shop::factory()->create();

    $this->admin = User::factory()->admin()->create([
        'shop_id' => $this->shop->id,
    ]);
});

test('un admin peut uploader un logo pour son atelier', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->actingAs($this->admin)->post(route('settings.shop'), [
        'name' => $this->shop->name,
        'email' => $this->shop->email,
        'tax_rate' => 20,
        'logo' => $file,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');

    $this->shop->refresh();
    expect($this->shop->logo_url)->not->toBeNull();
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $this->shop->logo_url));
});
