<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separates "annual entitlement" leave from leave that is granted case by case.
 *
 * Sick, casual and annual are allocated to everyone each year. Maternity has a
 * cap but is not an entitlement every employee carries around, and compensatory
 * leave is earned by working a holiday — neither should be handed out up front
 * or turn up as an encashable balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leave_types', 'auto_allocate')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->boolean('auto_allocate')
                    ->default(true)
                    ->after('max_days')
                    ->comment('Allocate max_days to every employee each year, or grant case by case');
            });
        }

        DB::table('leave_types')
            ->whereIn('slug', ['maternity', 'cpl'])
            ->update(['auto_allocate' => false]);

        // Clear balances that were handed out under the old assumption, but only
        // where nothing has been used against them.
        DB::table('leave_balances')
            ->whereIn('leave_type', ['maternity'])
            ->where('used', 0)
            ->update(['allocated' => 0, 'remaining' => 0]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_types', 'auto_allocate')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropColumn('auto_allocate');
            });
        }
    }
};
