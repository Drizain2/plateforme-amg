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
            // stock seulement
            [
                'name' => 'Stock Starter',
                'slug' => 'stock-starter',
                'description' => 'Pour démarrer la gestion de stock sans service de réparation.',
                'price' => 0,
                'max_users' => 3,
                'max_depots' => 1,
                'features' => ['1 dépôt', '3 utilisateurs', 'Gestion de stock & facturation'],
                'disabled_modules' => ['tickets', 'inventaire'],
                'sort_order' => 4,
            ],
            [
                'name' => 'Stock Pro',
                'slug' => 'stock-pro',
                'description' => 'Pour les entreprises de stock avec plusieurs dépôts.',
                'price' => 12000,
                'max_users' => 10,
                'max_depots' => null,
                'features' => ['Dépôts illimités', '10 utilisateurs', 'Achats fournisseurs', 'Analytics'],
                'disabled_modules' => ['tickets'],
                'sort_order' => 5,
            ],
            [
                'name' => 'Stock Enterprise',
                'slug' => 'stock-enterprise',
                'description' => 'Pour les réseaux multi-dépôts avec besoins avancés.',
                'price' => 30000,
                'max_users' => null,
                'max_depots' => null,
                'features' => ['Tout en illimité', 'Achats fournisseurs', 'Analytics avancés', 'Support prioritaire', 'API access'],
                'disabled_modules' => ['tickets'],
                'sort_order' => 6,
            ],

            // stock + reparation
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Pour démarrer avec un seul atelier.',
                'price' => 0,
                'max_users' => 3,
                'max_depots' => 1,
                'max_tickets' => 10,
                'disabled_modules' => ['inventaire'],
                'features' => ['1 dépôt', '3 utilisateurs', '10 tickets/mois', 'Gestion de stock & facturation'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Pour les ateliers en croissance avec plusieurs dépôts.',
                'price' => 20000,
                'max_users' => 10,
                'max_depots' => null,
                'max_tickets' => null,
                'disabled_modules' => [],
                'features' => ['Dépôts illimités', '10 utilisateurs', 'Tickets illimités', 'Facturation', 'Notifications', 'Analytics'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Pour les réseaux d\'ateliers avec besoins avancés.',
                'price' => 45000,
                'max_users' => null,
                'max_depots' => null,
                'max_tickets' => null,
                'disabled_modules' => [],
                'features' => ['Tout en illimité', 'Support prioritaire', 'API access'],
                'sort_order' => 3,
            ],
        ];
        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
