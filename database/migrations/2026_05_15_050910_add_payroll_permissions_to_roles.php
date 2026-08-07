<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Prevent cache interference during deployment
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'approve-payroll']);

        $role = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
        if (!$role) {
            $role = \Spatie\Permission\Models\Role::first();
        }

        if ($role && !$role->hasPermissionTo('approve-payroll')) {
            $role->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not strictly delete permissions on rollback
    }
};
