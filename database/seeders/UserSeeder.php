<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $isDemo = $shop->slug === 'atelier-demo';

            // Un admin par shop
            User::factory()->for($shop)->admin()->create(
                $isDemo ? ['email' => 'admin@atelier-demo.fr', 'name' => 'Admin Demo'] : []
            );

            // 2 techniciens par shop (connus pour la démo)
            if ($isDemo) {
                User::factory()->for($shop)->technician()->create(['email' => 'tech1@atelier-demo.fr', 'name' => 'Technicien 1']);
                User::factory()->for($shop)->technician()->create(['email' => 'tech2@atelier-demo.fr', 'name' => 'Technicien 2']);
            } else {
                User::factory()->count(fake()->numberBetween(2, 3))->for($shop)->technician()->create();
            }
        });
    }
}
