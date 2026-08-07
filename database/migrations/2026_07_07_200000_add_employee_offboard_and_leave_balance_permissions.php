<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $definitions = [
            [
                'name' => 'offboard-employee',
                'module' => 'Employees',
                'inherit_from' => ['delete-employee', 'manage-employees'],
            ],
            [
                'name' => 'manage-leave-balances',
                'module' => 'Leaves',
                'inherit_from' => ['edit-employee', 'manage-employees'],
            ],
        ];

        foreach ($definitions as $definition) {
            $permission = Permission::firstOrCreate(
                ['name' => $definition['name'], 'guard_name' => 'web'],
                ['module' => $definition['module']]
            );

            if (!$permission->module || $permission->module === 'General') {
                $permission->update(['module' => $definition['module']]);
            }

            $roles = Role::whereHas('permissions', function ($query) use ($definition) {
                $query->whereIn('name', $definition['inherit_from']);
            })->get();

            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(
                Permission::whereIn('name', ['offboard-employee', 'manage-leave-balances'])->get()
            );
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['offboard-employee', 'manage-leave-balances'])->delete();
    }
};
