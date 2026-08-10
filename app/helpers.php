<?php

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Leave;
use App\Models\Setting;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use App\Models\EmployeeBreak;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\EmployeeShift;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


if (!function_exists('calendar_days_until')) {
    /**
     * Whole calendar days from $from to $to (timezone-safe).
     * Avoids Carbon float truncating mid-day diffs to "Tomorrow" when 2 days remain.
     */
    function calendar_days_until($from, $to): int
    {
        $from = $from instanceof Carbon ? $from->copy() : Carbon::parse($from);
        $to = $to instanceof Carbon ? $to->copy() : Carbon::parse($to);

        return (int) $from->startOfDay()->diffInDays($to->startOfDay(), false);
    }
}

if (!function_exists('get_app_settings')) {
    function get_app_settings($key = null)
    {
        $settings = Cache::remember('app_settings', 60, function () {
            return AppSetting::pluck('value', 'key')->toArray();
        });

        $formatted = [
            'company_name' => $settings['company_name'] ?? 'Your Company Name',
            'app_logo' => $settings['app_logo'] ?? '/images/logo.png',
            'break_duration' => $settings['break_duration'] ?? '30',
            'late_minutes' => $settings['late_minutes'] ?? '15',
            'half_day_allowed_in_month' => $settings['half_day_allowed_in_month'] ?? '2',
            'full_day_allowed_in_month' => $settings['full_day_allowed_in_month'] ?? '20',
            'app_timezone' => $settings['app_timezone'] ?? config('app.timezone', 'Asia/Karachi'),
            'leaves_allowed_in_year' => (int) ($settings['leaves_allowed_in_year'] ?? 24),

        ];

        return $key ? ($formatted[$key] ?? null) : $formatted;
    }
}

if (!function_exists('get_today_attendance_status')) {
    function get_today_attendance_status($employee)
    {
        return Attendance::where('emp_id', $employee->id)
            ->whereDate('check_in', Carbon::now(get_app_settings('app_timezone'))->toDateString())
            ->exists()
            ? 'Checked In'
            : 'Not Checked In';
    }
}

if (!function_exists('get_assigned_shift')) {
    function get_assigned_shift($employee)
    {
        return Shift::find($employee->shift_id); // Or your logic like AssignedShift::where(...)
    }
}

if (!function_exists('handleHalfDayDeduction')) {
    function handleHalfDayDeduction($employeeId, $referenceDate, $shift, $lateReason, $now, $allowedHalfDays, $halfDayCount)
    {
        Log::info('handleHalfDayDeduction called', [
            'employeeId' => $employeeId,
            'referenceDate' => $referenceDate,
            'shiftId' => $shift->id,
            'lateReason' => $lateReason,
            'allowedHalfDays' => $allowedHalfDays,
            'halfDayCount' => $halfDayCount,
        ]);

        // UNIFIED POLICY: Every half-day ALWAYS deducts 0.5 from annual leave.
        // half_day_allowed_in_month is a LIMIT (max allowed), not a free quota.
        $leaveType = 'annual';
        $year = $now->year;

        $allocatedLeaves = (int) (get_employee_settings($employeeId, 'leavesAllowedInYear') ?? 16);

        Log::info('Creating or updating LeaveBalance', [
            'employeeId' => $employeeId,
            'leaveType' => $leaveType,
            'year' => $year,
            'allocatedLeaves' => $allocatedLeaves,
        ]);

        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type' => $leaveType,
                'year' => $year,
            ],
            [
                'allocated' => $allocatedLeaves,
                'used' => 0,
                'remaining' => $allocatedLeaves,
            ]
        );

        $balance->used += 0.5;
        $balance->remaining -= 0.5;
        $balance->save();

        Log::info('LeaveBalance updated', [
            'balanceId' => $balance->id,
            'used' => $balance->used,
            'remaining' => $balance->remaining,
        ]);

        // Create leave record
        $leave = new Leave();
        $leave->employee_id = $employeeId;
        $leave->shift_id = $shift->id;
        $leave->leave_type = $leaveType;
        $leave->start_date = $referenceDate;
        $leave->end_date = $referenceDate;
        $leave->status = 'Approved';
        $leave->reason = 'Auto deducted half-day from late check-in: ' . ($lateReason ?? '');
        $leave->approved_by = null; // System approved
        $leave->is_balance_deducted = 1;
        $leave->day_type = 'first_half'; // Missed first half due to late
        $leave->created_at = $now;
        $leave->updated_at = $now;
        $leave->save();

        Log::info('Leave record created', [
            'leaveId' => $leave->id,
            'reason' => $leave->reason,
        ]);
    }
}


if (!function_exists('get_leave_stats')) {
    function get_leave_stats($employee)
    {
        $employeeId = $employee->id;
        $year = now()->year;
        $cacheKey = "leave_stats:v2:{$employeeId}:{$year}";

        return Cache::remember($cacheKey, 300, function () use ($employee, $employeeId, $year) {
            return compute_leave_stats($employee, $employeeId, $year);
        });
    }
}

