<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Events\LeaveApplied;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveController extends BaseController
{
    /**
     * Get Leave stats and balance for the authenticated employee.
     */
    public function getStats(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return $this->sendError('Employee record not found.', [], 404);
        }

        $stats = get_leave_stats($employee);

        return $this->sendResponse($stats, 'Leave stats fetched successfully.');
    }

    /**
     * Get Leave request history for the employee.
     */
    public function getHistory(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return $this->sendError('Employee record not found.', [], 404);
        }

        $leaves = Leave::with(['leaveType', 'approvals'])
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->sendResponse($leaves, 'Leave history fetched successfully.');
    }

    /**
     * Get active leave types.
     */
    public function getTypes()
    {
        $types = LeaveType::where('is_active', true)->get(['id', 'name', 'slug']);
        return $this->sendResponse($types, 'Leave types fetched successfully.');
    }

    /**
     * Submit a new leave request.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'day_type' => 'required|in:full_day,first_half,second_half',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $employee = Auth::user()->employee;
            if (!$employee) {
                return $this->sendError('Employee record not found.', [], 404);
            }

            $leave = DB::transaction(function () use ($request, $employee) {
                $leaveType = LeaveType::find($request->leave_type_id);
                return Leave::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $employee->shift_id,
                    'leave_type_id' => $request->leave_type_id,
                    'leave_type' => $leaveType->slug ?? strtolower($leaveType->name),
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'day_type' => $request->day_type,
                    'reason' => $request->reason,
                    'status' => 'Pending',
                ]);
            });

            // Dispatch Pusher Event for Admin Notification
            event(new LeaveApplied($leave, $employee->name));

            clear_employee_app_data_cache((int) $employee->id);

            return $this->sendResponse($leave, 'Leave request submitted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error: ' . $e->getMessage(), [], 500);
        }
    }
}
