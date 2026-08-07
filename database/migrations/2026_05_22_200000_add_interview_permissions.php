<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Interview Permissions
        $permissions = [
            'view-interview',
            'create-interview',
            'edit-interview',
            'delete-interview',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Assign to Roles
        // Admin gets everything
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Manager gets everything for interviews
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $manager->givePermissionTo($permissions);
        }

        // Team Lead gets view only
        $teamLead = Role::where('name', 'team-lead')->first();
        if ($teamLead) {
            $teamLead->givePermissionTo('view-interview');
        }

        // HR gets view and create
        $hr = Role::where('name', 'hr')->first();
        if ($hr) {
            $hr->givePermissionTo(['view-interview', 'create-interview']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'view-interview',
            'create-interview',
            'edit-interview',
            'delete-interview',
        ];

        Permission::whereIn('name', $permissions)->delete();
        
        // Spatie handles pivot cleanup automatically on delete if constrained correctly, 
        // but it's good to clear cache anyway.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
