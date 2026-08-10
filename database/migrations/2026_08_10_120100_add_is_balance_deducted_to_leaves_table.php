<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restores the flag the leave balance flow is built around.
 *
 * LeaveObserver, LeaveController, MarkAbsentees and HandlesManualAttendance all
 * read and write leaves.is_balance_deducted, but the column was never created —
 * so approvals silently failed to touch balances (and the admin approve flow
 * errored on write).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leaves', 'is_balance_deducted')) {
            return;
        }

        Schema::table('leaves', function (Blueprint $table) {
            $table->boolean('is_balance_deducted')
                ->default(false)
                ->after('approved_by')
                ->comment('Whether this leave has been deducted from the employee leave balance');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('leaves', 'is_balance_deducted')) {
            return;
        }

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('is_balance_deducted');
        });
    }
};