if (!function_exists('compute_leave_stats')) {
    function compute_leave_stats($employee, int $employeeId, int $year)
    {
        $tz = ($employeeId ? get_employee_settings($employeeId, 'time_zone') : null) ?? get_app_settings('app_timezone') ?? 'Asia/Karachi';
        $nowTz = \Carbon\Carbon::now($tz);
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($nowTz);
        $periodStart = $period['start']->copy()->startOfDay();
        $periodEnd = $period['end']->copy()->endOfDay();

        /* ---------------------------------------------------------
           1. Fetch ALL leaves for this employee & year
        --------------------------------------------------------- */
        $leaves = \App\Models\Leave::where('employee_id', $employeeId)
            ->whereYear('start_date', $year)
            ->get(['status', 'day_type', 'start_date', 'end_date', 'reason', 'leave_type']);

        $pendingCount = \App\Models\Leave::where('employee_id', $employeeId)
            ->where('status', 'Pending')
            ->whereYear('start_date', $year)
            ->count();

        // Data Structure for New SaaS Display
        $stats = [
            'approved' => [
                'month' => ['full' => 0, 'half' => 0, 'total' => 0],
                'year' => ['full' => 0, 'half' => 0, 'total' => 0],
            ],
            'cancelled' => [ // Maps to Rejected / Absent (Unpaid)
                'month' => ['full' => 0, 'half' => 0, 'total' => 0],
                'year' => ['full' => 0, 'half' => 0, 'total' => 0],
            ],
            'half_days' => [ // For quick reference of total half-days
                'month' => 0,
                'year' => 0
            ]
        ];

        foreach ($leaves as $leave) {
            $start = \Carbon\Carbon::parse($leave->start_date);
            $end = \Carbon\Carbon::parse($leave->end_date);
            $isCurrentMonth = $start->between($periodStart, $periodEnd);

            // Determine categorization: Approved vs Cancelled(Rejected)
            $category = null;
            if ($leave->status === 'Approved') {
                $category = 'approved';
            } elseif ($leave->status === 'Rejected') {
                $category = 'cancelled';
            }

            if ($category) {
                if ($leave->day_type === 'first_half' || $leave->day_type === 'second_half') {
                    // UNIFIED POLICY: Count ALL approved half-day leaves (both manual and auto-deducted)
                    // No skip needed — every half-day now creates a Leave record.

                    // Add to Year
                    $stats[$category]['year']['half'] += 1;
                    $stats[$category]['year']['total'] += 0.5;
                    $stats['half_days']['year'] += 1;

                    // Add to Month
                    if ($isCurrentMonth) {
                        $stats[$category]['month']['half'] += 1;
                        $stats[$category]['month']['total'] += 0.5;
                        $stats['half_days']['month'] += 1;
                    }

                } else {
                    // Full day leave
                    // Count working days only (configured off days don't consume leave)
                    $days = $start->diffInDaysFiltered(
                        fn($date) => \App\Services\WorkingDayService::isWorkingDayForEmployee($date, $employee),
                        $end
                    ) + 1;

                    // Add to Year
                    $stats[$category]['year']['full'] += $days;
                    $stats[$category]['year']['total'] += $days;

                    // Add to Month
                    if ($isCurrentMonth) {
                        $stats[$category]['month']['full'] += $days;
                        $stats[$category]['month']['total'] += $days;
                    }
                }
            }
        }

        // NOTE: HalfDay table records are no longer separately counted here.
        // Since every half-day now creates a Leave record, the Leave loop above
        // already captures all half-day data. Counting HalfDay records separately
        // would cause double-counting.

        /* ---------------------------------------------------------
           3. Get leave balances (Total and Breakdown)
        --------------------------------------------------------- */
        $allBalances = \App\Models\LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get(['leave_type', 'allocated', 'used', 'remaining']);

        $balances = $allBalances->keyBy('leave_type');
        
        $totalAllocated = $allBalances->sum('allocated');
        $totalUsed = $allBalances->sum('used');
        $totalRemaining = $allBalances->sum('remaining');

        // Fallback for annual if missing but we have total_leave_balance in legacy (Optional but safer)
        if ($balances->isEmpty() && $employee->leaves_allowed_in_year > 0) {
            $totalAllocated = $employee->leaves_allowed_in_year;
            $totalRemaining = $totalAllocated - $stats['approved']['year']['total'];
            $totalUsed = $stats['approved']['year']['total'];
        }

        // Note: For backward compatibility with any other parts of the app that rely on old structure (if any)
        // We still provide the old keys, but also provide the new structured nested arrays.
        return [
            // Old keys (legacy)
            'approved_days' => $stats['approved']['year']['total'],
            'half_as_full' => $stats['half_days']['year'],
            'half_days_monthly' => $stats['half_days']['month'],
            'cancelled' => $stats['cancelled']['year']['total'],

            // New Structured SaaS Data
            'approved_data' => $stats['approved'],
            'cancelled_data' => $stats['cancelled'],
            'half_days_data' => $stats['half_days'],

            'leave_balances' => $balances,
            'total_allocated' => $totalAllocated,
            'total_used' => $totalUsed,
            'total_remaining' => $totalRemaining,
            'pending_count' => $pendingCount,
            
            // Desktop App Compatibility
            'halfDays' => $stats['half_days'],
            'leaveBalances' => $balances,
            'totalAllocated' => $totalAllocated,
            'totalUsed' => $totalUsed,
            'totalRemaining' => $totalRemaining,
            'pendingCount' => $pendingCount,
        ];
    }
}





if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        return \App\Models\AppSetting::where('key', $key)->value('value') ?? $default;
    }
}



if (!function_exists('get_effective_break_minutes')) {
    /**
     * Effective break allowance (minutes) for an employee on a given shift date.
     * On half day: use employees.break_allowed_in_half_day, else shift.halfday_break, else floor(full/2).
     * Otherwise: full employee_settings.break_duration (fallback app setting).
     */
    function get_effective_break_minutes(int $employeeId, ?string $date = null): int
    {
        $date = $date ?: now()->toDateString();

        $full = (int) (get_employee_settings($employeeId, 'break_duration')
            ?? (function_exists('get_app_settings') ? get_app_settings('break_duration') : null)
            ?? 0);

        $isHalfDay = \App\Models\HalfDay::query()
            ->where('emp_id', $employeeId)
            ->whereDate('date', $date)
            ->exists();

        if (!$isHalfDay) {
            return max(0, $full);
        }

        // New Business Rule: If full break is 45 or 60, half day break is strictly 30 mins
        if ($full === 45 || $full === 60) {
            return 30;
        }

        $employee = \App\Models\Employee::query()->find($employeeId);
        $half = $employee?->break_allowed_in_half_day;

        if ($half === null || $half === '') {
            $shift = \App\Models\EmployeeShift::query()
                ->where('emp_id', $employeeId)
                ->with('shift')
                ->latest('assigned_at')
                ->first()
                ?->shift;
            $half = $shift?->halfday_break;
        }

        if ($half === null || $half === '') {
            $half = (int) floor($full / 2);
        }

        return max(0, (int) $half);
    }
}

