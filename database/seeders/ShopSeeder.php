<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Shop::factory()->create([
            'name' => 'Atelier Demo',
            'slug' => 'atelier-demo',
            'email' => 'demo@atelier.fr',
            'plan_id' => Plan::where('slug', 'pro')->value('id'),
        ]);
        Shop::factory(10)->create();
    }
}
