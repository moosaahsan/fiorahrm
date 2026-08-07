<?php

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AppSetting;
use App\Models\EmployeeBreak;
use App\Models\EmployeeShift;
use App\Models\LateArrival;
use App\Models\HalfDay;
use App\Models\Employee;
use Illuminate\Support\Facades\Cache;

if (!function_exists('app_settings')) {
    function app_settings($key = null)
    {
        $settings = Cache::remember('app_settings', now()->addHours(2), function () {
            if (!Schema::hasTable('app_settings')) {
                return [];
            }
            return AppSetting::pluck('value', 'key')->toArray();
        });
        $formatted = [
            'company_name' => $settings['company_name'] ?? 'Your Company Name',
            'app_logo' => $settings['app_logo'] ?? '/images/logo.png',
            'break_duration' => (int) ($settings['break_duration'] ?? 30),
            'late_minutes' => (int) ($settings['late_minutes'] ?? 15),
            'half_day_allowed_in_month' => (int) ($settings['half_day_allowed_in_month'] ?? 2),
            'full_day_allowed_in_month' => (int) ($settings['full_day_allowed_in_month'] ?? 20),
            'app_timezone' => $settings['app_timezone'] ?? config('app.timezone', 'Asia/Karachi'),
            'leaves_allowed_in_year' => (int) ($settings['leaves_allowed_in_year'] ?? 24),
        ];

        return $key ? ($formatted[$key] ?? null) : $formatted;
    }
}

if (!function_exists('now_with_timezone')) {
    function now_with_timezone()
    {
        return Carbon::now(app_settings('app_timezone') ?? 'Asia/Karachi');
    }
}

if (!function_exists('get_leave_balance')) {
    function get_leave_balance($employeeId)
    {
        $currentYear = date('Y');
        $balance = \App\Models\LeaveBalance::where('employee_id', $employeeId)
                    ->where('year', $currentYear)
                    ->get();
        
        $totalAllocated = $balance->sum('allocated');
        $totalUsed = $balance->sum('used');
        $totalRemaining = $balance->sum('remaining'); 

        // Fallback for old system if no balances found
        if ($balance->isEmpty()) {
             $totalAllocated = (int) app_settings('leaves_allowed_in_year');
             // $totalUsed = Leave::where('employee_id', $employeeId)->count(); // Old logic, deprecated
             $totalUsed = 0; 
             $totalRemaining = $totalAllocated;
        }

        return [
            'total' => $totalAllocated,
            'used' => $totalUsed,
            'remaining' => $totalRemaining,
        ];
    }
}

// if (!function_exists('get_clockouts')) {
//     function get_clockouts($employeeId, $date = null)
//     {
//         $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
//         $now = now()->timezone($timezone);

//         // Resolve shift date if not provided
//         if (!$date) {
//             $employeeShift = EmployeeShift::with('shift')
//                 ->where('emp_id', $employeeId)
//                 ->latest('assigned_at')
//                 ->first();

//             $date = $employeeShift ? web_resolve_shift_date($employeeShift) : $now->toDateString();
//         }

//         $allowed = (int) app_settings('break_duration') ?? 30;

//         $breaks = \App\Models\EmployeeBreak::where('emp_id', $employeeId)
//             ->where('type', 'General')
//             ->where('status', 'Completed')
//             ->whereDate('end_time', $date) // Filter by end_time date
//             ->get();

//         $spent = 0;

//         foreach ($breaks as $break) {
//             try {
//                 $end = \Carbon\Carbon::parse($break->end_time)->timezone($timezone);

//                 // Combine shift date and start_time (which is just H:i:s)
//                 $start = \Carbon\Carbon::parse($date . ' ' . $break->start_time)->timezone($timezone);

//                 // Adjust if start is after end (means break started previous day)
//                 if ($start->gt($end)) {
//                     $start->subDay();
//                 }

//                 if ($end->gt($start)) {
//                     $spent += $start->diffInMinutes($end);
//                 }
//             } catch (\Exception $e) {
//                 \Log::warning('get_clockouts: Error parsing break time', [
//                     'emp_id' => $employeeId,
//                     'start_time_raw' => $break->start_time,
//                     'end_time_raw' => $break->end_time,
//                     'error' => $e->getMessage()
//                 ]);
//                 continue;
//             }
//         }

//         $spent = round($spent);
//         $remaining = max($allowed - $spent, 0);
//         $exceeded = max($spent - $allowed, 0);

//         \Log::info('get_clockouts: Break summary', [
//             'emp_id' => $employeeId,
//             'shift_date' => $date,
//             'allowed' => $allowed,
//             'spent' => $spent,
//             'remaining' => $remaining,
//             'exceeded' => $exceeded,
//         ]);

//         return [
//             'break_spent' => $spent,
//             'break_limit' => $allowed,
//             'break_remaining' => $remaining,
//             'break_exceeded' => $exceeded,
//         ];
//     }
// }


