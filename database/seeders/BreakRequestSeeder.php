<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BreakRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timezone = 'Asia/Karachi';
        $today = Carbon::today($timezone);
        
        // Get all active employees
        $employees = Employee::whereNull('resign_date')
            ->with('employeeShifts.shift')
            ->get();

        if ($employees->isEmpty()) {
            $this->command->warn('No active employees found. Please run EmployeeSeeder first.');
            return;
        }

        // Get shifts
        $shifts = Shift::all();
        if ($shifts->isEmpty()) {
            $this->command->warn('No shifts found. Please run ShiftSeeder first.');
            return;
        }

        $breakReasons = [
            'Medical emergency - need to visit doctor',
            'Family emergency - urgent matter',
            'Personal work - bank visit required',
            'Official meeting outside office',
            'Client visit - urgent requirement',
            'Lunch break extended due to traffic',
            'Emergency call from home',
            'Official documentation work',
            'Network issue - need to visit ISP',
            'Vehicle breakdown - need to fix',
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($employees as $employee) {
            // Get today's assigned shift
            $assignedShift = EmployeeShift::where('emp_id', $employee->id)
                ->with('shift')
                ->latest('assigned_at')
                ->first();

            if (!$assignedShift || !$assignedShift->shift) {
                $skippedCount++;
                continue;
            }

            $shift = $assignedShift->shift;
            
            // Resolve shift date (handles cross-midnight)
            $shiftDate = $this->resolveShiftDate($assignedShift, $timezone);

            // Check if employee already has break requests for today
            $existingBreaks = EmployeeBreak::where('emp_id', $employee->id)
                ->where('shift_date', $shiftDate)
                ->whereNotNull('reason')
                ->count();

            // Skip if already has break requests (to avoid duplicates)
            if ($existingBreaks > 0) {
                continue;
            }

            // Create 1-3 break requests per employee randomly
            $numBreaks = rand(1, 3);

            for ($i = 0; $i < $numBreaks; $i++) {
                // Random reason
                $reason = $breakReasons[array_rand($breakReasons)];
                
                // Random time within shift hours
                $shiftStart = Carbon::parse($shift->start_time, $timezone);
                $shiftEnd = Carbon::parse($shift->end_time, $timezone);
                
                if ($shift->crosses_midnight && $shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }

                // Start time (within shift)
                $startTime = $shiftStart->copy()->addHours(rand(2, 6))->addMinutes(rand(0, 59));
                
                // End time (15-60 minutes after start)
                $endTime = $startTime->copy()->addMinutes(rand(15, 60));
                
                // Ensure end time is within shift
                if ($endTime->gt($shiftEnd)) {
                    $endTime = $shiftEnd->copy()->subMinutes(5);
                }

                // Random status (70% Completed, 30% Pending)
                $statuses = ['Completed', 'Completed', 'Completed', 'Pending'];
                $status = $statuses[array_rand($statuses)];

                // Type based on status
                // If Completed, randomly assign Official or General
                // If Pending, keep as General (needs approval)
                $type = 'General';
                if ($status === 'Completed') {
                    $type = rand(0, 1) ? 'Official' : 'General';
                }

                // Calculate spent minutes
                $spentMinutes = $startTime->diffInMinutes($endTime);

                // Create break request
                EmployeeBreak::create([
                    'emp_id' => $employee->id,
                    'shift_date' => $shiftDate,
                    'attendance_id' => null, // Can be linked if needed
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $status === 'Completed' ? $endTime->toDateTimeString() : null,
                    'type' => $type,
                    'status' => $status,
                    'reason' => $reason,
                    'spent_minutes' => $spentMinutes,
                    'remaining_minutes' => max(0, 30 - $spentMinutes), // Assuming 30 min allowed
                    'exceeded_minutes' => max(0, $spentMinutes - 30),
                    'created_at' => $startTime->copy()->subHours(rand(1, 5)), // Created some time before
                    'updated_at' => $status === 'Completed' ? $endTime : now(),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("✅ Created {$createdCount} break requests for " . count($employees) . " employees.");
        if ($skippedCount > 0) {
            $this->command->warn("⚠️  Skipped {$skippedCount} employees (no assigned shift).");
        }
    }

    /**
     * Resolve shift date for cross-midnight shifts
     */
    private function resolveShiftDate($employeeShift, $timezone)
    {
        if (!$employeeShift || !$employeeShift->shift) {
            return Carbon::today($timezone)->toDateString();
        }

        $shift = $employeeShift->shift;
        $now = Carbon::now($timezone);
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        $bufferMinutes = 180;

        $startToday = Carbon::parse("{$today->toDateString()} {$shift->start_time}", $timezone);
        $endToday = Carbon::parse("{$today->toDateString()} {$shift->end_time}", $timezone)->addMinutes($bufferMinutes);
        if ($shift->crosses_midnight && $endToday->lt($startToday)) {
            $endToday->addDay();
        }

        $startYesterday = Carbon::parse("{$yesterday->toDateString()} {$shift->start_time}", $timezone);
        $endYesterday = Carbon::parse("{$yesterday->toDateString()} {$shift->end_time}", $timezone)->addMinutes($bufferMinutes);
        if ($shift->crosses_midnight && $endYesterday->lt($startYesterday)) {
            $endYesterday->addDay();
        }

        if ($now->between($startYesterday, $endYesterday)) {
            return $yesterday->toDateString();
        }

        if ($now->between($startToday, $endToday)) {
            return $today->toDateString();
        }

        return $today->toDateString();
    }
}
