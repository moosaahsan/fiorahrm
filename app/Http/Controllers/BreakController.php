<?php

namespace App\Http\Controllers;

use App\Events\BreakUpdated;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreakController extends Controller
{
    public function startBreak(Request $request)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Check for active break
        $activeBreak = EmployeeBreak::active()->where('emp_id', $employee->id)->first();
        if ($activeBreak) {
            return response()->json(['error' => 'An active break is already in progress'], 400);
        }

        // Create new break
        $break = EmployeeBreak::create([
            'emp_id' => $employee->id,
            'start_time' => now_with_timezone(),
            'type' => $request->input('type', 'General'),
            'status' => 'On break',
            'reason' => $request->input('reason'),
            'spent_minutes' => 0,
            'remaining_minutes' => 0,
            'exceeded_minutes' => 0,
        ]);

        // Fetch break data using helper function
        $shiftDate = web_resolve_shift_date(getTodayAssignedShift($employee->id));
        $breakData = get_clockouts_for_pusher($employee->id, $shiftDate);

        // Dispatch Pusher event
        \Log::info('Dispatching BreakUpdated event on start', ['employee_id' => $employee->id, 'break_data' => $breakData]);
        event(new BreakUpdated($employee->id, $breakData));

        return response()->json(['message' => 'Break started', 'break' => $break]);
    }

    public function endBreak(Request $request, $breakId)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $break = EmployeeBreak::where('emp_id', $employee->id)->findOrFail($breakId);
        if ($break->status === 'Ended') {
            return response()->json(['error' => 'Break already ended'], 400);
        }

        // End the break using the model's endBreak method
        $break->endBreak();

        // Fetch updated break data
        $shiftDate = web_resolve_shift_date(getTodayAssignedShift($employee->id));
        $breakData = get_clockouts_for_pusher($employee->id, $shiftDate);

        // Dispatch Pusher event
        \Log::info('Dispatching BreakUpdated event on end', ['employee_id' => $employee->id, 'break_data' => $breakData]);
        event(new BreakUpdated($employee->id, $breakData));

        return response()->json(['message' => 'Break ended', 'break' => $break]);
    }

    public function getActiveBreak(Request $request)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $activeBreak = EmployeeBreak::active()->where('emp_id', $employee->id)->first();
        return response()->json(['activeBreak' => $activeBreak]);
    }
}