if (!function_exists('get_clockouts')) {
    function get_clockouts($employeeId, $date = null)
    {
        $timezone = (function_exists('get_employee_settings') ? get_employee_settings($employeeId, 'time_zone') : null) 
                    ?? (app_settings('app_timezone') ?? 'Asia/Karachi');
        $now = now($timezone);

        // Resolve shift date if not provided
        if (!$date) {
            $employeeShift = EmployeeShift::with('shift')
                ->where('emp_id', $employeeId)
                ->latest('assigned_at')
                ->first();

            $date = $employeeShift ? web_resolve_shift_date($employeeShift) : $now->toDateString();
        }

        $allowed = get_effective_break_minutes((int) $employeeId, $date);

        // Fetch attendance just to get the ID if needed
        $attendance = \App\Models\Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $date)
            ->first();

        $attendanceId = $attendance?->id;

        // ✅ Use shift_date for filtering (matches helpers.php logic)
        // Note: We include both Completed AND On Break.
        $breaks = \App\Models\EmployeeBreak::where('emp_id', $employeeId)
            ->where('type', 'General')
            ->where('shift_date', $date) 
            ->get();

        $spent = 0;
        $status = 'Working'; 

        foreach ($breaks as $break) {
            if (strtolower($break->status) === 'on break') {
                $status = 'Ongoing';
                // Calculate live duration for active break
                try {
                    // Use created_at as the anchor for the date to avoid TIME column issues
                    $start = \Carbon\Carbon::parse($break->created_at)->timezone($timezone);
                    
                    if ($break->start_time) {
                        $st = \Carbon\Carbon::parse($break->start_time)->timezone($timezone);
                        $start->setTime($st->hour, $st->minute, $st->second);
                        
                        if ($start->gt($break->created_at)) {
                            $start->subDay();
                        }
                    }
                    
                    $spent += $start->diffInMinutes($now);
                } catch (\Exception $e) {
                    continue;
                }
            } else {
                 // Trust stored spent_minutes for completed breaks (helpers.php logic)
                 $spent += $break->spent_minutes;
            }
        }

        $spent = round($spent);
        $remaining = max($allowed - $spent, 0);
        $exceeded = max($spent - $allowed, 0);

        return [
            'break_spent' => $spent,
            'break_limit' => $allowed,
            'break_remaining' => $allowed - $spent, 
            'break_exceeded' => $exceeded,
            'attendance_id' => $attendanceId, // ✅ returned
            'status' => $status,
        ];
    }
}

if (!function_exists('get_daily_hour')) {
    function get_daily_hour($employeeId, $date = null)
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = now($timezone);

        $employeeShift = EmployeeShift::with('shift')
            ->where('emp_id', $employeeId)
            ->latest('assigned_at')
            ->first();

        if (!$employeeShift || !$employeeShift->shift) {
            return '0 min';
        }

        if (!$date) {
            $date = web_resolve_shift_date($employeeShift);
        }

        $attendance = Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $date)
            ->first();

        // Cross-midnight check logic preserved
        if ($employeeShift->shift->crosses_midnight && (!$attendance || !$attendance->check_in)) {
            $prevDate = \Carbon\Carbon::parse($date)->subDay()->toDateString();
            $prevAttendance = Attendance::where('emp_id', $employeeId)
                ->whereDate('shift_date', $prevDate)
                ->first();

            if ($prevAttendance && $prevAttendance->check_in) {
                // Determine if this is the active session - simplistic check
                $attendance = $prevAttendance;
                $date = $prevDate;
            }
        }

        if (!$attendance || !$attendance->check_in) {
            return '0 min';
        }

        // Use unified calculation helper
        $netMinutes = calculate_net_minutes($attendance, $timezone, $now);

        return format_minutes($netMinutes); 
    }
}


