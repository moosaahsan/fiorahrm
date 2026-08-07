<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncEmployeeSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-employee-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync default mark_half_day_after setting for all employees';

    public function handle()
    {
        $employees = \App\Models\Employee::all();
        $count = 0;

        foreach ($employees as $employee) {
            $exists = \Illuminate\Support\Facades\DB::table('employee_settings')
                ->where('emp_id', $employee->id)
                ->where('setting_name', 'mark_half_day_after')
                ->exists();

            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('employee_settings')->insert([
                    'emp_id' => $employee->id,
                    'setting_name' => 'mark_half_day_after',
                    'setting_value' => '120',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        $this->info("Successfully synced mark_half_day_after for {$count} employees.");
    }
}
