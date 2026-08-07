<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LeavePolicy\LeavePolicyEngine;
use App\Services\LeavePolicy\Policies\MonthlyHalfDayBenefitPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ProcessMonthlyLeavePolicies extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'leaves:process-monthly-policies {--month= : Month to process (YYYY-MM)}';

    /**
     * The console command description.
     */
    protected $description = 'Run leave policy engine to calculate benefits/reverts for the month';

    /**
     * Execute the console command.
     */
    public function handle(LeavePolicyEngine $engine)
    {
        $monthStr = $this->option('month');

        if ($monthStr) {
            try {
                $parts = explode('-', $monthStr);
                $period = \App\Services\PayrollPeriodService::forMonth((int) $parts[0], (int) $parts[1]);
                $monthToProcess = $period['start']->copy();
            } catch (\Exception $e) {
                $this->error("Invalid month format. Use YYYY-MM.");
                return 1;
            }
        } else {
            // Default to PREVIOUS payroll month
            $now = Carbon::now();
            $thisPeriod = \App\Services\PayrollPeriodService::getPeriodForDate($now);
            $targetDateForLastMonth = \Carbon\Carbon::createFromDate($thisPeriod['payroll_year'], $thisPeriod['payroll_month'], 1)->subMonthNoOverflow();
            $lastPeriod = \App\Services\PayrollPeriodService::forMonth($targetDateForLastMonth->year, $targetDateForLastMonth->month);
            $monthToProcess = $lastPeriod['start']->copy();
        }

        $this->info("Processing leave policies for: " . $monthToProcess->format('F Y'));

        // Register Policies
        $engine->registerPolicy(new MonthlyHalfDayBenefitPolicy());

        // Process all employees
        try {
            $engine->processAllEmployees($monthToProcess);
            $this->info("Successfully processed leave policies.");
            Log::info("Leave policies processed manually/scheduled for: " . $monthToProcess->format('Y-m'));
        } catch (\Exception $e) {
            $this->error("Error processing policies: " . $e->getMessage());
            Log::error("Failed to process leave policies: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