if (!function_exists('employee_status')) {
    function employee_status($employeeId)
    {
        $employeeShift = \App\Models\EmployeeShift::with('shift')
            ->where('emp_id', $employeeId)
            ->latest('assigned_at')
            ->first();

        if (!$employeeShift || !$employeeShift->shift) {
            \Log::warning('employee_status: No shift assigned or shift not found', ['emp_id' => $employeeId]);
            return '<span class="text-danger">Absent</span>';
        }

        $shiftDate = web_resolve_shift_date($employeeShift);

        $attendance = \App\Models\Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $shiftDate)
            ->first();

        if (!$attendance && $employeeShift->shift->crosses_midnight) {
            $prevDate = now_with_timezone()->subDay()->toDateString();
            $attendance = \App\Models\Attendance::where('emp_id', $employeeId)
                ->whereDate('shift_date', $prevDate)
                ->first();
        }

        if (
            !$attendance ||
            !$attendance->check_in ||
            empty($attendance->status)
        ) {
            // Check for Leaves (Approved or Pending)
            $leave = \App\Models\Leave::where('employee_id', $employeeId)
                ->whereIn('status', ['Approved', 'Pending'])
                ->where(function($q) use ($shiftDate) {
                    $q->whereDate('start_date', '<=', $shiftDate)
                      ->whereDate('end_date', '>=', $shiftDate);
                })
                ->orderByRaw("FIELD(status, 'Approved', 'Pending')")
                ->first();

            if ($leave) {
                if ($leave->status === 'Approved') {
                    return '<span class="z-badge z-badge-approved">Approved (On Leave)</span>';
                } else {
                    return '<span class="z-badge z-badge-pending">Pending</span>';
                }
            }

            $employee = \App\Models\Employee::find($employeeId);
            $teamId = $employee ? $employee->team_id : null;

            // Check for Holiday / Off Day
            if (\App\Models\CompanyOffDay::isOffDay($shiftDate, 'Holiday', $teamId)) {
                return '<span class="z-badge z-badge-holiday">Holiday</span>';
            }
            if (\App\Models\CompanyOffDay::isOffDay($shiftDate, null, $teamId)) {
                return '<span class="z-badge z-badge-off">OFF</span>';
            }
            // Verify if there's an approved leave that caused deduction
            $hasLeave = \App\Models\Leave::where('employee_id', $employeeId)
                ->whereIn('status', ['Approved', 'Pending'])
                ->whereDate('start_date', '<=', $shiftDate)
                ->whereDate('end_date', '>=', $shiftDate)
                ->exists();

            if ($hasLeave) {
                return '<span class="z-badge z-badge-absent-deducted">Absent (Deducted)</span>';
            } else {
                return '<span class="z-badge z-badge-absent-unpaid">Absent (Unpaid)</span>';
            }
        }

        // ✅ If we get here, $attendance exists and has check_in
        if (is_half_day($attendance)) {
            return '<span class="z-badge z-badge-half">Half Day</span>';
        }

        if (is_late_arrival($attendance)) {
            return '<span class="z-badge z-badge-late">Late</span>';
        }

        return '<span class="z-badge z-badge-present">Present</span>';
    }
}



if (!function_exists('employee_status_by_shift_date')) {
    /**
     * Return an HTML badge representing status for a given employee & date,
     * using attendance + late_arrivals + half_days tables.
     *
     * Order of precedence:
     *  - no attendance row      => Absent
     *  - half_days has a record => Half Day
     *  - late_arrivals has row  => Late (with minutes if available)
     *  - otherwise              => On Time
     *
     * @param  int         $employeeId
     * @param  string|null $shiftDate  Y-m-d (or any parsable date). If null uses today (app TZ).
     * @return string       HTML <span class="badge ...">...</span>
     */
    function employee_status_by_shift_date(int $employeeId, ?string $shiftDate = null): string
    {
        $tz = app_settings('app_timezone') ?? 'Asia/Karachi';
        $date = $shiftDate
            ? Carbon::parse($shiftDate, $tz)->toDateString()
            : now()->timezone($tz)->toDateString();

        // 1) Must have attendance for that date
        $attendance = Attendance::query()
            ->select('id', 'shift_id', 'emp_id', 'status', 'check_in')
            ->where('emp_id', $employeeId)
            ->whereDate('shift_date', $date)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            // Check for Leaves (Approved or Pending)
            $leave = \App\Models\Leave::where('employee_id', $employeeId)
                ->whereIn('status', ['Approved', 'Pending'])
                ->where(function ($q) use ($date) {
                    $q->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                })
                ->orderByRaw("FIELD(status, 'Approved', 'Pending')")
                ->first();

            if ($leave) {
                if ($leave->status === 'Approved') {
                    return '<span class="z-badge z-badge-approved">Approved (On Leave)</span>';
                } else {
                    return '<span class="z-badge z-badge-pending">Pending</span>';
                }
            }

            $employee = \App\Models\Employee::find($employeeId);
            $teamId = $employee ? $employee->team_id : null;

            if (\App\Models\CompanyOffDay::isOffDay($date, 'Holiday', $teamId)) {
                return '<span class="z-badge z-badge-holiday">Holiday</span>';
            }
            if (\App\Models\CompanyOffDay::isOffDay($date, null, $teamId)) {
                return '<span class="z-badge z-badge-off">OFF</span>';
            }
            // Verify if there's an approved leave that caused deduction
            $hasLeave = \App\Models\Leave::where('employee_id', $employeeId)
                ->whereIn('status', ['Approved', 'Pending'])
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($hasLeave) {
                return '<span class="z-badge z-badge-absent-deducted">Absent (Deducted)</span>';
            } else {
                return '<span class="z-badge z-badge-absent-unpaid">Absent (Unpaid)</span>';
            }
        }

        // 2) Half Day?
        $hasHalfDay = HalfDay::query()
            ->where('emp_id', $employeeId)
            ->whereDate('date', $date)
            ->exists();

        if ($hasHalfDay) {
            return '<span class="z-badge z-badge-half">Half Day</span>';
        }

        // 3) Late? (show minutes if available)
        $late = LateArrival::query()
            ->select('late_minutes')
            ->where('emp_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        if ($late) {
            $mins = (int) ($late->late_minutes ?? 0);
            $hm = format_minutes_to_hm($mins);      // e.g., "1 h 20 m"
            $label = $mins > 0 ? "Late ({$hm})" : 'Late';
            return '<span class="z-badge z-badge-late">' . e($label) . '</span>';  // BS5
        }


        // 4) Otherwise present and on time
        return '<span class="z-badge z-badge-present">Present</span>';
    }
}

