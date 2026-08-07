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

        $permission = Permission::firstOrCreate(
            ['name' => 'checkout-employee', 'guard_name' => 'web'],
            ['module' => 'Attendance']
        );

        if (!$permission->module || $permission->module === 'General') {
            $permission->update(['module' => 'Attendance']);
        }

        $roles = Role::whereHas('permissions', function ($query) {
            $query->whereIn('name', ['manage-attendance', 'view-attendance']);
        })->get();

        foreach ($roles as $role) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin && !$admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::where('name', 'checkout-employee')->delete();
    }
};
