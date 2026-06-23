<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'platform@sav-platform.fr'],
            [
                'shop_id' => null,
                'name' => 'Opérateur Plateforme',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $user->assignRole('super_admin');
    }
}