if (!function_exists('is_half_day')) {
    function is_half_day($attendance)
    {
        if (!$attendance)
            return false;

        return \App\Models\HalfDay::where('emp_id', $attendance->emp_id)
            ->where('attendance_id', $attendance->id)
            ->whereDate('date', $attendance->shift_date)
            ->exists();
    }
}

if (!function_exists('is_late_arrival')) {
    function is_late_arrival($attendance)
    {
        if (!$attendance)
            return false;

        return \App\Models\LateArrival::where('emp_id', $attendance->emp_id)
            ->where('attendance_id', $attendance->id)
            ->whereDate('date', $attendance->shift_date)
            ->exists();
    }
}



if (!function_exists('get_effective_shift_date_from_now')) {
    function get_effective_shift_date_from_now($shift, $timezone = 'Asia/Karachi')
    {
        return $shift && $shift->crosses_midnight
            ? now($timezone)->subDay()->toDateString()
            : now($timezone)->toDateString();
    }
}



if (!function_exists('format_minutes_to_hm')) {
    function format_minutes_to_hm($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    }
}


if (!function_exists('get_late_today')) {
    function get_late_today(array $employeeIds, $date = null): array
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $today = $date ?? now($timezone)->toDateString();
        
        // Strict: Only count records for the specific DATE requested (Today)
        // We exclude Yesterday's cross-midnight shifts to align with "Today's Shift" perspective.
        return \App\Models\LateArrival::whereIn('emp_id', $employeeIds)
            ->where('date', $today)
            ->where('late_minutes', '>', 0)
            ->pluck('emp_id')
            ->unique()
            ->toArray();
    }
}

if (!function_exists('get_half_day_today')) {
    function get_half_day_today(array $employeeIds, $date = null): array
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $today = $date ?? now($timezone)->toDateString();
        
        // Strict: Only count records for the specific DATE requested
        return \App\Models\HalfDay::whereIn('emp_id', $employeeIds)
            ->where('date', $today)
            ->pluck('emp_id')
            ->unique()
            ->toArray();
    }
}

if (!function_exists('get_on_time_today')) {
    function get_on_time_today(array $employeeIds, $date = null): array
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $today = $date ?? now($timezone)->toDateString();
        
        // 1. Get Present Employees strictly for TODAY'S shift date
        $presentIds = \App\Models\Attendance::whereIn('emp_id', $employeeIds)
            ->whereNotNull('check_in')
            ->whereDate('shift_date', $today) // Ensure strict date comparison
            ->pluck('emp_id')
            ->unique()
            ->toArray();

        // 2. Get DB Late + Half Day IDs (strictly for today)
        $lateIds = get_late_today($employeeIds, $today);
        $halfDayIds = get_half_day_today($employeeIds, $today);

        // 3. On Time = Present - (Late + Half Day)
        $excluded = array_unique(array_merge($lateIds, $halfDayIds));
        
        return array_values(array_diff($presentIds, $excluded));
    }
}


if (!function_exists('get_on_time_percentage')) {
    function get_on_time_percentage($onTimeCount, $totalEmployees)
    {
        return $totalEmployees > 0
            ? round(($onTimeCount / $totalEmployees) * 100, 2)
            : 0;
    }
}

function getTodayShiftDate($assignedShift)
{
    $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
    $now = now($timezone);

    $start = Carbon::parse($now->toDateString() . ' ' . $assignedShift->shift->start_time, $timezone);
    $end = Carbon::parse($now->toDateString() . ' ' . $assignedShift->shift->end_time, $timezone);

    if ($assignedShift->shift->crosses_midnight && $end->lt($start)) {
        $end->addDay();
    }

    // If current time is after start but before end → use today's shift
    if ($now->between($start, $end)) {
        return $start->toDateString();
    }

    // Else, maybe shift was yesterday and now is still in shift period (after midnight)
    if ($assignedShift->shift->crosses_midnight) {
        $yesterday = $now->copy()->subDay();
        $start = Carbon::parse($yesterday->toDateString() . ' ' . $assignedShift->shift->start_time, $timezone);
        $end = Carbon::parse($yesterday->toDateString() . ' ' . $assignedShift->shift->end_time, $timezone)->addDay();

        if ($now->between($start, $end)) {
            return $start->toDateString();
        }
    }

    return $now->toDateString(); // fallback
}


if (!function_exists('formatMinutesToHours')) {
    /**
     * Converts minutes to a human-readable string like "1 hour 20 min".
     *
     * @param int|null $minutes
     * @return string
     */
    function formatMinutesToHours($minutes)
    {
        if (!$minutes || $minutes <= 0)
            return '0 min';

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        $formatted = trim(
            ($hours ? "{$hours} hour" . ($hours > 1 ? 's' : '') : '') .
                ($mins ? " {$mins} min" : '')
        );

        return $formatted;
    }
}

function getReadableDuration($start, $end)
{
    $start = Carbon::parse($start);
    $end = Carbon::parse($end);

    $diff = $start->diff($end);
    return ($diff->h ? "{$diff->h} hour " : '') . "{$diff->i} min";
}

