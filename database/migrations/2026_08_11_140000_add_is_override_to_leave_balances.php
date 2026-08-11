<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a balance that HR set by hand for one employee.
 *
 * The nightly sync tops balances up to the leave type's entitlement. Without
 * this flag it would quietly undo a per-employee allocation — an employee given
 * 20 annual days would drop back to the standard 15 overnight.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leave_balances', 'is_override')) {
            return;
        }

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->boolean('is_override')
                ->default(false)
                ->after('remaining')
                ->comment('Allocation was set for this employee by HR; the nightly sync leaves it alone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('leave_balances', 'is_override')) {
            return;
        }

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn('is_override');
        });
    }
};
