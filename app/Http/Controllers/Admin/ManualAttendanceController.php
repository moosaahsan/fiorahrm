<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\EmployeeShift;
use App\Models\LateArrival;
use App\Models\HalfDay;
use App\Models\Leave;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\HandlesManualAttendance;

class ManualAttendanceController extends Controller
{
    use HandlesManualAttendance;

    /**
     * Show manual attendance entry form.
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add-manual-attendance'), 403);
        $employees = Employee::accessible()->whereNull('resign_date')
            ->orderBy('name')
            ->get(['id', 'name', 'profile_pic']);
            
        $shifts = Shift::accessible()->where('is_active', 1)->get(['id', 'shift_name', 'start_time', 'end_time']);
        return view('admin.attendance.modals.manual_entry', compact('employees', 'shifts'));
    }

    public function edit($id)
    {
        abort_if(!auth()->user()->can('edit-attendance-logs'), 403);
        $attendance = Attendance::accessible()->with('employee')->findOrFail($id);
        $employees = Employee::accessible()->whereNull('resign_date')
            ->orderBy('name')
            ->get(['id', 'name', 'profile_pic']);
        
        $shifts = Shift::accessible()->where('is_active', 1)->get(['id', 'shift_name', 'start_time', 'end_time']);
            
        return view('admin.attendance.modals.manual_entry', compact('attendance', 'employees', 'shifts'));
    }

    /**
     * Store a manual attendance record.
     */
    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('add-manual-attendance'), 403);
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'shift_date' => 'required|date',
            'status' => 'required|in:Present,Absent,Half Day,Late,Absent (Unpaid)',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ]);

        $employeeId = $validated['employee_id'];
        $shiftId = $validated['shift_id'];
        $shiftDate = $validated['shift_date'];
        $status = $validated['status'];
        $tz = get_employee_settings($employeeId, 'time_zone') ?? 'Asia/Karachi';

        // Check for existing record
        $existing = Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $shiftDate)
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Attendance record already exists for this date.'], 422);
        }

        $shift = Shift::find($shiftId);

        DB::beginTransaction();
        try {
            $attendance = new Attendance();
            $attendance->emp_id = $employeeId;
            $attendance->shift_id = $shift->id;
            $attendance->shift_date = $shiftDate;
            $attendance->status = ($status === 'Absent' || $status === 'Absent (Unpaid)') ? 'Absent' : 'Present';
            $attendance->is_manual = true;
            $attendance->modified_by = auth()->id();

            if ($status !== 'Absent' && $status !== 'Absent (Unpaid)') {
                if ($validated['check_in']) {
                    $attendance->check_in = Carbon::parse($shiftDate . ' ' . $validated['check_in'], $tz);
                }
                if ($validated['check_out']) {
                    $attendance->check_out = Carbon::parse($shiftDate . ' ' . $validated['check_out'], $tz);
                    // Handle overnight shift checkout if check_out is before check_in time
                    if ($attendance->check_in && $attendance->check_out && $attendance->check_out->lt($attendance->check_in)) {
                        $attendance->check_out->addDay();
                    }
                }
            } else {
                $attendance->check_in = null;
                $attendance->check_out = null;
            }

            // 🕒 Intelligence Late Calculation (Grace-Period Aware)
            $gracePeriod = (int) (get_employee_settings($employeeId, 'late_grace_minutes') ?? 5);
            $halfDayThreshold = (int) (get_employee_settings($employeeId, 'mark_half_day_after') ?? 120);

            if (($status === 'Late' || $status === 'Present') && $validated['check_in']) {
                $shiftStart = Carbon::parse($shiftDate . ' ' . $shift->start_time, $tz);
                $checkIn = $attendance->check_in;
                
                // Compare check-in with shift start + grace period
                if ($checkIn->gt($shiftStart->copy()->addMinutes($gracePeriod))) {
                    $attendance->late_duration = $shiftStart->diffInMinutes($checkIn);
                } else {
                    $attendance->late_duration = 0;
                }
            }

            $attendance->save();

            // Handle Specific Statuses (Late, Half Day, Absent)
            // Note: If lateMinutes >= threshold, it should ideally be Half Day, 
            // but we follow the user's manual selection if they chose "Late".
            if ($status === 'Late' && $attendance->late_duration > 0) {
                $this->createLateRecord($attendance, $shift, $validated['reason']);
            } elseif ($status === 'Half Day') {
                $this->createHalfDayRecord($attendance, $shift, $validated['reason']);
            } elseif ($status === 'Absent') {
                $this->handleAbsentDeduction($employeeId, $shiftDate, $shift, $validated['reason']);
            } elseif ($status === 'Absent (Unpaid)') {
                $this->handleUnpaidAbsentRecord($employeeId, $shiftDate, $shift, $validated['reason']);
            }

            if ($status === 'Present' || $status === 'Late') {
                $this->handlePresentRecord($employeeId, $shiftDate);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Manual attendance record created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual attendance store failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save record: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing attendance record manually.
     */
    public function update(Request $request, $id)
    {
        abort_if(!auth()->user()->can('edit-attendance-logs'), 403);
        $attendance = Attendance::accessible()->findOrFail($id);
        $validated = $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'status' => 'required|in:Present,Absent,Half Day,Late,Absent (Unpaid)',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ]);

        $status = $validated['status'];
        $tz = get_employee_settings($attendance->emp_id, 'time_zone') ?? 'Asia/Karachi';
        $shift = $attendance->shift;

        DB::beginTransaction();
        try {
            // 🔄 SMART REVERSAL LOGIC
            // If the old status was Absent or Leave, revert the full day deduction
            $oldStatus = trim(strtolower($attendance->status));
            if (in_array($oldStatus, ['absent', 'leave'])) {
                $this->revertDeduction($attendance, 'full_day');
            }
            
            // If the old status was Half Day, we need to check if a deduction record exists
            // Since HalfDay records are deleted below, we check there too.
            $hadHalfDay = HalfDay::where('attendance_id', $attendance->id)->exists();
            if ($hadHalfDay) {
                // Find any half-day leave on this date to revert
                $halfDayLeave = \App\Models\Leave::where('employee_id', $attendance->emp_id)
                    ->whereDate('start_date', $attendance->shift_date->toDateString())
                    ->whereIn('day_type', ['first_half', 'second_half'])
                    ->first();
                
                if ($halfDayLeave) {
                    $this->revertDeduction($attendance, $halfDayLeave->day_type);
                }
            }

            // Clean up old auxiliary records
            LateArrival::where('attendance_id', $attendance->id)->delete();
            HalfDay::where('attendance_id', $attendance->id)->delete();

            $attendance->status = ($status === 'Absent' || $status === 'Absent (Unpaid)') ? 'Absent' : 'Present';
            $attendance->is_manual = true;
            $attendance->modified_by = auth()->id();

            if ($status !== 'Absent' && $status !== 'Absent (Unpaid)') {
                if ($validated['check_in']) {
                    $attendance->check_in = Carbon::parse($attendance->shift_date->toDateString() . ' ' . $validated['check_in'], $tz);
                }
                if ($validated['check_out']) {
                    $attendance->check_out = Carbon::parse($attendance->shift_date->toDateString() . ' ' . $validated['check_out'], $tz);
                    if ($attendance->check_in && $attendance->check_out->lt($attendance->check_in)) {
                        $attendance->check_out->addDay();
                    }
                }
            } else {
                $attendance->check_in = null;
                $attendance->check_out = null;
            }

            // 🕒 Intelligence Late Calculation (Grace-Period Aware)
            $gracePeriod = (int) (get_employee_settings($attendance->emp_id, 'late_grace_minutes') ?? 5);
            
            if (($status === 'Late' || $status === 'Present') && $attendance->check_in) {
                $shiftStart = Carbon::parse($attendance->shift_date->toDateString() . ' ' . $shift->start_time, $tz);
                if ($attendance->check_in->gt($shiftStart->copy()->addMinutes($gracePeriod))) {
                    $attendance->late_duration = $shiftStart->diffInMinutes($attendance->check_in);
                } else {
                    $attendance->late_duration = 0;
                }
            } else {
                $attendance->late_duration = 0;
            }

            $attendance->save();

            // Re-create auxiliary records based on new status
            if ($status === 'Late' && $attendance->late_duration > 0) {
                $this->createLateRecord($attendance, $shift, $validated['reason']);
            } elseif ($status === 'Half Day') {
                $this->createHalfDayRecord($attendance, $shift, $validated['reason']);
            } elseif ($status === 'Absent') {
                $this->handleAbsentDeduction($attendance->emp_id, $attendance->shift_date->toDateString(), $shift, $validated['reason']);
            } elseif ($status === 'Absent (Unpaid)') {
                $this->handleUnpaidAbsentRecord($attendance->emp_id, $attendance->shift_date->toDateString(), $shift, $validated['reason']);
            }

            if ($status === 'Present' || $status === 'Late') {
                $this->handlePresentRecord($attendance->emp_id, $attendance->shift_date->toDateString());
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Attendance record updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual attendance update failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update record: ' . $e->getMessage()], 500);
        }
    }

    private function revertDeduction($attendance, $dayType)
    {
        $shiftDate = Carbon::parse($attendance->shift_date)->toDateString();
        
        Log::info('Initiating Leave Reversal', [
            'employee_id' => $attendance->emp_id,
            'shift_date' => $shiftDate,
            'day_type' => $dayType
        ]);

        // Find the leave record
        $leave = \App\Models\Leave::where('employee_id', $attendance->emp_id)
            ->whereDate('start_date', '<=', $shiftDate)
            ->whereDate('end_date', '>=', $shiftDate)
            ->first();

        if ($leave) {
            Log::info('Leave record found', ['leave_id' => $leave->id, 'day_type' => $leave->day_type]);
            
            $isAutoGenerated = str_starts_with($leave->reason, 'Absent:') || 
                               str_starts_with($leave->reason, 'Absent (Unpaid):') || 
                               str_starts_with($leave->reason, 'Manual deduction:');

            if ($isAutoGenerated) {
                // LeaveObserver@deleting will handle the balance restoration automatically
                $leave->delete();
                Log::info('Auto-generated leave record deleted after reversal');
            } else {
                $isSingleDay = Carbon::parse($leave->start_date)->toDateString() === Carbon::parse($leave->end_date)->toDateString();
                if ($isSingleDay) {
                    $leave->status = 'Cancelled';
                    $leave->save();
                    Log::info('User requested single-day leave marked as cancelled after reversal');
                } else {
                    Log::info('User requested multi-day leave ignored during reversal');
                }
            }
        } else {
            Log::warning('No leave record found to revert on date ' . $shiftDate . ' for type ' . $dayType);
        }
    }
    public function getShiftOnDate(Request $request, $id)
    {
        $date = $request->shift_date ?? date('Y-m-d');
        
        // 1. Check for direct shift assignment on or before this date
        $assignment = \App\Models\EmployeeShift::where('emp_id', $id)
            ->whereDate('assigned_at', '<=', $date)
            ->orderBy('assigned_at', 'desc')
            ->first();

        if ($assignment) {
            return response()->json([
                'success' => true,
                'shift' => $assignment->shift,
                'source' => 'Direct Assignment'
            ]);
        }

        // 2. If no direct assignment, try to get from team or department (if implemented there)
        // For now, return the first active shift as fallback or null
        $fallback = Shift::where('is_active', 1)->first();
        
        return response()->json([
            'success' => true,
            'shift' => $fallback,
            'source' => 'System Default'
        ]);
    }
}