function formatDecimalHours($decimal)
{
    $hours = floor($decimal);
    $minutes = round(($decimal - $hours) * 60);
    return "{$hours}h {$minutes}m";
}


if (!function_exists('calculateWorkedHours')) {

    function calculateWorkedHours($attendance)
    {
        if (!$attendance) {
            return '-';
        }

        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = now($timezone);

        if (!$attendance->check_in) {
            return '-';
        }

        // Use the unified calculation helper for consistency
        $minutes = calculate_net_minutes($attendance, $timezone, $now);

        return formatMinutesToHours($minutes);
    }


    if (!function_exists('formatMinutesToHours')) {
        function formatMinutesToHours($minutes)
        {
            if ($minutes < 1) {
                return "Just now";
            }

            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            return "{$hours}h {$mins}m";
        }
    }


    if (!function_exists('calculateEarlyLeave')) {
        function calculateEarlyLeave($attendance)
        {
            if (!$attendance->check_out || !$attendance->shift) {
                return null;
            }

            $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';

            $checkOut = Carbon::parse($attendance->check_out)->setTimezone($timezone);
            $shiftStart = Carbon::parse($attendance->shift->start_time);
            $shiftEnd = Carbon::parse($attendance->shift->end_time);

            // Handle shift crossing midnight
            if ($shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }

            // Use attendance date as base
            $shiftDate = Carbon::parse($attendance->shift_date ?? $checkOut->toDateString());
            $fullShiftEnd = $shiftDate->copy()
                ->setTimeFrom($shiftEnd);

            if ($shiftEnd->lt($shiftStart)) {
                $fullShiftEnd->addDay();
            }

            if ($checkOut->lt($fullShiftEnd)) {
                $minutesEarly = $fullShiftEnd->diffInMinutes($checkOut);
                return formatMinutesToHours($minutesEarly);
            }

            return null; // Not early

            // example usage in Controller 

            //         @php
            //     $earlyLeave = calculateEarlyLeave($attendance);
            // @endphp

            // @if ($earlyLeave)
            //     <span class="badge bg-warning">Left Early ({{ $earlyLeave }})</span>
            // @endif

        }
    }

    function get_working_summary($emp_id)
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = now($timezone);

        $thisPeriod = \App\Services\PayrollPeriodService::getPeriodForDate($now);
        $startOfThisMonth = $thisPeriod['start']->copy();
        
        $targetDateForLastMonth = \Carbon\Carbon::createFromDate($thisPeriod['payroll_year'], $thisPeriod['payroll_month'], 1)->subMonthNoOverflow();
        $lastPeriod = \App\Services\PayrollPeriodService::forMonth($targetDateForLastMonth->year, $targetDateForLastMonth->month);
        $startOfLastMonth = $lastPeriod['start']->copy();
        $endOfLastMonth = $lastPeriod['end']->copy();
        $startOfThisWeek = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $todayStr = $now->toDateString();

        // Fetch attendances from start of last month
        $attendances = Attendance::where('emp_id', $emp_id)
            ->where('shift_date', '>=', $startOfLastMonth->toDateString())
            ->get();

        $summary = [
            'last_month' => 0,
            'this_month' => 0,
            'this_week' => 0,
            'today' => 0,
        ];

        foreach ($attendances as $attendance) {
            $shiftDate = $attendance->shift_date;
            
            // Get net minutes using unified logic

            
            // Refactoring to use a shared core calculator for efficiency
            $minutes = calculate_net_minutes($attendance, $timezone, $now);

            // 1. Last Month (Exclusive)
            if ($shiftDate >= $startOfLastMonth->toDateString() && $shiftDate <= $endOfLastMonth->toDateString()) {
                $summary['last_month'] += $minutes;
            }
            
            // 2. This Month
            if ($shiftDate >= $startOfThisMonth->toDateString()) {
                $summary['this_month'] += $minutes;
            }

            // 3. This Week
            if ($shiftDate >= $startOfThisWeek->toDateString()) {
                $summary['this_week'] += $minutes;
            }
        }

        return [
            'last_month' => format_minutes($summary['last_month']),
            'this_month' => format_minutes($summary['this_month']),
            'this_week' => format_minutes($summary['this_week']),
        ];
    }

    function calculate_net_minutes($attendance, $timezone, $now)
    {
        if (!$attendance || !$attendance->check_in) return 0;

        $checkIn = Carbon::parse($attendance->check_in, $timezone);
        
        // If no check_out, determine if shift is likely still active
        if ($attendance->check_out) {
            $checkOut = Carbon::parse($attendance->check_out, $timezone);
        } else {
            // Normalize shift_date to 'Y-m-d' string regardless of how it's cast (date or datetime)
            $shiftDateStr = ($attendance->shift_date instanceof \Carbon\Carbon || $attendance->shift_date instanceof \Illuminate\Support\Carbon)
                ? $attendance->shift_date->toDateString()
                : \Carbon\Carbon::parse($attendance->shift_date)->toDateString();

            $isToday = $shiftDateStr === $now->toDateString();
            $employeeShift = EmployeeShift::with('shift')->where('emp_id', $attendance->emp_id)->latest()->first();
            $resolvedDate = $employeeShift ? web_resolve_shift_date($employeeShift) : null;

            if ($isToday || ($resolvedDate && \Carbon\Carbon::parse($resolvedDate)->toDateString() === $shiftDateStr)) {
                $checkOut = $now;
            } else {
                $checkOut = $checkIn->copy()->addMinutes(960);
            }
        }

        if ($checkOut->lt($checkIn)) {
            $checkOut->addDay();
        }

        $workedMinutes = min((int) $checkIn->diffInMinutes($checkOut), 960);

        // ✅ Use eager-loaded collection if available to prevent redundant queries
        if ($attendance->relationLoaded('breaks')) {
            $breakMinutes = (int) $attendance->breaks
                ->where('type', 'General')
                ->where('status', 'Completed')
                ->sum('spent_minutes');
        } else {
            $breakMinutes = (int) EmployeeBreak::where('emp_id', $attendance->emp_id)
                ->where('shift_date', $attendance->shift_date)
                ->where('type', 'General')
                ->where('status', 'Completed')
                ->sum('spent_minutes');
        }

        return max(0, $workedMinutes - $breakMinutes);
    }



    function format_minutes($minutes)
    {
        if ($minutes <= 0)
            return '0 min';
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }




    if (!function_exists('get_early_leaves_summary')) {
        function get_early_leaves_summary($emp_id)
        {
            $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';

            $now = Carbon::now($timezone);

            $thisPeriod = \App\Services\PayrollPeriodService::getPeriodForDate($now);
            $targetDateForLastMonth = \Carbon\Carbon::createFromDate($thisPeriod['payroll_year'], $thisPeriod['payroll_month'], 1)->subMonthNoOverflow();
            $lastPeriod = \App\Services\PayrollPeriodService::forMonth($targetDateForLastMonth->year, $targetDateForLastMonth->month);

            $periods = [
                'last_month' => [
                    $lastPeriod['start']->copy(),
                    $lastPeriod['end']->copy(),
                ],
                'this_month' => [
                    $thisPeriod['start']->copy(),
                    $now->copy(),
                ],
                'this_week' => [
                    Carbon::now($timezone)->startOfWeek(Carbon::MONDAY),
                    Carbon::now($timezone),
                ],
                'today' => [
                    Carbon::now($timezone)->startOfDay(),
                    Carbon::now($timezone),
                ],
            ];

            $summary = [];

            foreach ($periods as $key => [$start, $end]) {
                $attendances = Attendance::with('shift')
                    ->where('emp_id', $emp_id)
                    ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
                    ->get();

                $earlyMinutes = 0;

                foreach ($attendances as $att) {
                    if ($att->check_out && $att->shift) {
                        $outTime = Carbon::parse($att->check_out)->timezone($timezone);
                        $shiftEnd = Carbon::parse($att->shift->end_time)->timezone($timezone);

                        // Handle overnight shift
                        $shiftStart = Carbon::parse($att->shift->start_time);
                        if ($shiftEnd->lt($shiftStart)) {
                            $shiftEnd->addDay();
                        }

                        if ($outTime->lt($shiftEnd)) {
                            $earlyMinutes += $shiftEnd->diffInMinutes($outTime);
                        }
                    }
                }

                $summary[$key] = formatMinutesToHours($earlyMinutes);
            }

            return $summary;
        }
    }
}

