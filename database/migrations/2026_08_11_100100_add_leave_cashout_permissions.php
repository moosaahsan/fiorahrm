<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for year-end leave encashment.
 *
 * Viewing follows whoever can already see leaves; processing a payout follows
 * whoever can already manage leave balances or generate payroll, since the
 * money lands on a payslip.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definitions = [
            [
                'name' => 'view-leave-cashouts',
                'module' => 'Leaves',
                'inherit_from' => ['view-leaves'],
            ],
            [
                'name' => 'manage-leave-cashouts',
                'module' => 'Leaves',
                'inherit_from' => ['manage-leave-balances', 'generate-payroll'],
            ],
        ];

        foreach ($definitions as $definition) {
            $permission = Permission::firstOrCreate(
                ['name' => $definition['name'], 'guard_name' => 'web'],
                ['module' => $definition['module']]
            );

            if (! $permission->module || $permission->module === 'General') {
                $permission->update(['module' => $definition['module']]);
            }

            $roles = Role::whereHas('permissions', function ($query) use ($definition) {
                $query->whereIn('name', $definition['inherit_from']);
            })->get();

            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo(
                Permission::whereIn('name', ['view-leave-cashouts', 'manage-leave-cashouts'])->get()
            );
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['view-leave-cashouts', 'manage-leave-cashouts'])->delete();
    }
};
