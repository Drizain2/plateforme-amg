<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    /** @var string[] */
    private array $allPermissions = [
        'dashboard.view',
        'dashboard.analytics',

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

        'depots.view',
        'depots.manage',

        'users.view',
        'users.manage',

        'settings.manage',
    ];

    /** @var array<string, string[]> */
    private array $rolePermissions = [
        'admin' => '*', // toutes les permissions

        'gestionnaire' => [
            'dashboard.view', 'dashboard.analytics',
            'stock.view', 'stock.create', 'stock.edit', 'stock.restock', 'stock.transfer', 'stock.adjust',
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.transition', 'tickets.assign',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.mark_paid',
            'depots.view',
            'users.view',
        ],

        'technicien' => [
            'dashboard.view',
            'stock.view', 'stock.restock',
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.transition',
            'customers.view', 'customers.create',
            'invoices.view',
            'depots.view',
        ],

        'caissiere' => [
            'dashboard.view',
            'tickets.view',
            'customers.view', 'customers.create', 'customers.edit',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.mark_paid',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Créer toutes les permissions
        foreach ($this->allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Créer les rôles et assigner les permissions
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($permissions === '*') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