if (!function_exists('get_profile_picture_url')) {
    function get_profile_picture_url($profilePic, $name = null)
    {
        // 1. Check karein ke path mojud hai aur file disk par hai
        if ($profilePic && \Storage::disk('public')->exists($profilePic)) {
            // 2. Storage::url use karein jo config se 'public_storage' uthayega
            return \Storage::disk('public')->url($profilePic);
        }

        // 3. Agar name pass kiya hai to ui-avatars se generated image return karein
        if ($name) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=4f46e5&background=f0f0ff';
        }

        // 4. Agar image nahi hai to default fallback image
        return asset('assets/images/blue-background.png');
    }
}

if (!function_exists('get_cover_picture_url')) {
    function get_cover_picture_url($coverPic)
    {
        if ($coverPic && \Storage::disk('public')->exists($coverPic)) {
            return \Storage::disk('public')->url($coverPic);
        }

        return null;
    }
}

if (!function_exists('parse_cover_pic_position')) {
    function parse_cover_pic_position(?string $raw): array
    {
        $defaults = ['x' => 50, 'y' => 35, 'zoom' => 100];

        if (!$raw) {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return [
            'x' => max(0, min(100, (float) ($decoded['x'] ?? $defaults['x']))),
            'y' => max(0, min(100, (float) ($decoded['y'] ?? $defaults['y']))),
            'zoom' => max(100, min(200, (float) ($decoded['zoom'] ?? $defaults['zoom']))),
        ];
    }
}

if (!function_exists('working_badge')) {
    function working_badge(): string
    {
        return <<<HTML
<span class="saas-status-badge present animate-pulse" style="background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.3); color: #4338ca;">
    <i class="fas fa-satellite-dish me-1"></i> Working Active
</span>
HTML;
    }
}

if (!function_exists('get_employee_break')) {
    function get_employee_break($employeeId)
    {
        // Get today's assigned shift
        $assignedShift = getTodayAssignedShift($employeeId);

        // Resolve shift date
        $shiftDate = web_resolve_shift_date($assignedShift);
        $totals = get_clockouts_for_pusher($employeeId);
        return $totals;
    }
}

function getTodayAssignedShift($employeeId)
{
    $employeeShift = EmployeeShift::with('shift')
        ->where('emp_id', $employeeId)
        ->latest('assigned_at') // get most recent assignment
        ->first();

    return $employeeShift; // ✅ return full model, not just shift
}


if (!function_exists('web_resolve_shift_date')) {
    function web_resolve_shift_date($employeeShift)
    {
        return resolve_shift_date($employeeShift);
    }
}


// This function calculates and returns detailed break information for a specific employee. 
// you can use this function to get insights into how much break time an employee has used, 
// how much is remaining, and if they have exceeded their allowed break time.
function getEmployeeBreakDetailHelperr($employeeId)
{
    try {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return [
                'shift_date' => now()->toDateString(),
                'allowed_break_minutes' => 0,
                'total_spent_minutes' => 0,
                'remaining_minutes' => 0,
                'exceeded_minutes' => 0,
                'total_spent' => '0 minutes',
                'remaining' => '0 minutes',
                'exceeded' => '0 minutes',
            ];
        }

        $employeeShift = EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();

        $shiftDate = $employeeShift ? resolve_shift_date($employeeShift) : now()->toDateString();
        $allowed = get_effective_break_minutes((int) $employeeId, $shiftDate);
        $shift = $employeeShift->shift ?? null;

        if ($shift) {
            $shiftStart = Carbon::parse("$shiftDate {$shift->start_time}", 'Asia/Karachi');
            $shiftEnd = Carbon::parse("$shiftDate {$shift->end_time}", 'Asia/Karachi');
            if ($shift->crosses_midnight && $shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }
        }

        $breaks = EmployeeBreak::where('emp_id', $employee->id)
            ->where('type', 'General')
            ->where('status', 'Completed')
            ->where('shift_date', $shiftDate)
            ->get();

        $spent = 0;
        foreach ($breaks as $break) {
            if ($break->start_time && $break->end_time) {
                try {
                    $start = Carbon::parse($break->start_time);
                    $end = Carbon::parse($break->end_time);
                    if ($end->gt($start)) {
                        $spent += $start->diffInMinutes($end);
                    }
                } catch (\Exception $e) {
                    \Log::error('getEmployeeBreakDetailHelper: Failed to parse break times.', [
                        'emp_id' => $employeeId,
                        'break_id' => $break->id,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
        }

        $spent = round($spent);
        $remaining = max($allowed - $spent, 0);
        $exceeded = max($spent - $allowed, 0);

        $format = fn($mins) => $mins >= 60
            ? floor($mins / 60) . ' hour' . (floor($mins / 60) > 1 ? 's' : '') . ' ' . ($mins % 60) . ' minute' . (($mins % 60) !== 1 ? 's' : '')
            : "$mins minute" . ($mins != 1 ? 's' : '');

        return [
            'shift_date' => $shiftDate,
            'allowed_break_minutes' => $allowed,
            'total_spent_minutes' => $spent,
            'remaining_minutes' => $remaining,
            'exceeded_minutes' => $exceeded,
            'total_spent' => $format($spent),
            'remaining' => $format($remaining),
            'exceeded' => $format($exceeded),
        ];
    } catch (\Exception $e) {
        \Log::error('getEmployeeBreakDetailHelper: Failed to fetch break details.', [
            'emp_id' => $employeeId,
            'error' => $e->getMessage()
        ]);
        return [
            'shift_date' => now()->toDateString(),
            'allowed_break_minutes' => 0,
            'total_spent_minutes' => 0,
            'remaining_minutes' => 0,
            'exceeded_minutes' => 0,
            'total_spent' => '0 minutes',
            'remaining' => '0 minutes',
            'exceeded' => '0 minutes',
        ];
    }
}

function getEmployeeBreakDetailHelper($employeeId)
{
    try {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return [
                'shift_date' => now()->toDateString(),
                'allowed_break_minutes' => 0,
                'total_spent_minutes' => 0,
                'remaining_minutes' => 0,
                'exceeded_minutes' => 0,
                'total_spent' => '0 minutes',
                'remaining' => '0 minutes',
                'exceeded' => '0 minutes',
            ];
        }

        // Get latest assigned shift
        $employeeShift = EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();

        // Use global resolve_shift_date (consistent with dashboard)
        $shiftDate = $employeeShift
            ? resolve_shift_date($employeeShift)
            : now()->toDateString();

        $allowed = get_effective_break_minutes((int) $employeeId, $shiftDate);

        // Get completed general breaks for today
        $breaks = EmployeeBreak::where('emp_id', $employeeId)
            ->where('type', 'General')
            ->where('status', 'Completed')
            ->where('shift_date', $shiftDate)
            ->get();

        // ✔ Use DB saved minutes (correct)
        $spent = (int) $breaks->sum('spent_minutes');

        $remaining = max($allowed - $spent, 0);
        $exceeded = max($spent - $allowed, 0);

        // Format for UI
        $format = fn($mins) =>
        $mins >= 60
            ? floor($mins / 60) . ' hr ' . ($mins % 60) . ' min'
            : $mins . ' min';

        return [
            'shift_date' => $shiftDate,
            'allowed_break_minutes' => $allowed,
            'total_spent_minutes' => $spent,
            'remaining_minutes' => $remaining,
            'exceeded_minutes' => $exceeded,
            'total_spent' => $format($spent),
            'remaining' => $format($remaining),
            'exceeded' => $format($exceeded),
        ];
    } catch (\Exception $e) {
        \Log::error('getEmployeeBreakDetailHelper Error', [
            'emp_id' => $employeeId,
            'error' => $e->getMessage()
        ]);

        return [
            'shift_date' => now()->toDateString(),
            'allowed_break_minutes' => 0,
            'total_spent_minutes' => 0,
            'remaining_minutes' => 0,
            'exceeded_minutes' => 0,
            'total_spent' => '0 minutes',
            'remaining' => '0 minutes',
            'exceeded' => '0 minutes',
        ];
    }
}

if (!function_exists('get_clockouts_for_pusher')) {
    function get_clockouts_for_pusher($employeeId, $date = null)
    {
        // Use the unified/enhanced helper from helpers.php
        if (function_exists('standardize_break_data_for_pusher')) {
            // Find active break if any
            $activeBreak = \App\Models\EmployeeBreak::where('emp_id', $employeeId)
                ->whereIn('status', ['On Break', 'On break', 'Ongoing'])
                ->latest()
                ->first();
                
            return standardize_break_data_for_pusher($employeeId, $date, $activeBreak?->id);
        }
        
        // Fallback to legacy if helper not found (shouldn't happen)
        return get_clockouts($employeeId, $date);
    }
}

if (!function_exists('web_calculate_break_stats')) {
    function web_calculate_break_stats($employeeId, $providedShiftDate = null)
    {
        try {
            $timezone = (function_exists('get_employee_settings') ? get_employee_settings($employeeId, 'time_zone') : null) 
                        ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

            // Resolve shift date if not provided
            $shiftDate = $providedShiftDate;
            if (!$shiftDate) {
                $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)
                    ->with('shift')
                    ->latest('assigned_at')
                    ->first();
                $shiftDate = $employeeShift ? web_resolve_shift_date($employeeShift) : date('Y-m-d');
            }

            // Use effective break duration (halved on half-day)
            $allowed = get_effective_break_minutes((int) $employeeId, $shiftDate);

            // Get completed general breaks for this shift date
            $completedBreaks = \App\Models\EmployeeBreak::where('emp_id', $employeeId)
                ->where('shift_date', $shiftDate)
                ->where('type', 'General') // FIX: User requested General only
                ->where('status', 'Completed')
                ->get();

            $spentMinutes = (int) $completedBreaks->sum(function($b) {
                // SANITY CHECK: Any single break > 2 hours is treated as corrupted (0m)
                return ($b->spent_minutes > 120) ? 0 : $b->spent_minutes;
            });

            // Check for active "On Break" status
            $activeBreak = \App\Models\EmployeeBreak::where('emp_id', $employeeId)
                ->where('shift_date', $shiftDate)
                ->whereIn('status', ['On Break', 'Ongoing', 'On break'])
                ->whereNull('end_time')
                ->first();

            $isOnBreak = false;
            $ongoingMinutes = 0;
            if ($activeBreak) {
                $isOnBreak = true;
                // Calculate duration since start
                $start = \Carbon\Carbon::parse($activeBreak->start_time, $timezone);
                $now = \Carbon\Carbon::now($timezone);
                $ongoingMinutes = (int) $start->diffInMinutes($now);
                $spentMinutes += $ongoingMinutes;
            }

            $remaining = max($allowed - $spentMinutes, 0);
            $exceeded = max($spentMinutes - $allowed, 0);

            return [
                'allowed' => $allowed,
                'spent' => $spentMinutes,
                'remaining' => $remaining,
                'exceeded' => $exceeded,
                'isOnBreak' => $isOnBreak,
                'ongoingMinutes' => $ongoingMinutes,
                'percent' => $allowed > 0 ? min(($spentMinutes / $allowed) * 100, 100) : 0,
            ];
        } catch (\Exception $e) {
            \Log::error('web_calculate_break_stats Error: ' . $e->getMessage());
            return [
                'allowed' => 45,
                'spent' => 0,
                'remaining' => 45,
                'exceeded' => 0,
                'isOnBreak' => false,
                'ongoingMinutes' => 0,
                'percent' => 0,
            ];
        }
    }
}