if (!function_exists('calculate_break_stats')) {
    /**
     * Calculate break statistics for an employee on a specific attendance record.
     * @param mixed $employee
     * @param mixed $employeeShift
     * @param string $shiftDate
     * @param int|null $attendanceId (Optional) If provided, strictly filters breaks by this attendance ID.
     */
    function calculate_break_stats($employee, $employeeShift, $shiftDate, $attendanceId = null)
    {
        $allowed = get_effective_break_minutes((int) $employee->id, $shiftDate);

        // Fetch completed breaks - Strictly General breaks for the limit
        $completedQuery = \App\Models\EmployeeBreak::where('emp_id', $employee->id)
            ->where('type', 'General')
            ->whereNotNull('end_time');

        if ($attendanceId) {
            $completedQuery->where('attendance_id', $attendanceId);
        } else {
            $completedQuery->where('shift_date', $shiftDate);
        }

        $completedBreaks = $completedQuery->get();
        // Add safety caps to prevent unrealistic over-calculations ("ghost breaks" explosion)
        $spentGeneralSeconds = $completedBreaks->sum(function($b) {
            // Calculate minutes just like the modal does if spent_minutes is null
            $mins = $b->spent_minutes ?? (int) ceil(\Carbon\Carbon::parse($b->created_at)->diffInMinutes(\Carbon\Carbon::parse($b->end_time)));
            // Cap any corrupted or unclosed 'ghost' completed break at 120 minutes
            return min($mins, 120);
        }) * 60;

        // Add active break duration if any
        $activeBreak = \App\Models\EmployeeBreak::where('emp_id', $employee->id)
            ->where('type', 'General')
            ->whereNull('end_time')
            ->when($attendanceId, function($q) use ($attendanceId) {
                $q->where('attendance_id', $attendanceId);
            }, function($q) use ($shiftDate) {
                $q->where('shift_date', $shiftDate);
            })
            ->latest()
            ->first();

        $activeDurationSeconds = 0;
        if ($activeBreak) {
            $activeDurationSeconds = $activeBreak->calculateBreakTimeInSeconds();
            // Cap active 'ghost' breaks visually at 120 minutes
            if ($activeDurationSeconds > (120 * 60)) {
                $activeDurationSeconds = 120 * 60; 
            }
            $spentGeneralSeconds += $activeDurationSeconds;
        }

        // Working hours logic based on attendance
        $workingSeconds = 0;
        $workingHoursFormatted = "00:00:00";

        $attendance = $attendanceId
            ? \App\Models\Attendance::find($attendanceId)
            : \App\Models\Attendance::where('emp_id', $employee->id)->where('shift_date', $shiftDate)->latest()->first();

        if ($attendance && $attendance->check_in) {
            $tz = (get_employee_settings($employee->id, 'time_zone') ?? 'Asia/Karachi');
            $checkIn = \Carbon\Carbon::parse($attendance->check_in, $tz);
            $checkOut = $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out, $tz) : \Carbon\Carbon::now($tz);

            if ($checkOut->lt($checkIn)) {
                $checkOut->addDay();
            }

            $totalOfficeSeconds = $checkIn->diffInSeconds($checkOut, false);
            $workingSeconds = max(0, $totalOfficeSeconds - $spentGeneralSeconds);

            $hours = floor($workingSeconds / 3600);
            $mins = floor(($workingSeconds % 3600) / 60);
            $secs = $workingSeconds % 60;
            $workingHoursFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }

        $formatMinutes = function ($minutes) {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            $parts = [];
            if ($hours > 0)
                $parts[] = "$hours hour" . ($hours > 1 ? 's' : '');
            if ($mins > 0)
                $parts[] = "$mins minute" . ($mins > 1 ? 's' : '');
            return count($parts) > 0 ? implode(' ', $parts) : '0 minutes';
        };

        $totalSpentMinutes = floor($spentGeneralSeconds / 60);
        $remaining = max($allowed - $totalSpentMinutes, 0);
        $exceeded = max($totalSpentMinutes - $allowed, 0);

        return [
            'shift_date' => $shiftDate,
            'allowed_break_minutes' => $allowed,
            'total_spent_minutes' => $totalSpentMinutes,
            'remaining_minutes' => $remaining,
            'exceeded_minutes' => $exceeded,
            'working_hours' => $workingHoursFormatted,
            'working_minutes' => floor($workingSeconds / 60),
            'total_spent' => $formatMinutes($totalSpentMinutes),
            'remaining' => $formatMinutes($remaining),
            'exceeded' => $formatMinutes($exceeded),
            'active_break_duration' => floor($activeDurationSeconds / 60),
            'on_break' => (bool) $activeBreak,
            'active_break_id' => $activeBreak?->id,
            // Desktop App Compatibility
            'allowedBreakMinutes' => $allowed,
            'totalSpentMinutes' => $totalSpentMinutes,
            'remainingMinutes' => $remaining,
            'exceededMinutes' => $exceeded,
            'workingHours' => $workingHoursFormatted,
            'workingMinutes' => floor($workingSeconds / 60),
            'activeBreakDuration' => floor($activeDurationSeconds / 60),
            'onBreak' => (bool) $activeBreak,
            'activeBreakId' => $activeBreak?->id,
        ];
    }
}



if (!function_exists('get_break_stats')) {
    function get_break_stats($employeeId)
    {
        $timezone = get_app_settings('app_timezone') ?? 'Asia/Karachi';

        // Filter only today's general breaks (if applicable)
        $today = Carbon::now($timezone)->toDateString();

        $breaks = EmployeeBreak::where('emp_id', $employeeId)
            ->whereDate('start_time', $today)
            ->where('type', 'General') // Optional: if you use types
            ->get();

        if ($breaks->isEmpty()) {
            return [];
        }

        return $breaks->map(function ($break) {
            return [
                'id' => $break->id,
                'type' => $break->type,
                'status' => $break->status,
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
                'duration_minutes' => $break->end_time
                    ? ($break->spent_minutes ?? (int) $break->created_at->diffInMinutes(Carbon::parse($break->end_time)))
                    : (int) $break->created_at->diffInMinutes(now_with_timezone()),
            ];
        })->toArray();
    }
}

if (!function_exists('resolve_shift_date')) {

    /**
     * Resolve the shift date for a given employee shift.
     * This handles night shifts and provides a grace period for early/late check-ins.
     */
    function resolve_shift_date($employeeShift)
    {
        if (!$employeeShift || !$employeeShift->shift) {
            return now()->toDateString();
        }

        $shift = $employeeShift->shift;
        $timezone = get_app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = Carbon::now($timezone);

        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        // 1. Calculate Today's Windows
        $startToday = Carbon::parse("{$today->toDateString()} {$shift->start_time}", $timezone);
        $endToday = Carbon::parse("{$today->toDateString()} {$shift->end_time}", $timezone);
        if ($shift->crosses_midnight && $endToday->lt($startToday)) {
            $endToday->addDay();
        }

        // 2. Calculate Yesterday's Windows (if crosses midnight)
        $startYesterday = null;
        $endYesterday = null;
        if ($shift->crosses_midnight) {
            $startYesterday = Carbon::parse("{$yesterday->toDateString()} {$shift->start_time}", $timezone);
            $endYesterday = Carbon::parse("{$yesterday->toDateString()} {$shift->end_time}", $timezone);
            if ($endYesterday->lt($startYesterday)) {
                $endYesterday->addDay();
            }
        }

        // --- DECISION LOGIC ---

        // A. Inside Today's Window
        if ($now->between($startToday, $endToday)) {
            return $today->toDateString();
        }

        // B. Early Check-in for Today (Up to 2 hours before)
        if ($now->lt($startToday) && $now->diffInMinutes($startToday) <= 120) {
            return $today->toDateString();
        }

        // C. Inside Yesterday's Window
        if ($startYesterday && $now->between($startYesterday, $endYesterday)) {
            return $yesterday->toDateString();
        }

        // D. Late Checkout / Grace Period for Yesterday (Up to 2 hours after)
        if ($endYesterday && $now->gt($endYesterday) && $now->diffInMinutes($endYesterday) <= 120) {
            return $yesterday->toDateString();
        }

        // E. Default Fallback
        return $now->toDateString();
    }
}

