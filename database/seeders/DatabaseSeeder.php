<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,          // 1. Rôles Spatie (requis en premier)
            ShopSeeder::class,          // 2. Shops (tenant root)
            UserSeeder::class,          // 3. Admin + techniciens par shop
            DepotSeeder::class,         // 4. Dépôts + affectation utilisateurs
            SupplierSeeder::class,      // 5. Fournisseurs
            CategorieSeeder::class,     // 6. Catégories de pièces
            PartSeeder::class,          // 7. Pièces (nécessite catégories + fournisseurs)
            StockDepotSeeder::class,    // 8. Stock par pièce × dépôt
            StockMovementSeeder::class, // 9. Historique des mouvements
        ]);
    }
}
