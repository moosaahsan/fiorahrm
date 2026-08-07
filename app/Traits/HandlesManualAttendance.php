<?php

namespace App\Traits;

use App\Models\LateArrival;
use App\Models\HalfDay;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait HandlesManualAttendance
{
    protected function createLateRecord($attendance, $shift, $reason)
    {
        $tz = get_employee_settings($attendance->emp_id, 'time_zone') ?? 'Asia/Karachi';
        $shiftStart = Carbon::parse($attendance->shift_date->toDateString() . ' ' . $shift->start_time, $tz);
        
        LateArrival::updateOrCreate(
            ['attendance_id' => $attendance->id],
            [
                'emp_id' => $attendance->emp_id,
                'shift_id' => $shift->id,
                'date' => $attendance->shift_date,
                'scheduled_start' => $shiftStart,
                'actual_check_in' => $attendance->check_in,
                'late_minutes' => $attendance->late_duration,
                'late_reason' => $reason ?? 'Manual entry',
            ]
        );
    }

    protected function createHalfDayRecord($attendance, $shift, $reason)
    {
        // 1. Check if HalfDay record already exists for this date to avoid duplicate records/deductions
        $existsByDate = HalfDay::where('emp_id', $attendance->emp_id)
            ->whereDate('date', $attendance->shift_date)
            ->exists();
            
        if ($existsByDate) {
            Log::info('HalfDay record already exists for employee ' . $attendance->emp_id . ' on date ' . $attendance->shift_date);
            // We still updateOrCreate based on attendance_id just in case, but we return to avoid double leave deduction
            HalfDay::updateOrCreate(
                ['attendance_id' => $attendance->id],
                [
                    'emp_id' => $attendance->emp_id,
                    'shift_id' => $shift->id,
                    'date' => $attendance->shift_date,
                    'reason' => $reason ?? 'Manual half day entry',
                    'source' => 'manual',
                ]
            );
            return;
        }

        HalfDay::create([
            'attendance_id' => $attendance->id,
            'emp_id' => $attendance->emp_id,
            'shift_id' => $shift->id,
            'date' => $attendance->shift_date,
            'reason' => $reason ?? 'Manual half day entry',
            'source' => 'manual',
        ]);

        // 2. Determine day_type: Default to first_half (missed morning)
        $dayType = 'first_half';

        // 3. Check if a Leave record already exists for this date to avoid balance double-deduction
        $existingLeave = Leave::where('employee_id', $attendance->emp_id)
            ->whereDate('start_date', '<=', $attendance->shift_date->toDateString())
            ->whereDate('end_date', '>=', $attendance->shift_date->toDateString())
            ->first();

        if ($existingLeave) {
            Log::info('Leave record already exists on ' . $attendance->shift_date);
            $isSingleDay = Carbon::parse($existingLeave->start_date)->toDateString() === Carbon::parse($existingLeave->end_date)->toDateString();
            
            if ($isSingleDay && $existingLeave->status === 'Pending') {
                $existingLeave->status = 'Approved';
                $existingLeave->save();
                Log::info('Approved existing pending single-day leave on ' . $attendance->shift_date);
            }
            return;
        }

        // Perform deduction via Leave record (Observer handles balance)
        $leaveType = 'annual'; 

        Leave::create([
            'employee_id' => $attendance->emp_id,
            'shift_id' => $shift->id,
            'leave_type' => $leaveType,
            'start_date' => $attendance->shift_date,
            'end_date' => $attendance->shift_date,
            'status' => 'Approved',
            'reason' => 'Manual deduction: ' . ($reason ?? 'Half day mark'),
            'is_balance_deducted' => 1,
            'day_type' => $dayType,
        ]);
    }

    protected function handleAbsentDeduction($employeeId, $date, $shift, $reason)
    {
        $targetDate = Carbon::parse($date)->toDateString();
        Log::info('Initiating Absent Deduction', ['emp_id' => $employeeId, 'date' => $targetDate]);
        
        // Check if already deducted or requested to avoid double
        $existingLeave = Leave::where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->first();
            
        if ($existingLeave) {
            Log::info('Leave/Deduction already exists for ' . $targetDate);
            $isSingleDay = Carbon::parse($existingLeave->start_date)->toDateString() === Carbon::parse($existingLeave->end_date)->toDateString();
            
            if ($isSingleDay && $existingLeave->status === 'Pending') {
                $existingLeave->status = 'Approved';
                $existingLeave->save();
                Log::info('Approved existing pending single-day leave on ' . $targetDate);
            }
            return;
        }

        // Absent logic: Create leave record (Observer handles balance)
        $leaveType = 'annual'; 

        // Create a leave record for the absence
        Leave::create([
            'employee_id' => $employeeId,
            'shift_id' => $shift->id,
            'leave_type' => $leaveType,
            'start_date' => $date,
            'end_date' => $date,
            'status' => 'Approved',
            'reason' => 'Absent: ' . ($reason ?? 'Manual entry'),
            'is_balance_deducted' => 1,
            'day_type' => 'full_day',
        ]);
    }

    protected function handleUnpaidAbsentRecord($employeeId, $date, $shift, $reason)
    {
        $targetDate = Carbon::parse($date)->toDateString();
        Log::info('Initiating Unpaid Absent Record', ['emp_id' => $employeeId, 'date' => $targetDate]);
        
        // Check if already exists to avoid duplicate audit records
        $existingLeave = Leave::where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->first();
            
        if ($existingLeave) {
            Log::info('Leave/Deduction already exists for ' . $targetDate);
            $isSingleDay = Carbon::parse($existingLeave->start_date)->toDateString() === Carbon::parse($existingLeave->end_date)->toDateString();
            
            if ($isSingleDay && $existingLeave->status === 'Pending') {
                $existingLeave->status = 'Cancelled';
                $existingLeave->save();
                Log::info('Cancelled existing pending single-day leave on ' . $targetDate . ' due to Unpaid Absent');
            }
            return;
        }

        // Create a leave record for the unpaid absence without deducting balance
        Leave::create([
            'employee_id' => $employeeId,
            'shift_id' => $shift->id,
            'leave_type' => 'annual', 
            'start_date' => $date,
            'end_date' => $date,
            'status' => 'Rejected', // Rejected means it's unpaid/unapproved
            'reason' => 'Absent (Unpaid): ' . ($reason ?? 'Manual entry'),
            'is_balance_deducted' => 0, // IMPORTANT: No balance deducted
            'day_type' => 'full_day',
        ]);
    }

    protected function handlePresentRecord($employeeId, $date)
    {
        $targetDate = Carbon::parse($date)->toDateString();
        
        $existingLeave = Leave::where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->first();
            
        if ($existingLeave) {
            $isSingleDay = Carbon::parse($existingLeave->start_date)->toDateString() === Carbon::parse($existingLeave->end_date)->toDateString();
            
            if ($isSingleDay && $existingLeave->status === 'Pending') {
                Log::info('Cancelling pending single-day leave because employee is present on ' . $targetDate);
                $existingLeave->status = 'Cancelled';
                $existingLeave->reason = trim($existingLeave->reason . ' (Cancelled: Marked present on ' . $targetDate . ')');
                $existingLeave->save();
            }
        }
    }
}
