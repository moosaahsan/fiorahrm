<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure 'teams' table exists and has 'name'
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('teams', function (Blueprint $table) {
                if (!Schema::hasColumn('teams', 'name')) {
                    $table->string('name')->unique()->after('id');
                }
                if (!Schema::hasColumn('teams', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (!Schema::hasColumn('teams', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
            });
        }

        // 2. Ensure 'employees' table has 'team_id'
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('id');
            }
        });

        // 3. Ensure 'holiday_team' pivot table exists
        if (!Schema::hasTable('holiday_team')) {
            Schema::create('holiday_team', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('holiday_id');
                $table->unsignedBigInteger('team_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration to prevent data loss on production
    }
};