if (!function_exists('resolve_shift_bounds')) {
    /**
     * Resolve shift start/end datetimes for an employee assignment.
     */
    function resolve_shift_bounds($employeeShift, ?string $referenceDate = null): array
    {
        if (!$employeeShift || !$employeeShift->shift) {
            $timezone = get_app_settings('app_timezone') ?? 'Asia/Karachi';
            $now = Carbon::now($timezone);

            return [
                'reference_date' => $now->toDateString(),
                'timezone' => $timezone,
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ];
        }

        $shift = $employeeShift->shift;
        $empId = $employeeShift->emp_id ?? null;
        $timezone = ($empId ? get_employee_settings($empId, 'time_zone') : null)
            ?? get_app_settings('app_timezone')
            ?? 'Asia/Karachi';

        $referenceDate = $referenceDate ?? resolve_shift_date($employeeShift);

        $shiftStart = Carbon::parse($referenceDate . ' ' . $shift->start_time, $timezone);
        $shiftEnd = Carbon::parse($referenceDate . ' ' . $shift->end_time, $timezone);

        if ($shift->crosses_midnight || $shiftEnd->lte($shiftStart)) {
            $shiftEnd->addDay();
        }

        return [
            'reference_date' => $referenceDate,
            'timezone' => $timezone,
            'start' => $shiftStart,
            'end' => $shiftEnd,
        ];
    }
}

if (!function_exists('resolve_check_in_deadline')) {
    /** Latest allowed check-in (shift end + grace). Aligns with resolve_shift_date 2h grace. */
    function resolve_check_in_deadline($employeeShift, ?string $referenceDate = null): Carbon
    {
        $bounds = resolve_shift_bounds($employeeShift, $referenceDate);
        $empId = $employeeShift->emp_id ?? null;
        $graceMinutes = (int) (
            ($empId ? get_employee_settings($empId, 'check_in_grace_after_shift_end') : null)
            ?? get_app_settings('check_in_grace_after_shift_end')
            ?? 120
        );

        return $bounds['end']->copy()->addMinutes(max($graceMinutes, 0));
    }
}

if (!function_exists('resolve_attendance_status_label')) {
    function resolve_attendance_status_label(Attendance $attendance, ?Employee $employee = null): string
    {
        if ($attendance->halfDay) {
            return 'Half Day';
        }

        if ($attendance->lateArrival) {
            return 'Late';
        }

        if ($attendance->status === 'Absent' || ! $attendance->check_in) {
            if ($employee && \App\Models\CompanyOffDay::getHolidayForEmployee($attendance->shift_date, $employee)) {
                return 'Holiday';
            }

            return 'Absent';
        }

        return 'On Time';
    }
}

if (!function_exists('resolve_attendance_holiday_title')) {
    function resolve_attendance_holiday_title(
        Attendance $attendance,
        ?Employee $employee,
        string $statusLabel
    ): ?string {
        if ($statusLabel !== 'Holiday' || ! $employee) {
            return null;
        }

        $holiday = \App\Models\CompanyOffDay::getHolidayForEmployee($attendance->shift_date, $employee);

        return $holiday
            ? ($holiday->note ?: ($holiday->title ?: 'Holiday'))
            : 'Holiday';
    }
}

if (!function_exists('clear_employee_app_data_cache')) {
    function clear_employee_app_data_cache(int $empId): void
    {
        Cache::forget("app_data_refresh:v5:{$empId}");
        Cache::forget("app_data_refresh:v4:{$empId}");
        Cache::forget("app_data_refresh:v3:{$empId}");
        Cache::forget("app_data_refresh:v2:{$empId}");

        $tz = get_employee_settings($empId, 'time_zone') ?? get_app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = \Carbon\Carbon::now($tz);
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($now);
        $payrollMonthKey = $period['payroll_year'] . '-' . str_pad($period['payroll_month'], 2, '0', STR_PAD_LEFT);

        $holidayVersion = md5(
            (string) \App\Models\CompanyOffDay::query()->max('updated_at')
            .':'.\App\Models\CompanyOffDay::query()->count()
        );
        $leaveVersion = md5(
            (string) \App\Models\Leave::query()->where('employee_id', $empId)->max('updated_at')
            .':'.\App\Models\Leave::query()->where('employee_id', $empId)->count()
        );

        Cache::forget("attendance_stats_month:v14:{$empId}:{$payrollMonthKey}:{$holidayVersion}:{$leaveVersion}");
        Cache::forget("attendance_stats_month:v13:{$empId}:{$payrollMonthKey}:{$holidayVersion}:{$leaveVersion}");

        $monthKey = date('Y-m');
        Cache::forget("attendance_stats_month:v2:{$empId}:{$monthKey}");
        Cache::forget('attendance_stats_month:' . $empId . ':' . $monthKey);
        Cache::forget("leave_stats:v2:{$empId}:" . date('Y'));
        Cache::forget('leave_stats:' . $empId . ':' . date('Y'));
    }
}

if (!function_exists('clear_employee_settings_cache')) {
    function clear_employee_settings_cache(int $empId): void
    {
        Cache::forget("employee_settings:{$empId}");
        Cache::forget("employee_active_shift:{$empId}");
        clear_employee_app_data_cache($empId);
    }
}

if (!function_exists('warm_employee_settings_cache')) {
    /**
     * Bulk-load uncached employee settings in one query (admin dashboard).
     */
    function warm_employee_settings_cache(array $empIds): void
    {
        $empIds = array_values(array_unique(array_map('intval', array_filter($empIds))));
        if ($empIds === []) {
            return;
        }

        $uncached = array_values(array_filter(
            $empIds,
            static fn (int $id) => ! Cache::has("employee_settings:{$id}")
        ));

        if ($uncached === []) {
            return;
        }

        $grouped = DB::table('employee_settings')
            ->whereIn('emp_id', $uncached)
            ->get()
            ->groupBy('emp_id');

        foreach ($uncached as $empId) {
            $settings = isset($grouped[$empId])
                ? $grouped[$empId]->pluck('setting_value', 'setting_name')->toArray()
                : [];
            Cache::put("employee_settings:{$empId}", $settings, 300);
        }
    }
}

