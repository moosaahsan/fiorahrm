<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add guard_name to roles table
        if (!Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        // 2. Add guard_name to permissions table  
        if (!Schema::hasColumn('permissions', 'guard_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        // 3. Rename permissions.slug -> permissions.name (Spatie uses name as identifier)
        // First, copy slug values to name column (since Spatie identifies by name)
        if (Schema::hasColumn('permissions', 'slug')) {
            // Update name column with slug values (slug is what we use as identifier)
            DB::statement('UPDATE permissions SET name = slug WHERE slug IS NOT NULL AND slug != ""');
            // Drop the slug column
            Schema::table('permissions', function (Blueprint $table) {
                try {
                    $table->dropUnique('permissions_slug_unique');
                } catch (\Exception $e) {
                    // Ignore if not exists or sqlite constraint failure
                }
                $table->dropColumn('slug');
            });
        }

        // 4. Rename role_users -> model_has_roles (Spatie convention)
        if (Schema::hasTable('role_users') && !Schema::hasTable('model_has_roles')) {
            
            // Drop foreign keys first (required for renaming columns and dropping primary key in MySQL)
            try {
                Schema::table('role_users', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                    $table->dropForeign(['role_id']);
                });
            } catch (\Exception $e) {
                // Foreign keys might not exist or have different names
            }

            // Drop primary key
            try {
                Schema::table('role_users', function (Blueprint $table) {
                    $table->dropPrimary(['user_id', 'role_id']);
                });
            } catch (\Exception $e) {
                // Primary key might not exist in this format
            }

            Schema::rename('role_users', 'model_has_roles');
            
            Schema::table('model_has_roles', function (Blueprint $table) {
                // Spatie needs model_type and model_id instead of just user_id
                $table->string('model_type')->default('App\\Models\\User')->after('role_id');
            });
            
            // Rename user_id to model_id
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->renameColumn('user_id', 'model_id');
            });
            
            // Set new composite primary key
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        // 5. Rename permission_role -> role_has_permissions (Spatie convention)
        if (Schema::hasTable('permission_role') && !Schema::hasTable('role_has_permissions')) {
            Schema::rename('permission_role', 'role_has_permissions');
        }

        // 6. Create model_has_permissions table (Spatie needs this for direct user permissions)
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions')
                    ->onDelete('cascade');
                $table->primary(['permission_id', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            });
        }

        // 7. Drop the old text 'permissions' column from roles table (was unused)
        if (Schema::hasColumn('roles', 'permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }

    public function down(): void
    {
        // Reverse is complex — backup first
        Schema::dropIfExists('model_has_permissions');
        
        if (Schema::hasTable('role_has_permissions')) {
            Schema::rename('role_has_permissions', 'permission_role');
        }
        
        if (Schema::hasTable('model_has_roles')) {
            Schema::rename('model_has_roles', 'role_users');
        }
    }
};
