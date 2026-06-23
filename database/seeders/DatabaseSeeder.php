<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,          // 1. Rôles Spatie (requis en premier)
            PlanSeeder::class,          // 2. Offres d'abonnement
            ShopSeeder::class,          // 3. Shops (tenant root)
            UserSeeder::class,          // 4. Admin + techniciens par shop
            DepotSeeder::class,         // 5. Dépôts + affectation utilisateurs
            SupplierSeeder::class,      // 6. Fournisseurs
            CategorieSeeder::class,     // 7. Catégories de pièces
            PartSeeder::class,          // 8. Pièces (nécessite catégories + fournisseurs)
            StockDepotSeeder::class,    // 9. Stock par pièce × dépôt
            StockMovementSeeder::class, // 10. Historique des mouvements
        ]);
    }
}