if (!function_exists('get_employee_settings')) {
    /**
     * Get employee-specific settings by employee ID
     *
     * @param int $empId
     * @param string|null $key Optional: specific setting key
     * @return mixed Array of settings or single value
     */
    function get_employee_settings(int $empId, ?string $key = null)
    {
        $settings = Cache::remember("employee_settings:{$empId}", 300, function () use ($empId) {
            return DB::table('employee_settings')
                ->where('emp_id', $empId)
                ->pluck('setting_value', 'setting_name')
                ->toArray();
        });

        // Default values if DB missing any
        $defaults = [
            'break_duration' => (int) get_app_settings('break_duration'),
            'late_grace_minutes' => 10,
            'idle_time_allowed' => 5,
            'half_day_allowed_in_month' => 2,
            'full_day_allowed_in_month' => 2,
            'leaves_allowed_in_year' => 16,
            'mark_half_day_after' => 120,
        ];

        // Merge DB + defaults (DB overrides defaults)
        $finalSettings = array_merge($defaults, $settings);

        // Map to camelCase for frontend compatibility
        $mapped = [
            'breakDuration' => $finalSettings['break_duration'] ?? null,
            'halfDayAllowedInMonth' => $finalSettings['half_day_allowed_in_month'] ?? null,
            'fullDayAllowedInMonth' => $finalSettings['full_day_allowed_in_month'] ?? null,
            'leavesAllowedInYear' => $finalSettings['leaves_allowed_in_year'] ?? null,
            'lateGraceMinutes' => $finalSettings['late_grace_minutes'] ?? null,
            'idleTimeAllowed' => $finalSettings['idle_time_allowed'] ?? null,
            'markHalfDayAfter' => $finalSettings['mark_half_day_after'] ?? null,
            'appRespGraceMinutes' => $finalSettings['app_resp_grace_minutes'] ?? null,
            'timeZone' => $finalSettings['time_zone'] ?? null,

            // Reverse map (snake to camel already done, now ensure snake from camel if needed)
            'idle_time_allowed' => $finalSettings['idle_time_allowed'] ?? null,
            'mark_half_day_after' => $finalSettings['mark_half_day_after'] ?? null,
            'app_resp_grace_minutes' => $finalSettings['app_resp_grace_minutes'] ?? null,
            'time_zone' => $finalSettings['time_zone'] ?? null,
            'late_grace_minutes' => $finalSettings['late_grace_minutes'] ?? null,
            'half_day_allowed_in_month' => $finalSettings['half_day_allowed_in_month'] ?? null,
            'full_day_allowed_in_month' => $finalSettings['full_day_allowed_in_month'] ?? null,
            'leaves_allowed_in_year' => $finalSettings['leaves_allowed_in_year'] ?? null,
            'break_duration' => $finalSettings['break_duration'] ?? null,
        ];

        $finalSettings = array_merge($finalSettings, $mapped);

        return $key ? ($finalSettings[$key] ?? null) : $finalSettings;
    }
}



if (!function_exists('get_employee_dashboard_data')) {
    function get_employee_dashboard_data($employee)
    {
        $attendanceStatus = 'Not Checked In';
        $todayStatus = 'Not Checked In';
        $attendanceStats = [
            'total_work_days' => 0,
            'present_days' => 0,
            'on_time_days' => 0,
            'late_days' => 0,
            'on_time_percentage' => 0,
            'today_status' => 'Not Checked In',
        ];
        $assignedShift = null;
        $shiftDate = null;
        $leaveStats = [];
        $breakStats = [];
        $isOnBreak = false;

        if ($employee) {
            $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employee->id)->with('shift')->first();

            if ($employeeShift && $employeeShift->shift) {
                $shift = $employeeShift->shift;

                $assignedShift = [
                    'shift_name' => $shift->shift_name,
                    'crosses_midnight' => $shift->crosses_midnight,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                ];

                $shiftDate = resolve_shift_date($employeeShift);
                $leaveStats = get_leave_stats($employee);

                // Always calculate attendance stats (even before checking in)
                $attendanceStats = get_attendance_stats($employee->id, $todayStatus);

                if ($shiftDate) {
                    $attendance = Attendance::where('emp_id', $employee->id)
                        ->where('shift_date', $shiftDate)
                        ->latest()
                        ->first();



                    if ($attendance && $attendance->check_in && ! $attendance->check_out) {
                        $attendanceStatus = 'Checked In';
                        $lateThreshold = (int) (get_employee_settings($employee->id, 'late_grace_minutes') ?? 5);

                        if ($attendance->check_in) {
                            // $todayStatus = app('App\Services\EmployeeService')->getAttendanceStatus(
                            //     $attendance->check_in,
                            //     $shift->start_time,
                            //     60,
                            //     $lateThreshold
                            // );

                            $shiftRecord = \App\Models\EmployeeShift::where('emp_id', $employee->id)->latest()->first();

                            $checkInTime = $attendance->check_in;
                            $shiftStartTime = $shiftRecord->shift->start_time;
                            $shiftEndTime = $shiftRecord->shift->end_time;

                            $todayStatus = getAttendanceStatus(
                                $checkInTime,
                                $shiftStartTime,
                                $shiftEndTime,       // <--- THIS must be shift end time, not earlyThreshold
                                60,        // optional, default 60
                                $lateThreshold          // optional, default 5
                            );

                            // Update today's status in attendance stats
                            $attendanceStats['today_status'] = $todayStatus;
                        }
                    }

                    $breakStats = calculate_break_stats($employee, $employeeShift, $shiftDate);

                    $isOnBreak = \App\Models\EmployeeBreak::where('emp_id', $employee->id)
                        ->where('status', 'On Break')
                        // ->where('type', 'General')
                        ->exists();
                }
            }
        }

        return [
            'attendance_status' => $attendanceStatus,
            'assigned_shift' => $assignedShift,
            'leave_stats' => $leaveStats,
            'attendance_stats' => $attendanceStats,
            'break_stats' => $breakStats,
            'isOnBreak' => $isOnBreak,
        ];
    }
}

