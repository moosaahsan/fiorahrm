<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sanitize the string column BEFORE altering its type
        try {
            DB::statement("UPDATE performance_evaluations SET manual_dress_code = '10' WHERE LOWER(manual_dress_code) IN ('followed', 'yes', 'true', '1')");
        } catch (\Exception $e) {}

        try {
            DB::statement("UPDATE performance_evaluations SET manual_dress_code = '0' WHERE manual_dress_code NOT REGEXP '^[0-9]+(\\\\.[0-9]+)?$'");
        } catch (\Exception $e) {}
        
        try {
            DB::statement('ALTER TABLE performance_evaluations CHANGE manual_dress_code dress_code_score DECIMAL(5,2) NOT NULL DEFAULT 0.00');
        } catch (\Exception $e) {}

        // 2. Work Performance
        try {
            DB::statement('ALTER TABLE performance_evaluations CHANGE manual_work_performance work_performance_score DECIMAL(5,2) NOT NULL DEFAULT 0.00');
        } catch (\Exception $e) {}

        // 3. Behavior
        try {
            DB::statement('ALTER TABLE performance_evaluations CHANGE manual_behavior behavior_score DECIMAL(5,2) NOT NULL DEFAULT 0.00');
        } catch (\Exception $e) {}

        // 4. Comments
        try {
            DB::statement('ALTER TABLE performance_evaluations CHANGE manual_comments comments TEXT NULL DEFAULT NULL');
        } catch (\Exception $e) {}

        // 5. Evaluator ID (Foreign Key)
        try {
            DB::statement('ALTER TABLE performance_evaluations DROP FOREIGN KEY performance_evaluations_evaluated_by_foreign');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('ALTER TABLE performance_evaluations DROP FOREIGN KEY fk_evaluated_by');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE performance_evaluations CHANGE evaluated_by evaluator_id BIGINT UNSIGNED NULL DEFAULT NULL');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE performance_evaluations ADD CONSTRAINT perf_evaluator_id_fk FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE SET NULL');
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
