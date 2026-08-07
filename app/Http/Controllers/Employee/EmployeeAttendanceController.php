<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use App\Events\AttendanceUpdated;

class EmployeeAttendanceController extends Controller
{

    public function index(Request $request)
    {
        $employeeId = auth()->user()->employee->id;

        // Use shift_date instead of created_at for accuracy
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $period = \App\Services\PayrollPeriodService::forMonth(
            $request->input('year', $currentYear), 
            $request->input('month', $currentMonth)
        );

        $attendancesQuery = Attendance::where('emp_id', $employeeId)
            ->whereBetween('shift_date', [$period['start']->toDateString(), $period['end']->toDateString()]);

        $employeeShiftToday = EmployeeShift::where('emp_id', $employeeId)
            ->whereDate('assigned_at', Carbon::today())
            ->first();

        if (!$request->has('shift_id') && $employeeShiftToday) {
            $attendancesQuery->where('shift_id', $employeeShiftToday->shift_id);
        }

        if ($request->filled('shift_id') && $request->shift_id !== 'all') {
            $attendancesQuery->where('shift_id', $request->shift_id);
        }

        $attendances = $attendancesQuery->with('shift')->orderBy('shift_date', 'desc')->paginate(10);

        $assignedShiftIds = EmployeeShift::where('emp_id', $employeeId)->pluck('shift_id')->unique();
        $shifts = Shift::whereIn('id', $assignedShiftIds)->get();

        $year = $request->year ?? $currentYear;
        $month = $request->month ?? $currentMonth;

        return view('employee.attendance.index', compact('attendances', 'shifts', 'employeeShiftToday', 'year', 'month'));
    }

    //
    public function markShiftOver()
    {
        $employeeId = Auth::user()->employee->id;

        $today = now()->format('Y-m-d');

        $attendance = Attendance::where('emp_id', $employeeId)
            ->whereDate('check_in', $today)
            ->latest()
            ->first();

        if (!$attendance) {
            return back()->with('error', 'No attendance record found for today.');
        }

        if ($attendance->shift_over_at) {
            return back()->with('info', 'Shift is already marked as over.');
        }

        $attendance->shift_over_at = now();
        $attendance->save();

        // Dispatch synchronization event
        $attendanceData = standardize_attendance_data_for_pusher($employeeId);
        \Log::info('Dispatching AttendanceUpdated event on markShiftOver', ['employee_id' => $employeeId, 'attendance_id' => $attendance->id]);
        event(new AttendanceUpdated($employeeId, $attendanceData));

        return back()->with('success', 'Shift marked as over.');
    }

    public function hrCheckIn(Request $request)
    {
        if (!auth()->user()->is_hr) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $checkInTime = Carbon::parse("{$date} {$request->check_in}");

        $shift = Shift::find($request->shift_id);

        $shiftStart = Carbon::parse("{$date} {$shift->start_time}");
        if ($shift->crosses_midnight) {
            $shiftEnd = Carbon::parse("{$date} {$shift->end_time}")->addDay();
        } else {
            $shiftEnd = Carbon::parse("{$date} {$shift->end_time}");
        }

        $attendance = Attendance::firstOrNew([
            'emp_id' => $request->emp_id,
            'shift_id' => $request->shift_id,
            'shift_date' => $date,
        ]);

        $attendance->check_in = $checkInTime;
        $attendance->status = 'Present';
        $attendance->late_duration = $checkInTime->gt($shiftStart->addMinutes(5)) ? $shiftStart->diffInMinutes($checkInTime) : 0;
        $attendance->created_at = $checkInTime;
        $attendance->updated_at = now();
        $attendance->save();

        return back()->with('success', 'Check-in recorded.');
    }


    // --- NEW LOGS METHODS ---

    public function report(Request $request, $month = null, $year = null)
    {
        $employee = auth()->user()->employee;
        $employeeId = $employee->id;
        
        $month = (int) ($request->month ?: ($month ?: date('n'))); // 'n' for month without leading zeros
        $year = (int) ($request->year ?: ($year ?: date('Y')));

        $tz = (get_employee_settings($employeeId, 'time_zone') ?? 'Asia/Karachi');
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $tz);
        $endDate = $startDate->copy()->endOfMonth();

