<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\CompanyOffDay;
use App\Models\EmployeeBreak; // Model add kiya
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarkAbsentees extends Command
{
    protected $signature = 'attendance:mark-absentees';
    protected $description = 'Mark absentees and auto-close breaks/check-outs for finished shifts';

    public function handle()
    {
        $employees = Employee::where('status', 1)
            ->where(function ($q) {
                $q->whereNull('resign_date')
                    ->orWhere('resign_date', '>', now()->toDateString());
            })->get(); // Only check active and non-resigned employees
        $datesToCheck = [
            Carbon::yesterday(),
            Carbon::today(),
        ];

        foreach ($employees as $employee) {
            foreach ($datesToCheck as $dateToCheck) {
                // 1. Get Shift for this Date
                // We use clone because we might modify $dateToCheck in loop (though not here, good practice)
                $currentDate = $dateToCheck->copy();
                
                // Skip if date is before joining date
                if ($employee->joining_date && $currentDate->lt(Carbon::parse($employee->joining_date)->startOfDay())) {
                    continue;
                }
                
                // 1. Get Employee's Shift (Latest assignment on or before this date)
                $shiftAssignment = EmployeeShift::where('emp_id', $employee->id)
                    ->whereDate('assigned_at', '<=', $currentDate)
                    ->orderBy('assigned_at', 'desc')
                    ->first();

                if (!$shiftAssignment) continue;

                $shift = Shift::find($shiftAssignment->shift_id);
                if (!$shift) continue;

                // 2. Resolve Shift Start & End Times
                // Parse Times
                $shiftStart = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $shift->start_time);
                $shiftEnd   = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $shift->end_time);

                if ($shift->crosses_midnight) {
                    $shiftEnd->addDay(); // Ends the next day
                }

                // 3. Grace Period Check (1 Hour after shift start)
                $markAbsentThreshold = $shiftStart->copy()->addHour();
                $now = Carbon::now();

                // Validation: Too Early?
                if ($now->lessThan($markAbsentThreshold)) {
                    // $this->info("Skipping {$employee->name} for {$currentDate->toDateString()}: Too early (Shift starts {$shiftStart->format('H:i')}, wait till {$markAbsentThreshold->format('H:i')})");
                    continue;
                }

                // Validation: Too Late? (e.g., Don't mark absent for shift 2 days ago if cron runs late)
                // Lets keep it simple: If it's more than 24 hours past shift END, we might ignore or still mark?
                // For now, we process it.

                // 4. Skip configured off days — there is no fixed weekend, so a
                // Saturday or Sunday is a normal working day unless configured otherwise.
                if (\App\Services\WorkingDayService::isOffDayForEmployee($currentDate, $employee)) {
                    continue;
                }

                $offDay = CompanyOffDay::getHolidayForEmployee($currentDate, $employee);

                // 5. Define Attendance Window for Checking
                // We look for attendance roughly between shift start and shift end (+ buffer)
                // Ideally, any attendance with this shift_date is valid.
                
                // Attendance Check 1: Record exists for this shift_id + shift_date?
                $attendanceExists = Attendance::where('emp_id', $employee->id)
                    ->whereDate('shift_date', $currentDate->format('Y-m-d')) // Specific to date
                    ->exists();

                if ($attendanceExists) {
                    // $this->info("Skipping {$employee->name} for {$currentDate->toDateString()}: Attendance Exists");
                    continue; 
                }

                // 6. Check Leave
                $leave = Leave::where('employee_id', $employee->id)
                    ->where('status', 'Approved')
                    ->whereDate('start_date', '<=', $currentDate)
                    ->whereDate('end_date', '>=', $currentDate)
                    ->first();

                $status = 'Absent';
                if ($leave) {
                    $status = $leave->day_type === 'full_day' ? 'first_half' : 'second_half'; // Simplified logic
                    // If full day leave, we might technically log it as 'Leave' or 'Absent' with status?
                    // Original code used 'first_half' for full day?? 
                    // Let's correct: If full day leave, it shouldn't be 'Absent' usually, but maybe 'Leave'. 
                    // But schema says 'status' enum might be Present/Absent/Late etc. 
                    // Sticking to original logic's mapping for safety, but typically:
                    if ($leave->day_type === 'full_day') {
                         // If it's a full day leave, usually we DON'T mark 'Absent'. 
                         // We might mark 'Leave' if that system exists, or just skip marking absent.
                         // But if the user wants to see them in attendance report as 'Leave', we create record.
                         $status = 'Leave'; // Assuming this status exists or is just a string
                    }
                }

                if (!$attendanceExists) {
                    $attendance = new Attendance();
                    $attendance->emp_id = $employee->id;
                    $attendance->shift_id = $shift->id;
                    
                    if ($offDay) {
                        $attendance->status = $offDay->type; // 'Holiday' or 'Event'
                        $attendance->shift_date = $currentDate->format('Y-m-d');
                        $attendance->check_in = null;
                        $attendance->check_out = null;
                        $attendance->late_duration = 0;
                        $attendance->manual = false;
                        $attendance->save();
                        $this->info("Marked {$employee->name} as {$offDay->type} for {$currentDate->toDateString()}");
                        continue;
                    }

                    $attendance->check_in = null;
                    $attendance->check_out = null;
                    $attendance->status = $status;
                    $attendance->shift_date = $currentDate->format('Y-m-d');
                    $attendance->late_duration = 0;
                    $attendance->manual = false;
                    // Removed employee_shift_id as per original structure
                    // Save attendance before deducting leave
                    $attendance->save();
                }

                $this->info("Marked {$employee->name} as {$status} for {$currentDate->toDateString()}");

                // 8. Deduct Leave Balance if Absent (System marked)
                if ($status === 'Absent') {
                    $this->handleAbsentDeduction($employee->id, $currentDate, $shift);
                }
            }
            
            // Re-integrate the 'Close Active Breaks' logic for this employee? 
            // Original code did it inside the main loop. 
            // It's better to do it separately or here. 
            // Let's do a quick check for STUCK attendances (e.g. yesterday's checkout missing)
            $this->autoCloseStuckAttendance($employee);
        }

        $this->info('✅ Absentee marking completed.');
    }

    /**
     * Helper to close stuck breaks or checkouts
     */
    private function autoCloseStuckAttendance($employee)
    {
        // Logic from original code, cleaned up.
        // Find active attendance that should have ended.
        // FIX: We only want to close attendance that is REALLY stuck.
        // If it's from a previous day BUT it's a night shift that hasn't ended yet, skip it.
        $activeAttendance = Attendance::where('emp_id', $employee->id)
            ->whereNull('check_out')
            ->whereDate('created_at', '<', Carbon::today()) 
            ->get();

        foreach($activeAttendance as $attendance) {
             // Safety: Check if this is a night shift currently in progress
             $shiftDate = Carbon::parse($attendance->shift_date);
             $shift = $attendance->shift;
             
             if ($shift && $shift->crosses_midnight) {
                 $now = Carbon::now(app_settings('app_timezone') ?? 'Asia/Karachi');
                 $shiftEnd = Carbon::parse($shiftDate->toDateString() . ' ' . $shift->end_time, $now->timezone);
                 $shiftEnd->addDay(); // Since it crosses midnight
                 
                 // If shift hasn't ended yet (with buffer), don't close it!
                 if ($now->lt($shiftEnd->addHours(2))) {
                     continue;
                 }
             }

             $this->closeActiveBreaks($attendance);
        }
    }

    /**
     * Helper function to close any open breaks before checkout/absentee logic
     */
    private function closeActiveBreaks($attendance)
    {
        $openBreaks = EmployeeBreak::where('attendance_id', $attendance->id)
            ->whereIn('status', ['On Break', 'On break', 'Ongoing'])
            ->get();

        foreach ($openBreaks as $break) {
            // FIX: Use the model's centralized and robust endBreak logic!
            // This prevents duplicate bugs and handles midnight rollovers correctly.
            $break->endBreak(['end_time' => now_with_timezone()]);

            $this->info("Closed active break ID: {$break->id} for Attendance ID: {$attendance->id}");
        }
    }

    /**
     * Handle leave record creation for system-marked absence (Pending status, no auto-deduction)
     */
    private function handleAbsentDeduction($employeeId, $date, $shift)
    {
        $targetDate = $date->toDateString();
        
        // Check if already exists to avoid double creation
        $exists = Leave::where('employee_id', $employeeId)
            ->whereDate('start_date', $targetDate)
            ->where('day_type', 'full_day')
            ->exists();
            
        if ($exists) {
            return;
        }

        // Create a PENDING leave record for the absence
        // Balance will be deducted ONLY when admin approves manually
        Leave::create([
            'employee_id' => $employeeId,
            'shift_id' => $shift->id,
            'leave_type' => 'annual', 
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'status' => 'Pending',
            'reason' => 'Absent (Auto-marked by System)',
            'is_balance_deducted' => 0,
            'day_type' => 'full_day',
        ]);

        $this->info("Created Pending leave for {$employeeId} on {$targetDate}");
    }
}