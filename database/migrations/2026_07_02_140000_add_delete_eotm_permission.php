<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'delete-eotm', 'guard_name' => 'web']);
        
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->revokePermissionTo('delete-eotm');
        }
        Permission::where('name', 'delete-eotm')->delete();
    }
};
