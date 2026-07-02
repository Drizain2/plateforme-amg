<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,          // 1. Rôles Spatie (requis en premier)
            PlatformAdminSeeder::class, // 2. Opérateur plateforme (sans shop)
            PlanSeeder::class,          // 3. Offres d'abonnement
            ShopSeeder::class,          // 4. Shops (tenant root)
            UserSeeder::class,          // 5. Admin + techniciens par shop
            DepotSeeder::class,         // 6. Dépôts + affectation utilisateurs
            SupplierSeeder::class,      // 7. Fournisseurs
            CategorieSeeder::class,     // 8. Catégories de pièces
            PartSeeder::class,          // 9. Pièces (nécessite catégories + fournisseurs)
            StockDepotSeeder::class,    // 10. Stock par pièce × dépôt
            StockMovementSeeder::class, // 11. Historique des mouvements
            TicketSeeder::class,        // 12. Tickets SAV (nécessite dépôts + utilisateurs)
        ]);
    }
}
