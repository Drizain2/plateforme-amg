<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Pour démarrer avec un seul atelier.',
                'price' => 0,
                'max_users' => 3,
                'max_depots' => 1,
                'features' => ['1 dépôt', '3 utilisateurs', 'Tickets & Stock'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Pour les ateliers en croissance avec plusieurs dépôts.',
                'price' => 15000,
                'max_users' => 10,
                'max_depots' => null,
                'features' => ['Dépôts illimités', '10 utilisateurs', 'Facturation', 'Notifications', 'Analytics'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Pour les réseaux d\'ateliers avec besoins avancés.',
                'price' => 45000,
                'max_users' => null,
                'max_depots' => null,
                'features' => ['Tout en illimité', 'Support prioritaire', 'API access'],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
