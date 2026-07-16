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
            ['email' => 'drizain2.0@gmail.com'],
            [
                'shop_id' => null,
                'name' => 'Traore Drissa',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );

        $user->assignRole('super_admin');
    }
}