        // 1. Logs for the month
        $attendances = Attendance::where('emp_id', $employeeId)
            ->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with(['shift', 'lateArrival', 'halfDay', 'breaks'])
            ->orderBy('shift_date', 'asc')
            ->get();

        // 2. Summary Stats
        $limitDate = $endDate->lt(now($tz)) ? $endDate : now($tz);
        $totalWorkDays = collect(\Carbon\CarbonPeriod::create($startDate, $limitDate))
            ->filter(fn($date) => !$date->isWeekend())
            ->count();

        $presentDays = $attendances->where('status', 'Present')->count();
        $lateDays = $attendances->filter(fn($a) => $a->lateArrival)->count();
        $halfDays = $attendances->filter(fn($a) => $a->halfDay)->count();
        
        // Leave stats for the month
        $leaves = \App\Models\Leave::where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            })->count();
            
        $absentRecords = \App\Models\Attendance::where('emp_id', $employeeId)
            ->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where(function($query) {
                $query->where('status', 'Absent')
                      ->orWhereNull('check_in');
            })
            ->get();
            
        $absentDays = 0;
        foreach ($absentRecords as $att) {
            $shiftDateStr = \Carbon\Carbon::parse($att->shift_date)->toDateString();
            if (strcasecmp((string) $att->status, 'Holiday') === 0) continue;
            if (\App\Models\CompanyOffDay::getHolidayForEmployee($shiftDateStr, $employee)) continue;
            $absentDays++;
        }

        // Calculate total productive hours
        $totalWorkedMinutes = $attendances->sum(fn($a) => calculate_net_minutes($a, $tz, now($tz)));
        $totalHours = floor($totalWorkedMinutes / 60);
        $totalMinutes = $totalWorkedMinutes % 60;
        $productiveHours = sprintf("%dh %dm", $totalHours, $totalMinutes);

        // Leaves are already calculated above

        $stats = [
            'total_work_days' => $totalWorkDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'half_days' => $halfDays,
            'leaves' => $leaves,
            'productive_hours' => $productiveHours,
            'attendance_rate' => $totalWorkDays > 0 ? round(($presentDays / $totalWorkDays) * 100, 1) : 0
        ];

        return view('employee.attendance.report', compact('employee', 'month', 'year', 'stats', 'attendances', 'startDate'));
    }

    public function logs()
    {
        // Get shifts assigned to this employee used for filter
        $employeeId = auth()->user()->employee->id;
        $shifts = EmployeeShift::with('shift:id,shift_name')
            ->where('emp_id', $employeeId)
            ->get()
            ->pluck('shift')
            ->unique('id');

        return view('employee.attendance.logs', compact('shifts'));
    }

    public function logsData(Request $request)
    {
        $employee = auth()->user()->employee;
        $employeeId = $employee->id;
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';

        $departmentName = $employee->team ? ($employee->team->department ? $employee->team->department->name : '-') : '-';
        $teamName = $employee->team ? $employee->team->name : '-';
        
        $query = Attendance::where('emp_id', $employeeId)
            ->with(['shift:id,shift_name', 'lateArrival', 'halfDay', 'breaks'])
            ->orderByDesc('shift_date');

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('date_range')) {
            [$from, $to] = array_map('trim', explode(' - ', $request->date_range));
            $query->whereBetween('shift_date', [$from, $to]);
        }

        // Stats are computed on the date range / shift filter subset
        $totalsData = (clone $query)->get();

        // Apply status filter to the query displayed in the table
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'On Time') {
                $query->where('status', 'Present')->whereDoesntHave('lateArrival')->whereDoesntHave('halfDay');
            } elseif ($status === 'Present') {
                $query->where('status', 'Present');
            } elseif ($status === 'Late') {
                $query->whereHas('lateArrival');
            } elseif ($status === 'Absent') {
                $query->where('status', '!=', 'Present')->where('status', '!=', 'Holiday')->where('status', '!=', 'Off Day');
            } elseif ($status === 'Half Day') {
                $query->whereHas('halfDay');
            } elseif ($status === 'Holiday') {
                $query->where('status', 'Holiday');
            } elseif ($status === 'Off Day') {
                $query->whereIn('status', ['Off Day', 'Event']);
            }
        }

        // Generate calculations for statistics cards
        $totalLogs = $totalsData->count();
        
        $countPresent = $totalsData->filter(function($r) {
            return $r->status === 'Present';
        })->count();

        $countOnTime = $totalsData->filter(function($r) {
            return $r->status === 'Present' && !$r->lateArrival && !$r->halfDay;
        })->count();
        
        $countLate = $totalsData->filter(function($r) {
            return (bool)$r->lateArrival;
        })->count();
        
        $countHalfDay = $totalsData->filter(function($r) {
            return (bool)$r->halfDay;
        })->count();
        
        $countAbsent = $totalsData->filter(function($r) {
            return $r->status !== 'Present' && $r->status !== 'Holiday' && $r->status !== 'Off Day';
        })->count();

        $todayStr = now($timezone)->toDateString();
        $countMissingPunches = $totalsData->filter(function($r) use ($todayStr) {
            return $r->check_in && !$r->check_out && $r->shift_date < $todayStr;
        })->count();
        
        // Sum worked hours
        $totWorkedMinutes = $totalsData->sum(fn($r) => calculate_net_minutes($r, $timezone, now($timezone)));
        $totWorkedHours = round($totWorkedMinutes / 60, 2);

        // Average Work Hours
        $avgWorkMinutes = $countPresent > 0 ? ($totWorkedMinutes / $countPresent) : 0;
        $avgHours = floor($avgWorkMinutes / 60);
        $avgMins = round($avgWorkMinutes % 60);
        $avgWorkHoursFormatted = $countPresent > 0 ? "{$avgHours}h {$avgMins}m" : "-";

        // Active Today
        $activeTodayStatus = 'Offline';
        if ($todayAttendance = Attendance::where('emp_id', $employeeId)->whereDate('shift_date', $todayStr)->with('breaks')->first()) {
            if ($todayAttendance->check_in) {
                if ($todayAttendance->check_out) {
                    $activeTodayStatus = 'Checked Out';
                } else {
                    $isOnBreak = $todayAttendance->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time);
                    $activeTodayStatus = $isOnBreak ? 'On Break' : 'On Duty';
                }
            } else {
                $activeTodayStatus = $todayAttendance->status; // Holiday, Off Day, Absent, etc.
            }
        }

        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addColumn('shift_date', fn($r) => $r->shift_date ? Carbon::parse($r->shift_date)->format('Y-m-d') : '-')
            ->addColumn('employee_name', fn($r) => $employee->name)
            ->addColumn('employee_avatar', fn($r) => $employee->profile_pic_url)
            ->addColumn('employee_id', fn($r) => 'AST-' . $employee->id)
            ->addColumn('department_team', fn($r) => $departmentName . ' / ' . $teamName)
            ->addColumn('shift_name', fn($r) => $r->shift->shift_name ?? '-')
            ->addColumn('check_in', fn($r) => $r->check_in ? Carbon::parse($r->check_in)->format('H:i:s') : '-')
            ->addColumn('check_out', function ($r) {
                // Check active break status
                $isOnBreak = $r->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time);
                if ($isOnBreak) return '<span class="badge bg-warning text-dark">On Break</span>';
                
                return $r->check_out ? Carbon::parse($r->check_out)->format('H:i:s') : working_badge();
            })
            ->addColumn('worked_hours', fn($r) => calculateWorkedHours($r))
            ->addColumn('status_badge', function ($r) {
                return $this->generateStatusBadge($r);
            })
            ->addColumn('action', function($r) {
                 if ($r->status !== 'Present') return '-';
                 return '<button class="btn btn-sm btn-outline-primary view-details" data-id="' . $r->id . '" title="View Details">
                            <i class="fa fa-eye"></i>
                        </button>';
            })
            ->rawColumns(['check_out', 'status_badge', 'action'])
            ->with([
                'total_worked_hours' => $totWorkedHours,
                'total_logs' => $totalLogs,
                'count_on_time' => $countOnTime,
                'count_late' => $countLate,
                'count_half_day' => $countHalfDay,
                'count_absent' => $countAbsent,
                'count_missing_punches' => $countMissingPunches,
                'avg_work_hours' => $avgWorkHoursFormatted,
                'active_today' => $activeTodayStatus,
            ])
            ->make(true);
    }

    public function viewDetails($id)
    {
        $employeeId = auth()->user()->employee->id;
        // Strict check: User can only view their OWN attendance details
        $attendance = Attendance::where('emp_id', $employeeId)
            ->with('employee', 'breaks')
            ->findOrFail($id);

        // Reuse the partial from admin if it is generic enough. 
        // Admin partial usually has Approve/Reject buttons which might be weird for employee to see (though they can't click).
        // For expedience, we will use the same partial but hide buttons via CSS or JS checks if needed, 
        // OR better: ensure the partial checks permissions. 
        // For now, let's just make sure the partial exists.
        return view('admin.attendance.partials.details', compact('attendance'));
    }

    public function timesheet(Request $request)
    {
        $employee = auth()->user()->employee;
        $period = \App\Services\PayrollPeriodService::getPeriodForDate(now());
        
        $start = $request->input('start')
            ? Carbon::parse($request->input('start'))
            : $period['start']->copy();

        $end = $request->input('end')
            ? Carbon::parse($request->input('end'))
            : $period['end']->copy();

        return view('employee.attendance.timesheet', compact('employee', 'start', 'end'));
    }

    public function timesheetData(Request $request)
    {
        $employeeId = auth()->user()->employee->id;
        
        $startDate = Carbon::parse($request->start)->startOfDay();
        $endDate = Carbon::parse($request->end)->endOfDay();

        $dates = [];
        $keys = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $dates[] = $cursor->copy();
            $keys[] = $cursor->format('Y_m_d');
            $cursor->addDay();
        }

        $employee = \App\Models\Employee::with([
            'leaves' => function ($q) use ($startDate, $endDate) {
                $q->where(function ($q2) use ($startDate, $endDate) {
                    $q2->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate]);
                })->where('status', 'Approved');
            },
            'attendances' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()]);
            },
            'leaveBalances' => function ($q) {
                $q->where('year', now()->year)->with('leaveType');
            }
        ])->findOrFail($employeeId);

        $attendanceMap = $employee->attendances->keyBy(fn($att) => Carbon::parse($att->shift_date)->format('Y_m_d'));
        
        $leaveMap = [];
        foreach ($employee->leaves as $leave) {
            $ls = Carbon::parse($leave->start_date)->startOfDay();
            $le = Carbon::parse($leave->end_date)->endOfDay();
            $c = $ls->copy();
            while ($c <= $le) {
                $leaveMap[$c->format('Y_m_d')] = true;
                $c->addDay();
            }
        }

        $paid = $employee->leaveBalances->where('leaveType.is_paid', true)->sum('used');
        $unpaid = $employee->leaveBalances->where('leaveType.is_paid', false)->sum('used');
        $allocated = $employee->leaveBalances->where('leaveType.is_paid', true)->sum('allocated');
        $remaining = $employee->leaveBalances->where('leaveType.is_paid', true)->sum('remaining');

        $row = [
            'DT_RowIndex' => 1,
            'name' => $employee->name,
            'position' => $employee->position ?? '-',
            'paid' => $paid,
            'unpaid' => $unpaid,
            'allocated' => $allocated,
            'remaining' => $remaining,
        ];

        foreach ($keys as $i => $key) {
            $date = $dates[$i];
            
            if (isset($attendanceMap[$key])) {
                $row[$key] = employee_status_by_shift_date($employeeId, $date->toDateString());
            } elseif (isset($leaveMap[$key])) {
                $row[$key] = 'L';
            } elseif ($date->format('l') === 'Saturday' || $date->format('l') === 'Sunday') {
                $row[$key] = 'O';
            } elseif ($date->isFuture()) {
                $row[$key] = 'U';
            } else {
                $row[$key] = 'A';
            }
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => 1,
            'recordsFiltered' => 1,
            'data' => [$row]
        ]);
    }

    private function generateStatusBadge($r)
    {
        if ($r->status === 'Holiday') {
            return '<span class="badge bg-info text-white"><i class="fa fa-star me-1"></i>Holiday</span>';
        }
        if ($r->status === 'Event' || $r->status === 'Off Day') {
            return '<span class="badge bg-primary text-white"><i class="fa fa-calendar-week me-1"></i>Off Day</span>';
        }

        if ($r->status !== 'Present') {
            return '<span class="badge bg-secondary text-white">Absent</span>';
        }

        if ($r->halfDay) {
            return '<span class="badge bg-warning text-white" data-bs-toggle="tooltip" title="' . e($r->halfDay->reason) . '">Half Day</span>';
        }

        if ($r->lateArrival) {
            $duration = formatMinutesToHours($r->lateArrival->late_minutes);
            return '<span class="badge bg-danger text-white" data-bs-toggle="tooltip" title="' . e($r->lateArrival->late_reason) . '">Late</span>' .
                   '<br><small class="text-danger fw-bold">' . $duration . '</small>';
        }

        return '<span class="badge bg-success text-white">On Time</span>';
    }

    public function lateHistory()
    {
        $employee = auth()->user()->employee;
        $employeeId = $employee->id;

        $stats = [
            'total_late' => \App\Models\LateArrival::where('emp_id', $employeeId)->count(),
            'total_late_minutes' => \App\Models\LateArrival::where('emp_id', $employeeId)->sum('late_minutes'),
            'this_month_late' => \App\Models\LateArrival::where('emp_id', $employeeId)
                ->whereHas('attendance', function($q) {
                    $period = \App\Services\PayrollPeriodService::current();
                    $q->whereBetween('shift_date', [$period['start']->toDateString(), $period['end']->toDateString()]);
                })->count(),
        ];

        return view('employee.attendance.late_history', compact('employee', 'stats'));
    }

    public function lateHistoryData(Request $request)
    {
        $employeeId = auth()->user()->employee->id;
        
        $query = \App\Models\LateArrival::where('emp_id', $employeeId)
            ->with(['attendance.shift'])
            ->orderByDesc('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Calculate stats for the employee's late arrivals
        $totalIncidents = (clone $query)->count();
        $totalMinutes = (clone $query)->sum('late_minutes');

        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addColumn('formatted_date', function ($row) {
                return date('d M, Y', strtotime($row->date));
            })
            ->editColumn('scheduled_start', function ($row) {
                return $row->scheduled_start ? date('g:i A', strtotime($row->scheduled_start)) : '-';
            })
            ->editColumn('actual_check_in', function ($row) {
                return $row->actual_check_in ? date('g:i A', strtotime($row->actual_check_in)) : '-';
            })
            ->addColumn('late_duration', function ($row) {
                $hours = floor($row->late_minutes / 60);
                $minutes = $row->late_minutes % 60;
                $text = ($hours ? $hours . 'h ' : '') . ($minutes ? $minutes . 'm' : '');
                if (!$hours && !$minutes) {
                    $text = $row->late_minutes . 'm';
                }
                return '<span class="saas-status-badge late"><i class="fas fa-clock mr-1"></i>' . $text . '</span>';
            })
            ->addColumn('action', function($row) {
                if ($row->attendance_id) {
                    return '<button class="btn btn-sm btn-outline-primary view-details" data-id="' . $row->attendance_id . '" title="View Details">
                                <i class="fa fa-eye"></i>
                            </button>';
                }
                return '-';
            })
            ->rawColumns(['late_duration', 'action'])
            ->with([
                'total_incidents' => $totalIncidents,
                'total_minutes' => $totalMinutes,
                'lost_hours' => round($totalMinutes / 60, 1)
            ])
            ->make(true);
    }
}
