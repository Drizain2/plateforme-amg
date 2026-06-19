<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Stock
            'stock.view',
            'stock.create',
            'stock.edit',
            'stock.delete',
            'stock.restock',      // entrée de stock — interdit caissière
            'stock.transfer',     // transfert entre dépôts
            'stock.adjust',       // ajustement inventaire

            // Tickets
            'tickets.view',
            'tickets.create',
            'tickets.edit',
            'tickets.delete',
            'tickets.transition', // changer statut
            'tickets.assign',     // assigner technicien

            // Clients
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Facturation
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.mark_paid',

            // Achats fournisseurs
            'purchases.view',
            'purchases.create',
            'purchases.receive',  // marquer reçue — déclenche l'entrée en stock
            'purchases.mark_paid',

            // Dépôts
            'depots.view',
            'depots.manage',      // créer/modifier/supprimer dépôts

            // Utilisateurs
            'users.view',
            'users.manage',

            // Settings
            'settings.manage',

            // Dashboard
            'dashboard.view',
            'dashboard.analytics', // stats avancées
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Ensuite, création des rôles et assignation des permissions

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $technicien = Role::firstOrCreate(['name' => 'technicien']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $caissiere = Role::firstOrCreate(['name' => 'caissiere']);

        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions([
            'stock.view',
            'stock.create',
            'stock.edit',
            'stock.delete',
            'stock.restock',
            'stock.transfer',
            'stock.adjust',

            'tickets.view',
            'tickets.create',
            'tickets.edit',
            'tickets.delete',
            'tickets.transition',
            'tickets.assign',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.mark_paid',

            'purchases.view',
            'purchases.create',
            'purchases.receive',
            'purchases.mark_paid',

            'depots.view',
            'depots.manage',

            'users.view',
            'users.manage',

            'dashboard.view',
            'dashboard.analytics',
        ]);

        $technicien->syncPermissions([
            'tickets.view',
            'tickets.create',
            'tickets.edit',
            'tickets.transition',
            'tickets.assign',

            'customers.view',
            'customers.create',

            'invoices.view',
            'invoices.create',

            'dashboard.view',
        ]);

        $manager->syncPermissions([
            'stock.view',
            'stock.create',
            'stock.edit',
            'stock.delete',
            'stock.restock',
            'stock.transfer',

            'tickets.view',
            'tickets.create',
            'tickets.edit',
            'tickets.transition',
            'tickets.assign',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'invoices.view',
            'invoices.create',

            'purchases.view',
            'purchases.create',
            'purchases.receive',
            'purchases.mark_paid',

            'depots.view',

            'dashboard.view',
            'dashboard.analytics',
        ]);

        $caissiere->syncPermissions([
            'stock.view', // lecture seule
            'tickets.view',
            'tickets.create',
            'customers.view',
            'customers.create',
            'invoices.view',
            'invoices.create',
            'invoices.mark_paid', // enregistrer un paiment
            'dashboard.view',
        ]);
    }
}
