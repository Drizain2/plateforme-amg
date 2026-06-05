<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    private array $names = [
        'GSM Partner', 'Phone Parts Pro', 'iStock Europe',
        'Repair Supply', 'TechParts FR', 'MobilePro Distribution',
        'Euro Phone Parts', 'AlphaTech Supply',
    ];

    public function run(): void
    {
        Shop::all()->each(function (Shop $shop) {
            app()->instance('current_shop', $shop);

            $count = fake()->numberBetween(3, 5);
            $selected = array_slice($this->names, 0, $count);

            foreach ($selected as $name) {
                Supplier::create([
                    'shop_id' => $shop->id,
                    'name' => $name,
                    'email' => fake()->companyEmail(),
                    'phone' => fake()->phoneNumber(),
                    'address' => fake()->address(),
                    'is_active' => true,
                ]);
            }
        });
    }
}
