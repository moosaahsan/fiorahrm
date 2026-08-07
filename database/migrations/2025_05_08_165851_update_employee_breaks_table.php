<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_breaks', function (Blueprint $table) {
            // Add new columns
            $table->enum('type', ['Official', 'General'])
                ->default('General')
                ->after('end_time')
                ->comment('Break type: Official (excluded from deduction), General (deducted)');

            $table->string('status')
                ->default('On break')
                ->after('type')
                ->comment('Break status: On break, Ended, etc.');

            $table->string('reason')->nullable()
                ->after('status')
                ->comment('Reason or note for the break');

            $table->integer('spent_minutes')->nullable()->default(0)
                ->after('reason')
                ->comment('Total break duration in minutes');

            $table->integer('remaining_minutes')->nullable()->default(0)
                ->after('spent_minutes')
                ->comment('Remaining break minutes allowed');

            $table->integer('exceeded_minutes')->nullable()->default(0)
                ->after('remaining_minutes')
                ->comment('Break time exceeded beyond allowed limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_breaks', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'status',
                'reason',
                'spent_minutes',
                'remaining_minutes',
                'exceeded_minutes',
            ]);
        });
    }
};