if (!function_exists('get_attendance_statsold')) {

    function get_attendance_statsold($employeeId, $todayStatus = 'Not Checked In')
    {
        $now = Carbon::now('Asia/Karachi');
        $today = $now->toDateString();
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($now);
        $startOfMonth = $period['start']->copy();
        $endOfMonth = $period['end']->copy();

        $employee = \App\Models\Employee::find($employeeId);

        // Count total working days (configured off days excluded)
        $workDays = collect(CarbonPeriod::create($startOfMonth, $endOfMonth))
            ->filter(fn($date) => \App\Services\WorkingDayService::isWorkingDayForEmployee($date, $employee))
            ->count();

        // Present attendances on working days
        $presentAttendances = \App\Models\Attendance::where('emp_id', $employeeId)
            ->where('status', 'Present')
            ->whereBetween('shift_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->filter(fn($a) => \App\Services\WorkingDayService::isWorkingDayForEmployee($a->shift_date, $employee));

        $presentDays = $presentAttendances->count();

        // Late arrivals count
        $lateDays = \App\Models\LateArrival::where('emp_id', $employeeId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->filter(fn($l) => \App\Services\WorkingDayService::isWorkingDayForEmployee($l->date, $employee))
            ->count();

        // Derived stats
        $onTimeDays = max($presentDays - $lateDays, 0);
        $onTimePercentage = $presentDays > 0
            ? round(($onTimeDays / $presentDays) * 100)
            : 0;

        $todayStatus = getTodayAttendanceStatus($employeeId, $presentAttendances);


        return [
            'total_work_days' => $workDays,
            'present_days' => $presentDays,
            'on_time_days' => $onTimeDays,
            'late_days' => $lateDays,
            'on_time_percentage' => $onTimePercentage,
            'today_status' => $todayStatus,
        ];
    }
}


function getTodayAttendanceStatus($employeeId, $presentAttendances)
{
    $today = Carbon::now('Asia/Karachi')->startOfDay();
    $yesterday = $today->clone()->subDay();
    $todayStr = $today->toDateString();
    $yesterdayStr = $yesterday->toDateString();

    // Get employee's current assigned shift from employee_shifts (latest one)
    $assignedShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)
        ->orderBy('assigned_at', 'desc')
        ->first();

    if (!$assignedShift) {
        return 'No Shift Assigned';
    }

    $assignedShiftId = $assignedShift->shift_id;

    // Find attendance that covers today and matches assigned shift
    $hasAttendance = $presentAttendances->first(function ($attendance) use ($todayStr, $yesterdayStr, $assignedShiftId) {
        if ($attendance->shift_id != $assignedShiftId) {
            return false; // Only consider if matches assigned shift
        }

        $shiftDateStr = Carbon::parse($attendance->shift_date)->toDateString();

        $shift = Shift::find($attendance->shift_id);
        if (!$shift) {
            return false;
        }

        if ($shiftDateStr === $todayStr) {
            return true;
        } elseif ($shiftDateStr === $yesterdayStr && $shift->crosses_midnight == 1) {
            return true;
        }

        return false;
    });

    if (!$hasAttendance) {
        return 'Not Checked In';
    }

    // Check late on the shift_date (start date)
    $lateDate = $hasAttendance->shift_date;

    $isLate = \App\Models\LateArrival::where('emp_id', $employeeId)
        ->whereDate('date', $lateDate)
        ->exists();

    return $isLate ? 'Late' : 'On Time';
}


if (!function_exists('get_attendance_stats')) {

    function get_attendance_stats($employeeId, $todayStatus = 'Not Checked In')
    {
        $tz = (get_employee_settings($employeeId, 'time_zone') ?? get_app_settings('app_timezone') ?? 'Asia/Karachi');
        $now = \Carbon\Carbon::now($tz);
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($now);
        $monthKey = $period['payroll_year'] . '-' . str_pad($period['payroll_month'], 2, '0', STR_PAD_LEFT);

        $employee = \App\Models\Employee::find($employeeId);

        // Holiday fingerprint in the cache key: any add/edit/delete of company
        // off days invalidates the cached stats immediately (instead of waiting
        // for end-of-day expiry and showing stale ABSENT counts on the app).
        $holidayVersion = md5(
            (string) \App\Models\CompanyOffDay::query()->max('updated_at')
            .':'.\App\Models\CompanyOffDay::query()->count()
        );
        $leaveVersion = md5(
            (string) \App\Models\Leave::query()->where('employee_id', $employeeId)->max('updated_at')
            .':'.\App\Models\Leave::query()->where('employee_id', $employeeId)->count()
        );

        $monthStats = Cache::remember(
            "attendance_stats_month:v14:{$employeeId}:{$monthKey}:{$holidayVersion}:{$leaveVersion}",
            $now->copy()->endOfDay(),
            function () use ($employeeId, $now, $employee, $tz, $period) {
                $startOfMonth = $period['start']->copy();
                $limitDate = $now->copy()->startOfDay();

                // Do not count days before joining as scheduled / missed.
                if ($employee?->joining_date) {
                    $joining = \Carbon\Carbon::parse($employee->joining_date, $tz)->startOfDay();
                    if ($joining->gt($startOfMonth)) {
                        $startOfMonth = $joining;
                    }
                }

                // Build the set of holiday / company-off dates for this employee
                // (team + branch scoped — same as recent-attendance APIs).
                $holidayDates = [];
                $holidayRecords = \App\Models\CompanyOffDay::holidaysForEmployeeInRange(
                    $employee,
                    $startOfMonth->toDateString(),
                    $limitDate->toDateString()
                );
                foreach ($holidayRecords as $holiday) {
                    $range = \Carbon\CarbonPeriod::create(
                        max(\Carbon\Carbon::parse($holiday['start_date'])->startOfDay(), $startOfMonth),
                        min(\Carbon\Carbon::parse($holiday['end_date'])->startOfDay(), $limitDate)
                    );
                    foreach ($range as $day) {
                        $holidayDates[$day->toDateString()] = true;
                    }
                }

                // Any other company off day types for this team (e.g. custom offs).
                if ($employee) {
                    $extraOffs = \App\Models\CompanyOffDay::query()
                        ->whereDate('start_date', '<=', $limitDate->toDateString())
                        ->whereDate('end_date', '>=', $startOfMonth->toDateString())
                        ->where('type', '!=', 'Holiday')
                        ->forTeam($employee->team_id)
                        ->forBranch($employee->branch_id)
                        ->get(['start_date', 'end_date']);
                    foreach ($extraOffs as $off) {
                        $range = \Carbon\CarbonPeriod::create(
                            max(\Carbon\Carbon::parse($off->start_date)->startOfDay(), $startOfMonth),
                            min(\Carbon\Carbon::parse($off->end_date)->startOfDay(), $limitDate)
                        );
                        foreach ($range as $day) {
                            $holidayDates[$day->toDateString()] = true;
                        }
                    }
                }

                $isWorkDay = function ($date) use ($holidayDates, $employee) {
                    return \App\Services\WorkingDayService::isWorkingDayForEmployee($date, $employee)
                        && ! isset($holidayDates[$date->toDateString()]);
                };

                // Full-day leave (approved and disapproved/rejected) must not inflate "missed" absents,
                // EXCEPT system auto-marked absence records (e.g. "Absent (Auto-marked by System)").
                $leaveDates = [];
                $leaves = \App\Models\Leave::query()
                    ->where('employee_id', $employeeId)
                    ->whereIn('status', ['Approved', 'Disapproved', 'Rejected'])
                    ->where(function ($q) {
                        $q->whereNull('reason')
                          ->orWhere(function ($q2) {
                              $q2->where('reason', 'not like', '%Absent%')
                                 ->where('reason', 'not like', '%absent%')
                                 ->where('reason', 'not like', '%Auto-marked%')
                                 ->where('reason', 'not like', '%auto-marked%');
                          });
                    })
                    ->whereDate('start_date', '<=', $limitDate->toDateString())
                    ->whereDate('end_date', '>=', $startOfMonth->toDateString())
                    ->get(['start_date', 'end_date', 'day_type']);

                foreach ($leaves as $leave) {
                    $dayType = strtolower((string) ($leave->day_type ?? 'full_day'));
                    if (in_array($dayType, ['first_half', 'second_half', 'half_day'], true)) {
                        continue;
                    }
                    $sDate = \Carbon\Carbon::parse($leave->start_date)->timezone($tz)->startOfDay();
                    $eDate = \Carbon\Carbon::parse($leave->end_date)->timezone($tz)->startOfDay();
                    $range = \Carbon\CarbonPeriod::create(
                        max($sDate, $startOfMonth),
                        min($eDate, $limitDate)
                    );
                    foreach ($range as $day) {
                        if ($isWorkDay($day)) {
                            $leaveDates[$day->toDateString()] = true;
                        }
                    }
                }

                $workDayDates = collect(\Carbon\CarbonPeriod::create($startOfMonth, $limitDate))
                    ->filter($isWorkDay)
                    ->map(fn ($d) => $d->toDateString())
                    ->values();

                $workDays = $workDayDates->count();

                $presentDateSet = \App\Models\Attendance::where('emp_id', $employeeId)
                    ->whereBetween('shift_date', [$startOfMonth->toDateString(), $limitDate->toDateString()])
                    ->whereNotNull('check_in')
                    ->get()
                    ->filter(function ($a) use ($isWorkDay, $tz) {
                        return $isWorkDay(\Carbon\Carbon::parse($a->shift_date)->timezone($tz));
                    })
                    ->pluck('shift_date')
                    ->map(fn ($d) => \Carbon\Carbon::parse($d)->timezone($tz)->toDateString())
                    ->unique();

                $presentDays = $presentDateSet->count();

                $lateDays = \App\Models\LateArrival::where('emp_id', $employeeId)
                    ->whereBetween('date', [$startOfMonth->toDateString(), $limitDate->toDateString()])
                    ->get()
                    ->filter(fn ($l) => $isWorkDay(\Carbon\Carbon::parse($l->date)))
                    ->count();

                $lateDaysYearly = \App\Models\LateArrival::where('emp_id', $employeeId)
                    ->whereYear('date', $now->year)
                    ->get()
                    ->filter(fn ($l) => \App\Services\WorkingDayService::isWorkingDayForEmployee($l->date, $employee))
                    ->count();

                $onTimeDays = max($presentDays - $lateDays, 0);
                $onTimePercentage = $presentDays > 0 ? round(($onTimeDays / $presentDays) * 100) : 0;

                // Missed days = workdays without check-in and without approved leave.
                // Today's shift still open and not checked-in is not yet a miss.
                $todayKey = $limitDate->toDateString();
                $excludeTodayFromAbsent = false;
                if (
                    $workDayDates->contains($todayKey)
                    && ! $presentDateSet->contains($todayKey)
                    && ! isset($leaveDates[$todayKey])
                ) {
                    $shiftRecord = \App\Models\EmployeeShift::where('emp_id', $employeeId)->latest()->first();
                    $shiftEnded = false;
                    if ($shiftRecord?->shift?->end_time) {
                        $shiftEnd = \Carbon\Carbon::parse($todayKey.' '.$shiftRecord->shift->end_time, $tz);
                        $shiftStart = \Carbon\Carbon::parse($todayKey.' '.$shiftRecord->shift->start_time, $tz);
                        if ($shiftEnd->lt($shiftStart)) {
                            $shiftEnd->addDay();
                        }
                        $shiftEnded = $now->gte($shiftEnd);
                    }
                    $explicitAbsentToday = \App\Models\Attendance::where('emp_id', $employeeId)
                        ->whereDate('shift_date', $todayKey)
                        ->where('status', 'Absent')
                        ->exists();

                    $excludeTodayFromAbsent = ! $shiftEnded && ! $explicitAbsentToday;
                }

                $absentCount = 0;
                foreach ($workDayDates as $dateStr) {
                    if ($presentDateSet->contains($dateStr)) {
                        continue;
                    }
                    if (isset($leaveDates[$dateStr])) {
                        continue;
                    }
                    if ($dateStr === $todayKey && $excludeTodayFromAbsent) {
                        continue;
                    }
                    $absentCount++;
                }

                $absentDays = (string) $absentCount;

                // --- Old Desktop App Backward Compatibility ---
                // Old app versions do NOT read `absentDays` from backend.
                // Instead they calculate: absent = elapsedWorkDays(from month start) - presentDays.
                // We compute a "fake" presentDays so that the old app's formula
                // yields the correct $absentCount.
                // fakePresentDays = elapsedFromMonthStart - absentCount
                // => old app: absent = elapsedFromMonthStart - fakePresentDays = absentCount ✓
                $tz = get_employee_settings($employeeId, 'time_zone') ?? get_app_settings('app_timezone') ?? 'Asia/Karachi';
                $fakePresentDays = max(0, $workDays - (int) $absentCount);
                // --- End Backward Compatibility ---

                return [
                    'total_work_days' => $workDays,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'leave_days' => count($leaveDates),
                    'on_time_days' => $onTimeDays,
                    'late_days' => $lateDays,
                    'late_days_yearly' => $lateDaysYearly,
                    'on_time_percentage' => $onTimePercentage,
                    // Desktop App Compatibility
                    'totalWorkDays' => $workDays,
                    'presentDays' => $fakePresentDays, // Adjusted for old app's absent formula
                    'absentDays' => $absentDays,       // New apps read this directly
                    'leaveDays' => count($leaveDates),
                    'onTimeDays' => $onTimeDays,
                    'lateDays' => $lateDays,
                    'lateDaysYearly' => $lateDaysYearly,
                    'onTimePercentage' => $onTimePercentage,
                ];
            }
        );

        $attendanceId = null;
        $attendanceToday = null;

        $shiftRecord = \App\Models\EmployeeShift::where('emp_id', $employeeId)->latest()->first();

        if ($shiftRecord && $shiftRecord->shift) {
            $shift = $shiftRecord->shift;
            $referenceDate = resolve_shift_date($shiftRecord);

            $attendanceToday = Attendance::where('emp_id', $employeeId)
                ->where('shift_date', $referenceDate)
                ->first();

            if ($attendanceToday && $attendanceToday->check_in && ! $attendanceToday->check_out) {
                $attendanceId = $attendanceToday->id;
                $lateThreshold = (int) (get_employee_settings($employeeId, 'late_grace_minutes') ?? 5);

                $todayStatus = getAttendanceStatus(
                    $attendanceToday->check_in,
                    $shift->start_time,
                    $shift->end_time,
                    60,
                    $lateThreshold,
                    $employeeId,
                    $referenceDate
                );
            } elseif ($attendanceToday && $attendanceToday->check_out) {
                $todayStatus = 'Checked Out';
            }
        }

        return array_merge($monthStats, [
            'today_status' => $todayStatus,
            'attendance_id' => $attendanceId,
            'check_in' => ($attendanceToday && ! $attendanceToday->check_out) ? $attendanceToday->check_in : null,
            'check_out' => $attendanceToday?->check_out,
            // Desktop App Compatibility
            'todayStatus' => $todayStatus,
            'attendanceId' => $attendanceId,
            'checkIn' => ($attendanceToday && ! $attendanceToday->check_out) ? $attendanceToday->check_in : null,
            'checkOut' => $attendanceToday?->check_out,
        ]);
    }
}

function getAttendanceStatus($checkInTime, $shiftStartTime, $shiftEndTime, $earlyThresholdMinutes = 60, $lateThresholdMinutes = 5, $empId = null, $shiftDate = null)
{
    $checkIn = Carbon::parse($checkInTime);
    $earlyThresholdMinutes = (int) $earlyThresholdMinutes;
    $lateThresholdMinutes = (int) $lateThresholdMinutes;

    // Determine shift start & end datetime
    $shiftStart = Carbon::parse($checkIn->toDateString() . ' ' . $shiftStartTime);
    $shiftEnd = Carbon::parse($checkIn->toDateString() . ' ' . $shiftEndTime);

    // Adjust for night shift crossing midnight
    if ($shiftEnd->lt($shiftStart)) {
        $shiftEnd->addDay();
    }

    // If check-in is after midnight but shift started previous day
    if ($checkIn->lt($shiftStart) && $checkIn->hour < 12) {
        $shiftStart->subDay();
        $shiftEnd->subDay();
    }

    // Thresholds
    $earlyThreshold = $shiftStart->copy()->subMinutes($earlyThresholdMinutes);
    $lateThreshold = $shiftStart->copy()->addMinutes($lateThresholdMinutes);

    // Calculate late minutes
    $lateMinutes = $checkIn->gt($shiftStart->copy()->addMinutes($lateThresholdMinutes))
        ? $shiftStart->diffInMinutes($checkIn)
        : 0;


    // Determine status
    if ($checkIn->lt($earlyThreshold)) {
        return 'Too Early';
    }

    if ($lateMinutes >= 120) {
        // Half Day mark - check if record exists
        if ($empId && $shiftDate) {
            $hasHalfDayRecord = \App\Models\HalfDay::where('emp_id', $empId)
                ->where('date', $shiftDate)
                ->exists();
            if (!$hasHalfDayRecord) {
                return 'On Time'; // Excused/Deleted
            }
        }
        return 'Half Day';
    }

    if ($lateMinutes > 0) {
        // Late mark - check if record exists
        if ($empId && $shiftDate) {
            $hasLateRecord = \App\Models\LateArrival::where('emp_id', $empId)
                ->where('date', $shiftDate)
                ->exists();
            if (!$hasLateRecord) {
                return 'On Time'; // Excused/Deleted
            }
        }
        return 'Late';
    }

    if ($checkIn->between($earlyThreshold, $lateThreshold)) {
        return 'On Time';
    }

    return 'Unknown';
}


if (!function_exists('isEmployeeResigned')) {
    function isEmployeeResigned(Employee $employee)
    {
        return !empty($employee->resign_date);
    }
}

if (!function_exists('isEmployeeCurrentlySuspended')) {
    function isEmployeeCurrentlySuspended(Employee $employee): bool
    {
        if ($employee->exit_type !== 'suspended') {
            return false;
        }

        $today = \Carbon\Carbon::today();
        $start = $employee->suspended_start_date
            ? \Carbon\Carbon::parse($employee->suspended_start_date)->startOfDay()
            : null;
        $end = $employee->suspended_end_date
            ? \Carbon\Carbon::parse($employee->suspended_end_date)->endOfDay()
            : null;

        if ($start && $today->lt($start)) {
            return false;
        }

        if ($end && $today->gt($end)) {
            return false;
        }

        return (bool) ($start || $end || $employee->resign_date);
    }
}

if (!function_exists('getEmployeeAccessBlockReason')) {
    function getEmployeeAccessBlockReason(?Employee $employee): ?string
    {
        if (!$employee || empty($employee->resign_date)) {
            return null;
        }

        if ($employee->exit_type === 'suspended') {
            if (!isEmployeeCurrentlySuspended($employee)) {
                return null;
            }

            $end = $employee->suspended_end_date
                ? \Carbon\Carbon::parse($employee->suspended_end_date)->format('d M, Y')
                : 'the specified date';

            return "Your account is suspended until {$end}. Please contact HR.";
        }

        return 'Your account has been deactivated. Please contact HR.';
    }
}

if (!function_exists('reactivateExpiredSuspensions')) {
    function reactivateExpiredSuspensions(): void
    {
        \Artisan::call('employees:reactivate-suspended');
    }
}
if (!function_exists('standardize_break_data_for_pusher')) {
    function standardize_break_data_for_pusher($employeeId, $shiftDate, $activeBreakId = null, $attendanceId = null)
    {
        $employee = \App\Models\Employee::find($employeeId);
        $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)->latest()->first();

        $stats = calculate_break_stats($employee, $employeeShift, $shiftDate, $attendanceId);

        // Log diagnostic same as before
        \Log::info("standardize_break_data_for_pusher sync logic", [
            'empId' => $employeeId,
            'shiftDate' => $shiftDate,
            'workingHours' => $stats['working_hours'],
            'totalSpent' => $stats['total_spent_minutes']
        ]);

        return $stats;
    }
}

if (!function_exists('standardize_attendance_data_for_pusher')) {
    function standardize_attendance_data_for_pusher($employeeId)
    {
        return (new \App\Services\EmployeeService())->getAppData($employeeId);
    }
}
