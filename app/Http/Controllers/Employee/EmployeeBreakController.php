<?php

namespace App\Http\Controllers\Employee;

use App\Events\BreakUpdated;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeeBreak;
use App\Models\Attendance;
use App\Models\AppSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class EmployeeBreakController extends BaseController
{
    public function index(Request $request)
    {
        $employeeId = Auth::user()->employee->id;
        $assignedShift = $this->getTodayAssignedShift($employeeId);

        if (!$assignedShift) {
            return redirect()->back()->with('error', 'No assigned shift found for today.');
        }

        $shiftDate = resolve_shift_date($assignedShift);
        \Log::info('EmployeeBreakController@index Resolved shiftDate', ['shiftDate' => $shiftDate]);

        $currentBreak = EmployeeBreak::where('emp_id', $employeeId)
            ->where('status', 'On Break')
            ->where('shift_date', $shiftDate)
            ->first();

        // Filters
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $selectedDate = $request->get('date', Carbon::today($timezone)->toDateString());
        $selectedStatus = $request->get('status', 'All');

        $allBreaksOnDate = EmployeeBreak::where('emp_id', $employeeId)
            ->whereDate('shift_date', $selectedDate)
            ->get();

        $activeCount = $allBreaksOnDate->whereIn('status', ['On Break', 'Ongoing', 'On break'])->whereNull('end_time')->count();
        $totalMinutes = $allBreaksOnDate->where('status', 'Completed')->sum('spent_minutes');
        $pendingCount = $allBreaksOnDate->where('type', 'Official')->whereNotIn('status', ['Approved', 'Rejected'])->count();

        $query = EmployeeBreak::where('emp_id', $employeeId)
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
            // Full Audit
        }

        $breaks = $query->orderBy('created_at', 'desc')->get();
        $officialRequests = [];
        foreach ($breaks as $break) {
            $officialRequests[] = [
                'id' => $break->id,
                'employee_id' => $break->employee?->id ?? $employeeId,
                'employee_name' => $break->employee?->name ?? 'Unknown',
                'profile_pic_url' => $break->employee?->profile_pic ?? null,
                'shift_name' => $break->employee?->currentShiftAssignment?->shift?->shift_name ?? 'N/A',
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

        ActivityLogger::log('view', 'EmployeeBreak', ActivityLogger::format('view', 'EmployeeBreak', 'All Records', 'Listing'));

        return view('employee.breaks.index', [
            'currentBreak' => $currentBreak,
            'officialRequests' => $officialRequests,
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'totalMinutes' => round($totalMinutes),
            'selectedDate' => $selectedDate,
            'selectedStatus' => $selectedStatus,
            'employees' => [], // Empty string for employee because we don't dynamically select them
            'currentEmployeeId' => Auth::user()->employee->id,
        ]);
    }

    public function data(Request $request)
    {
        $employeeId = Auth::user()->employee->id;
        if (!$employeeId)
            return DataTables::of([])->make(true);

        $assignedShift = $this->getTodayAssignedShift($employeeId);
        if (!$assignedShift) {
            return DataTables::of([])->with('error', 'No assigned shift found for today.')->make(true);
        }

        $shiftDate = getTodayShiftDate($assignedShift);

        $query = EmployeeBreak::where('emp_id', $employeeId)
            ->whereDate('start_time', $shiftDate)
            ->whereDate('created_at', $shiftDate)
            ->select(['id', 'start_time', 'end_time', 'status', 'reason', 'type']);

        if ($request->filled('type')) {
            $query->where('type', ucfirst(strtolower($request->type)));
        }

        $dataTable = DataTables::of($query)
            ->editColumn('start_time', fn($row) => $row->start_time ? Carbon::parse($row->start_time)->format('d M, Y H:i') : '-')
            ->editColumn('end_time', fn($row) => $row->end_time ? Carbon::parse($row->end_time)->format('d M, Y H:i') : '-')
            ->editColumn('type', fn($row) => ucfirst($row->type))
            ->addColumn('action', function ($row) {
                if ($row->status === 'On Break') {
                    return '<button class="btn btn-success btn-sm end-break-btn" data-url="' . route('employee.break.end', $row->id) . '">End Break</button>';
                }
                return '';
            })
            ->rawColumns(['action']);

        return $dataTable->make(true);
    }
    // public function startBreak(Request $request)
    // {
    //     try {
    //         $employeeId = Auth::user()->employee->id;

    //         $request->validate([
    //             'break_type' => 'required|in:general,official',
    //             'reason' => 'required_if:break_type,official|nullable|string|max:255',
    //         ]);

    //         $assignedShift = $this->getTodayAssignedShift($employeeId);
    //         if (!$assignedShift) {
    //             return $request->ajax()
    //                 ? response()->json(['success' => false, 'message' => 'No assigned shift found for today.'], 400)
    //                 : redirect()->back()->with('error', 'No assigned shift found for today.');
    //         }

    //         $shiftDate = getTodayShiftDate($assignedShift);

    //         $attendance = Attendance::where('emp_id', $employeeId)
    //             ->whereDate('shift_date', $shiftDate)
    //             ->latest()
    //             ->first();

    //         if (!$attendance) {
    //             return $request->ajax()
    //                 ? response()->json(['success' => false, 'message' => 'No attendance record found for today.'], 400)
    //                 : redirect()->back()->with('error', 'No attendance record found for today.');
    //         }

    //         $existingBreak = EmployeeBreak::where('emp_id', $employeeId)
    //             ->where('status', 'On Break')
    //             ->whereDate('created_at', $shiftDate)
    //             ->first();

    //         if ($existingBreak) {
    //             return $request->ajax()
    //                 ? response()->json(['success' => false, 'message' => 'Already on break.'], 400)
    //                 : redirect()->back()->with('error', 'Already on break.');
    //         }

    //         $employeeBreak = new EmployeeBreak();
    //         $employeeBreak->attendance_id = $attendance->id;
    //         $employeeBreak->emp_id = $employeeId;
    //         $employeeBreak->start_time = now_with_timezone();
    //         $employeeBreak->status = 'On Break';
    //         $employeeBreak->type = ucfirst(strtolower($request->input('break_type')));
    //         $employeeBreak->reason = $request->input('reason');
    //         $employeeBreak->spent_minutes = 0;
    //         $employeeBreak->remaining_minutes = 0;
    //         $employeeBreak->exceeded_minutes = 0;
    //         $employeeBreak->save();

    //         ActivityLogger::log('create', 'EmployeeBreak', ActivityLogger::format('create', 'EmployeeBreak', Auth::user()->employee->name ?? 'Unknown', $employeeId) .
    //             "<br><small>Type: {$employeeBreak->type}, Reason: " . ($employeeBreak->reason ?? 'None') . "</small>");

    //         $breakData = $this->prepareBreakDataForPusher($employeeId, $shiftDate, $employeeBreak->id);
    //         \Log::info('Dispatching BreakUpdated event on start', ['employee_id' => $employeeId, 'break_data' => $breakData]);
    //         event(new BreakUpdated($employeeId, $breakData));

    //         return $request->ajax()
    //             ? response()->json(['success' => true, 'message' => 'Break started successfully.', 'break' => $employeeBreak])
    //             : redirect()->route('employee.breaks.index')->with('success', 'Break started successfully.');
    //     } catch (\Exception $e) {
    //         \Log::error('startBreak: Failed', ['emp_id' => $employeeId, 'error' => $e->getMessage()]);
    //         return $request->ajax()
    //             ? response()->json(['success' => false, 'message' => 'Failed to start break: ' . $e->getMessage()], 500)
    //             : redirect()->back()->with('error', 'Failed to start break: ' . $e->getMessage());
    //     }
    // }

    // public function endBreak(Request $request, $id)
    // {
    //     try {
    //         $employeeId = Auth::user()->employee->id;

    //         $break = EmployeeBreak::where('emp_id', $employeeId)
    //             ->where('status', 'On Break')
    //             ->findOrFail($id);

    //         $break->end_time = now_with_timezone();
    //         $break->status = 'Completed';
    //         $break->spent_minutes = Carbon::parse($break->start_time)->diffInMinutes($break->end_time);

    //         $breakDurationSetting = AppSetting::where('key', 'break_duration')->first();
    //         $allowedBreakMinutes = (int) ($breakDurationSetting->value ?? 0);

    //         $break->remaining_minutes = max(0, $allowedBreakMinutes - $break->spent_minutes);
    //         $break->exceeded_minutes = max(0, $break->spent_minutes - $allowedBreakMinutes);

    //         $break->save();

    //         ActivityLogger::log('update', 'EmployeeBreak', ActivityLogger::format('update', 'EmployeeBreak', Auth::user()->employee->name ?? 'Unknown', $employeeId) .
    //             "<br><small>Break ended, Duration: {$break->spent_minutes} minutes</small>");

    //         $shiftDate = getTodayShiftDate($this->getTodayAssignedShift($employeeId));
    //         $breakData = $this->prepareBreakDataForPusher($employeeId, $shiftDate, null);
    //         \Log::info('Dispatching BreakUpdated event on end', ['employee_id' => $employeeId, 'break_data' => $breakData]);
    //         event(new BreakUpdated($employeeId, $breakData));

    //         return $request->ajax()
    //             ? response()->json(['success' => true, 'message' => 'Break ended successfully.', 'break' => $break])
    //             : redirect()->route('employee.breaks.index')->with('success', 'Break ended successfully.');
    //     } catch (\Exception $e) {
    //         \Log::error('endBreak: Failed', ['emp_id' => $employeeId, 'break_id' => $id, 'error' => $e->getMessage()]);
    //         return $request->ajax()
    //             ? response()->json(['success' => false, 'message' => 'Failed to end break: ' . $e->getMessage()], 500)
    //             : redirect()->back()->with('error', 'Failed to end break: ' . $e->getMessage());
    //     }
    // }

    
    public function startBreak(Request $request)
    {
        $employeeId = Auth::user()->employee->id;
        \Log::info('Web EmployeeBreakController@startBreak called', ['employeeId' => $employeeId, 'request' => $request->all()]);

        $request->validate([
            'break_type' => 'required|in:general,official',
            'reason' => 'required_if:break_type,official|nullable|string|max:255',
        ]);

        $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();

        if (!$employeeShift || !$employeeShift->shift) {
            return response()->json(['success' => false, 'message' => 'Employee shift not found.'], 404);
        }

        $shiftDate = resolve_shift_date($employeeShift);

        $attendance = Attendance::where('emp_id', $employeeId)
            ->where('shift_date', $shiftDate)
            ->first();

        if (!$attendance) {
             return response()->json(['success' => false, 'message' => 'No attendance record found for today. Please check in first.'], 400);
        }

        if ($attendance->check_out) {
             return response()->json(['success' => false, 'message' => 'You are already checked out.'], 400);
        }

        // Close any dangling breaks (Sanity Check - same as API)
        EmployeeBreak::where('emp_id', $employeeId)
            ->whereIn('status', ['On Break', 'On break', 'Ongoing'])
            ->get()
            ->each(function ($break) {
                $break->endBreak(['end_time' => now_with_timezone()]);
            });

        $employeeBreak = new EmployeeBreak();
        $employeeBreak->emp_id = $employeeId;
        $employeeBreak->attendance_id = $attendance->id;
        $employeeBreak->shift_date = $shiftDate;
        $employeeBreak->start_time = now_with_timezone();
        $employeeBreak->status = 'On Break';
        $employeeBreak->type = ucfirst(strtolower($request->input('break_type')));
        $employeeBreak->reason = $request->input('reason');
        $employeeBreak->save();

        $breakData = standardize_break_data_for_pusher($employeeId, $shiftDate, $employeeBreak->id);
        \Log::info('Dispatching BreakUpdated event on markBreak', ['employee_id' => $employeeId, 'break_data' => $breakData]);
        event(new BreakUpdated($employeeId, $breakData));

        return response()->json(['success' => true, 'message' => 'Break started.', 'break' => $employeeBreak]);
    }

    public function endBreak(Request $request, $id)
    {
        $employeeId = Auth::user()->employee->id;
        \Log::info('endBreak called', ['employeeId' => $employeeId, 'request' => $request->all()]);

        $break = EmployeeBreak::where('emp_id', $employeeId)->findOrFail($id);
        
        // Use the centralized robust endBreak logic that accounts for actual start_time
        $break->endBreak(['end_time' => now_with_timezone()]);

        $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();
        $shiftDate = $employeeShift ? resolve_shift_date($employeeShift) : now()->toDateString();
        $breakData = standardize_break_data_for_pusher($employeeId, $shiftDate, null);
        \Log::info('Dispatching BreakUpdated event on endBreak', ['employee_id' => $employeeId, 'break_data' => $breakData]);
        event(new BreakUpdated($employeeId, $breakData));

        return response()->json(['success' => true, 'message' => 'Break ended.']);
    }

    public function getActiveBreak()
    {
        try {
            $employeeId = Auth::user()->employee->id;
            $assignedShift = getTodayAssignedShift($employeeId);
            $shiftDate = resolve_shift_date($assignedShift);
            $activeBreak = EmployeeBreak::where('emp_id', $employeeId)
                ->where('status', 'On Break')
                ->where('shift_date', $shiftDate)
                ->first();

            if ($activeBreak) {
                $duration = (int) round(Carbon::parse($activeBreak->start_time)->diffInMinutes(now_with_timezone()));
                return response()->json([
                    'success' => true,
                    'activeBreak' => [
                        'id' => $activeBreak->id,
                        'duration' => $duration
                    ]
                ]);
            }

            return response()->json(['success' => true, 'activeBreak' => null]);
        } catch (\Exception $e) {
            \Log::error('getActiveBreak: Failed to fetch active break.', ['emp_id' => $employeeId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch active break'], 500);
        }
    }

    public function getBreakDuration($id)
    {
        try {
            $employeeId = Auth::user()->employee->id;
            $break = EmployeeBreak::where('emp_id', $employeeId)->findOrFail($id);
            $duration = $break->status === 'On Break'
                ? (int) round(Carbon::parse($break->start_time)->diffInMinutes(now_with_timezone()))
                : (int) $break->spent_minutes;
            return response()->json(['success' => true, 'duration' => $duration]);
        } catch (\Exception $e) {
            \Log::error('getBreakDuration: Failed to fetch break duration.', ['emp_id' => $employeeId, 'break_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch break duration: ' . $e->getMessage()], 500);
        }
    }

    public function breakStatus()
    {
        $employeeId = Auth::user()->employee->id;

        $assignedShift = $this->getTodayAssignedShift($employeeId);
        if (!$assignedShift) {
            return redirect()->back()->with('error', 'No assigned shift found.');
        }

        $shiftDate = resolve_shift_date($assignedShift);

        $ongoingBreak = EmployeeBreak::where('emp_id', $employeeId)
            ->where('status', 'On Break')
            ->first();

        return view('employee.breaks.status', ['onBreak' => $ongoingBreak ? true : false]);
    }

    public function getBreaks()
    {
        $employeeId = Auth::user()->employee->id;

        $breaks = EmployeeBreak::where('emp_id', $employeeId)
            ->orderBy('start_time', 'desc')
            ->get();

        return view('employee.breaks.list', compact('breaks'));
    }

    public function getBreakStats()
    {
        $employeeId = Auth::user()->employee->id;
        $assignedShift = $this->getTodayAssignedShift($employeeId);
        if (!$assignedShift)
            return redirect()->back()->with('error', 'Assigned shift not found.');

        $shiftDate = getTodayShiftDate($assignedShift);

        $allowedBreakMinutes = get_effective_break_minutes((int) $employeeId, $shiftDate);

        $totalSpentMinutes = EmployeeBreak::where('emp_id', $employeeId)
            ->where('type', 'General')
            ->where('status', 'Completed')
            ->where('shift_date', $shiftDate)
            ->get()
            ->sum('spent_minutes');

        $remaining = max($allowedBreakMinutes - $totalSpentMinutes, 0);
        $exceeded = max($totalSpentMinutes - $allowedBreakMinutes, 0);

        $formatMinutes = fn($minutes) => (($h = floor($minutes / 60)) ? $h . 'h ' : '') . (($m = $minutes % 60) ? $m . 'm' : '0m');

        $stats = [
            'shift_date' => $shiftDate,
            'allowed_break_minutes' => $allowedBreakMinutes,
            'total_spent_minutes' => $totalSpentMinutes,
            'remaining_minutes' => $remaining,
            'exceeded_minutes' => $exceeded,
            'total_spent' => $formatMinutes($totalSpentMinutes),
            'remaining' => $formatMinutes($remaining),
            'exceeded' => $formatMinutes($exceeded),
        ];

        return view('employee.breaks.stats', compact('stats'));
    }

    // EmployeeBreakController.php
    public function getBreakLive(Request $request)
    {
        try {
            $employeeId = Auth::user()->employee->id;
            $breakId = $request->query('break_id');
            $break = EmployeeBreak::where('emp_id', $employeeId)->findOrFail($breakId);

            if ($break->status !== 'On Break') {
                return response()->json(['success' => false, 'message' => 'Break is not active'], 400);
            }

            $duration = (int) round(Carbon::parse($break->start_time)->diffInMinutes(now_with_timezone()));
            $employee_breaks = getEmployeeBreakDetailHelper($employeeId);

            // Adjust total_spent_minutes to include current break duration
            $total_spent = $employee_breaks['total_spent_minutes'] + $duration;
            $remaining = max($employee_breaks['allowed_break_minutes'] - $total_spent, 0);
            $exceeded = max($total_spent - $employee_breaks['allowed_break_minutes'], 0);

            return response()->json([
                'success' => true,
                'duration' => $duration,
                'total_spent' => $total_spent,
                'remaining' => $remaining,
                'exceeded' => $exceeded
            ]);
        } catch (\Exception $e) {
            \Log::error('getBreakLive: Failed to fetch live break data.', [
                'emp_id' => $employeeId,
                'break_id' => $breakId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch live break data'], 500);
        }
    }

}