<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BreakService;
use Illuminate\Http\Request;
use App\Models\EmployeeBreak;
use App\Models\Employee;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeBreakController extends Controller
{
    protected $breakService;

    public function __construct(BreakService $breakService)
    {
        $this->breakService = $breakService;
    }

    // View all employee breaks for today (or specific date)
    public function index(Request $request)
    {
        $date = $request->get('date'); // Optional date filter
        $breaks = $this->breakService->getAllEmployeeBreaks($date);

        return response()->json($breaks);
    }

    // View specific employee's breaks
    public function show(Request $request, $empId)
    {
        $date = $request->get('date');
        $breaks = $this->breakService->getEmployeeBreaks($empId, $date);

        return response()->json($breaks);
    }

    /**
     * Break Requests - Show pending break requests for today's assigned shifts
     */
    public function breakRequests(Request $request)
    {
        $this->authorize('view-breaks');
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';

        // Filters
        $selectedDate = $request->get('date', Carbon::today($timezone)->toDateString());
        $selectedStatus = $request->get('status', 'Pending'); // Pending, Approved, Rejected, All

        // Stats are always for the selected date
        $allBreaksOnDate = EmployeeBreak::accessible()
            ->whereDate('shift_date', $selectedDate)
            ->get();

        $activeCount = $allBreaksOnDate->whereIn('status', ['On Break', 'Ongoing', 'On break'])->whereNull('end_time')->count();
        $totalMinutes = $allBreaksOnDate->where('status', 'Completed')->sum('spent_minutes');
        $pendingCount = $allBreaksOnDate->where('type', 'Official')->whereNotIn('status', ['Approved', 'Rejected'])->count();

        // Main Query for the table
        $query = EmployeeBreak::accessible()
            ->with(['employee', 'approvedBy'])
            ->whereDate('shift_date', $selectedDate);

        if ($selectedStatus === 'Pending') {
            $query->where('type', 'Official')->whereNotIn('status', ['Approved', 'Rejected']);
        } elseif ($selectedStatus === 'Approved') {
            $query->where('status', 'Approved');
        } elseif ($selectedStatus === 'Rejected') {
            $query->where('status', 'Rejected');
        } elseif ($selectedStatus === 'Live') {
            $query->whereIn('status', ['On Break', 'Ongoing', 'On break'])->whereNull('end_time');
        } elseif ($selectedStatus === 'All') {
            // Full Audit: Only show decided breaks (Approved or Rejected) as requested
            $query->whereIn('status', ['Approved', 'Rejected']);
        }

        $breaks = $query->orderBy('created_at', 'desc')->get();

        // Transform for view compatibility if needed, or just pass the objects
        $officialRequests = [];
        foreach ($breaks as $break) {
            $officialRequests[] = [
                'id' => $break->id,
                'employee_id' => $break->employee->id,
                'employee_name' => $break->employee->name,
                'profile_pic_url' => $break->employee->profilePicUrl,
                'shift_name' => $break->employee->currentShiftAssignment?->shift?->shift_name ?? 'N/A',
                'shift_date' => $break->shift_date ?? $break->created_at->toDateString(),
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
                'reason' => $break->reason,
                'type' => $break->type ?? 'General',
                'status' => $break->status,
                'duration' => $break->spent_minutes,
                'created_at' => $break->created_at,
                'approved_by_name' => $break->approvedBy?->name ?? null,
            ];
        }

        $currentEmployeeId = auth()->user()->employee->id ?? null;

        return view('admin.break_requests.index', [
            'officialRequests' => $officialRequests,
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'totalMinutes' => round($totalMinutes),
            'selectedDate' => $selectedDate,
            'selectedStatus' => $selectedStatus,
            'employees' => Employee::accessible()->whereNull('resign_date')->orderBy('name')->get(),
            'currentEmployeeId' => $currentEmployeeId,
        ]);
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

        $bufferMinutes = 180; // 3 hours grace

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

    /**
     * Check if shift date belongs to today's shift window
     */
    private function isTodayShift($employeeShift, $shiftDate, $timezone)
    {
        $today = Carbon::today($timezone);
        $shiftDateCarbon = Carbon::parse($shiftDate, $timezone);

        // If shift crosses midnight, check if it's today or yesterday
        if ($employeeShift->shift->crosses_midnight) {
            return $shiftDateCarbon->isSameDay($today) || $shiftDateCarbon->isSameDay($today->copy()->subDay());
        }

        return $shiftDateCarbon->isSameDay($today);
    }

    /**
     * Approve break request - Mark as Official and Approved
     */
    public function approve($id)
    {
        $this->authorize('approve-break');
        try {
            $break = EmployeeBreak::accessible()->findOrFail($id);

            // Prevent HR from approving their own break
            $currentEmployeeId = auth()->user()->employee->id ?? null;
            if ($currentEmployeeId && $break->emp_id == $currentEmployeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot approve your own break request.'
                ], 403);
            }

            $break->type = 'Official';
            $break->status = 'Approved';
            $break->approved_by = auth()->id();
            $break->save();

            return response()->json([
                'success' => true,
                'message' => 'Break request approved successfully.',
                'status' => 'approved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve break request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject break request - Mark as General
     */
    public function reject($id)
    {
        $this->authorize('reject-break');
        try {
            $break = EmployeeBreak::accessible()->findOrFail($id);

            // Prevent HR from rejecting their own break
            $currentEmployeeId = auth()->user()->employee->id ?? null;
            if ($currentEmployeeId && $break->emp_id == $currentEmployeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot reject your own break request.'
                ], 403);
            }

            $break->type = 'General';
            $break->status = 'Rejected';
            $break->approved_by = auth()->id();
            $break->save();

            return response()->json([
                'success' => true,
                'message' => 'Break request rejected.',
                'status' => 'rejected'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject break request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a manually entered break.
     */
    public function storeManual(Request $request)
    {
        $this->authorize('view-breaks');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_date' => 'required|date',
            'type' => 'required|in:Official,General',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ]);

        $employeeId = $validated['employee_id'];
        $shiftDate = $validated['shift_date'];
        $type = $validated['type'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $reason = $validated['reason'];

        $tz = (function_exists('get_employee_settings') ? get_employee_settings($employeeId, 'time_zone') : null) 
              ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

        $startTimeCarbon = Carbon::parse($shiftDate . ' ' . $startTime, $tz);
        $endTimeCarbon = Carbon::parse($shiftDate . ' ' . $endTime, $tz);

        if ($endTimeCarbon->lt($startTimeCarbon)) {
            $endTimeCarbon->addDay();
        }

        $spentMinutes = (int) $startTimeCarbon->diffInMinutes($endTimeCarbon);

        // Fetch corresponding attendance record if exists
        $attendance = \App\Models\Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $shiftDate)
            ->first();

        // Calculate allowed break minutes and accumulated break times
        $allowed = get_effective_break_minutes((int) $employeeId, $shiftDate);
        
        $todaySpent = EmployeeBreak::where('emp_id', $employeeId)
            ->where('shift_date', $shiftDate)
            ->whereIn('status', ['Completed', 'Approved'])
            ->sum('spent_minutes') + $spentMinutes;

        $remainingMinutes = max($allowed - $todaySpent, 0);
        $exceededMinutes = max($todaySpent - $allowed, 0);

        try {
            $employeeBreak = new EmployeeBreak();
            $employeeBreak->emp_id = $employeeId;
            $employeeBreak->attendance_id = $attendance?->id;
            $employeeBreak->shift_date = $shiftDate;
            $employeeBreak->start_time = $startTimeCarbon;
            $employeeBreak->end_time = $endTimeCarbon;
            $employeeBreak->type = $type;
            $employeeBreak->status = $type === 'Official' ? 'Approved' : 'Completed';
            $employeeBreak->reason = $reason;
            $employeeBreak->spent_minutes = $spentMinutes;
            $employeeBreak->remaining_minutes = $remainingMinutes;
            $employeeBreak->exceeded_minutes = $exceededMinutes;
            if ($type === 'Official') {
                $employeeBreak->approved_by = auth()->id();
            }
            $employeeBreak->save();

            return response()->json([
                'success' => true,
                'message' => 'Manual break logged successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save manual break: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Instantly start a break for an employee RIGHT NOW (admin one-click)
     */
    public function instantStart(Request $request)
    {
        $this->authorize('view-breaks');

        $request->validate(['employee_id' => 'required|exists:employees,id']);

        $employeeId = $request->employee_id;
        $timezone   = (function_exists('get_employee_settings') ? get_employee_settings($employeeId, 'time_zone') : null)
                      ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

        // Resolve shift date
        $employeeShift = \App\Models\EmployeeShift::with('shift')
            ->where('emp_id', $employeeId)
            ->latest('assigned_at')
            ->first();

        $shiftDate = $employeeShift ? web_resolve_shift_date($employeeShift) : Carbon::today($timezone)->toDateString();

        // Check if already on break
        $existing = EmployeeBreak::where('emp_id', $employeeId)
            ->whereIn('status', ['On Break', 'On break', 'Ongoing'])
            ->whereNull('end_time')
            ->first();

        if ($existing) {
            return response()->json([
                'success'  => false,
                'message'  => 'Employee is already on break.',
                'break_id' => $existing->id,
            ], 409);
        }

        // Get attendance record
        $attendance = \App\Models\Attendance::where('emp_id', $employeeId)
            ->where('shift_date', $shiftDate)
            ->first();

        $now = Carbon::now($timezone);

        $break              = new EmployeeBreak();
        $break->emp_id      = $employeeId;
        $break->attendance_id = $attendance?->id;
        $break->shift_date  = $shiftDate;
        $break->start_time  = $now;
        $break->status      = 'On Break';
        $break->type        = 'General';
        $break->reason      = 'Marked by admin';
        $break->spent_minutes    = 0;
        $break->remaining_minutes = 0;
        $break->exceeded_minutes  = 0;
        $break->approved_by = auth()->id();
        $break->save();

        return response()->json([
            'success'    => true,
            'message'    => 'Break started for employee.',
            'break_id'   => $break->id,
            'start_time' => $now->format('H:i'),
        ]);
    }

    /**
     * Instantly end an active break for an employee (admin one-click)
     */
    public function instantEnd(Request $request)
    {
        $this->authorize('view-breaks');

        $request->validate(['employee_id' => 'required|exists:employees,id']);

        $employeeId = $request->employee_id;
        $timezone   = (function_exists('get_employee_settings') ? get_employee_settings($employeeId, 'time_zone') : null)
                      ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

        $break = EmployeeBreak::where('emp_id', $employeeId)
            ->whereIn('status', ['On Break', 'On break', 'Ongoing'])
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if (!$break) {
            return response()->json([
                'success' => false,
                'message' => 'No active break found for this employee.',
            ], 404);
        }

        $break->endBreak(['end_time' => Carbon::now($timezone)]);

        return response()->json([
            'success'      => true,
            'message'      => 'Break ended for employee.',
            'spent_minutes' => $break->spent_minutes,
        ]);
    }

}
