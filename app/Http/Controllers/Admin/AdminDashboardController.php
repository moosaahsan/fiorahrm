<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Leave;
use App\Models\User;
use App\Services\AdminCheckoutService;
use App\Services\AttendanceStatusResolver;
use App\Services\DashboardMetricsService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected AdminCheckoutService $adminCheckoutService,
        protected AttendanceStatusResolver $attendanceStatusResolver,
        protected DashboardMetricsService $dashboardMetricsService,
    ) {
    }

    public function index()
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        
        // Date Filter Logic
        $filterDate = request('date'); // User selected date (YYYY-MM-DD)
        $isLiveMode = empty($filterDate);
        $today = $filterDate ?? now($timezone)->toDateString();

        // ✅ Smart Scoping for Dashboard
        // ✅ Total active employees (Scoped via trait)
        $accessibleEmpIds = Employee::accessible()->whereNull('resign_date')->pluck('id')->toArray();
        warm_employee_settings_cache($accessibleEmpIds);
        $totalEmployees = count($accessibleEmpIds);

        $yesterday = Carbon::parse($today)->subDay()->toDateString();

        // Latest shift per employee (single query — avoids N+1 in dashboard table)
        $latestShiftByEmp = EmployeeShift::with('shift')
            ->whereIn('emp_id', $accessibleEmpIds)
            ->orderByDesc('assigned_at')
            ->get()
            ->unique('emp_id')
            ->keyBy('emp_id');

        $appAllowedBreak = (int) (app_settings('break_duration') ?? 45);
        $nowTime = now($timezone);
        
        $thisPeriod = \App\Services\PayrollPeriodService::getPeriodForDate($nowTime);
        $targetDateForLastMonth = \Carbon\Carbon::createFromDate($thisPeriod['payroll_year'], $thisPeriod['payroll_month'], 1)->subMonthNoOverflow();
        $lastPeriod = \App\Services\PayrollPeriodService::forMonth($targetDateForLastMonth->year, $targetDateForLastMonth->month);
        $startOfLastMonth = $lastPeriod['start']->copy();

        $allAttendances = Attendance::with(['lateArrival', 'halfDay', 'breaks'])
            ->whereIn('emp_id', $accessibleEmpIds)
            ->whereIn('shift_date', [$today, $yesterday])
            ->get()
            ->groupBy('emp_id');

        $summaryAttendances = Attendance::with(['lateArrival', 'halfDay', 'breaks'])
            ->whereIn('emp_id', $accessibleEmpIds)
            ->where('shift_date', '>=', $startOfLastMonth->toDateString())
            ->get()
            ->groupBy('emp_id');

        $leaveBalancesByEmp = $this->dashboardMetricsService->batchLeaveBalances(
            $accessibleEmpIds,
            (int) $nowTime->year
        );
        $workingSummariesByEmp = $this->dashboardMetricsService->batchWorkingSummaries(
            $summaryAttendances,
            $timezone,
            $nowTime
        );

        $allBreaks = \App\Models\EmployeeBreak::whereIn('emp_id', $accessibleEmpIds)
            ->whereIn('shift_date', [$today, $yesterday])
            ->get()
            ->groupBy('emp_id');

        $allActiveBreaks = \App\Models\EmployeeBreak::whereIn('emp_id', $accessibleEmpIds)
            ->whereIn('status', ['On Break', 'Ongoing', 'On break'])
            ->whereNull('end_time')
            ->get()
            ->keyBy('emp_id');

        $employeeQuery = Employee::accessible()
            ->with([
                'user:id,name,email',
                'currentShiftAssignment.shift',
            ])
            ->whereNull('resign_date');

        if (app()->environment('production')) {
            $employeeQuery->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('email')
                        ->orWhere(function ($e) {
                            $e->where('email', 'not like', '%@test.local')
                                ->where('email', 'not like', '%@test.com');
                        });
                })->where('name', 'not like', 'Checkout Test%');
            });
        }

        $employees = $employeeQuery->latest()->get();

        $lateToday = 0;
        $halfDayToday = 0;
        $punctualToday = 0;
        $absentToday = 0;
        $scheduledEmployeesCount = 0;

        foreach ($employees as $employee) {
            $employeeShift = $latestShiftByEmp->get($employee->id);
            $effectiveDate = ($isLiveMode && $employeeShift)
                ? web_resolve_shift_date($employeeShift)
                : $today;

            $empAtts = $allAttendances->get($employee->id, collect());
            $attendance = $empAtts->first(function ($att) use ($effectiveDate) {
                return Carbon::parse($att->shift_date)->toDateString() === $effectiveDate;
            });
            $employee->setRelation('attendances', collect([$attendance])->filter());
            $employee->dashboard_shift = $employeeShift;
            $employee->dashboard_shift_date = $effectiveDate;
            $employee->dashboard_attendance = $attendance;
            $employee->dashboard_attendance_id = $attendance?->id;

            $empBreaks = $allBreaks->get($employee->id, collect())->where('shift_date', $effectiveDate);
            $empAllowed = get_effective_break_minutes((int) $employee->id, $effectiveDate);

            $spentMinutes = (int) $empBreaks->where('status', 'Completed')->where('type', 'General')->sum(function ($b) {
                return ($b->spent_minutes > 120) ? 0 : $b->spent_minutes;
            });

            $activeBreak = $allActiveBreaks->get($employee->id);
            $isOnBreak = false;
            $ongoingMinutes = 0;
            $isCheckedOut = (bool) ($attendance && $attendance->check_in && $attendance->check_out);

            if ($activeBreak && ! $isCheckedOut) {
                $isOnBreak = true;
                $start = Carbon::parse($activeBreak->start_time, $timezone);
                $ongoingMinutes = (int) $start->diffInMinutes($nowTime);
                $spentMinutes += $ongoingMinutes;
            }

            $breakStats = [
                'allowed' => $empAllowed,
                'spent' => $spentMinutes,
                'remaining' => max($empAllowed - $spentMinutes, 0),
                'exceeded' => max($spentMinutes - $empAllowed, 0),
                'isOnBreak' => $isOnBreak,
                'ongoingMinutes' => $ongoingMinutes,
                'percent' => $empAllowed > 0 ? min(($spentMinutes / $empAllowed) * 100, 100) : 0,
            ];
            $employee->break_stats = $breakStats;

            $presence = $this->attendanceStatusResolver->resolve(
                $employee,
                $attendance,
                $effectiveDate,
                $breakStats,
                $timezone,
                $nowTime
            );
            $employee->presence_status = $presence;
            $employee->presence_type_calc = $presence['presence_type'];
            $employee->is_checked_in_active = $presence['code'] !== AttendanceStatusResolver::CHECKED_OUT
                && $attendance
                && $attendance->check_in
                && ! $attendance->check_out;
            $employee->is_checked_out_today = $presence['code'] === AttendanceStatusResolver::CHECKED_OUT;
            $employee->checkout_time_label = $presence['checkout_time'] ?? null;
            $employee->last_seen_label = $presence['pulse_line'];
            $employee->is_online_now = $presence['is_online'];
            $employee->leave_balance = $leaveBalancesByEmp->get($employee->id, ['remaining' => 0]);
            $employee->working_summary = $workingSummariesByEmp->get($employee->id, [
                'last_month' => '0 min',
                'this_month' => '0 min',
                'this_week' => '0 min',
                'today' => '0 min',
            ]);
            $employee->employment_status = $this->dashboardMetricsService->employmentStatusLabel($employee);
            $employee->daily_hours_today = ($attendance && $attendance->check_in)
                ? format_minutes($this->dashboardMetricsService->netMinutes($attendance, $timezone, $nowTime))
                : '0 min';

            match ($presence['presence_type']) {
                'punctual', 'on_break', 'break_exceeded', 'connection_lost' => $punctualToday++,
                'late' => $lateToday++,
                'half_day' => $halfDayToday++,
                'absent' => $absentToday++,
                default => null,
            };

            if (in_array($presence['presence_type'], ['punctual', 'late', 'half_day', 'absent', 'checked_out', 'on_break', 'break_exceeded', 'connection_lost'], true)) {
                $scheduledEmployeesCount++;
            }
        }

        $onTimePercentage = $scheduledEmployeesCount > 0
            ? round(($punctualToday / $scheduledEmployeesCount) * 100)
            : 0;

        // ✅ Fetch Birthdays & Work Anniversaries (Next 7 Days)
        $start = now($timezone);
        $end = now($timezone)->addDays(7);

        $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
        $dobSql = $isSqlite ? "strftime('%m-%d', dob)" : "DATE_FORMAT(dob, '%m-%d')";
        $jdSql = $isSqlite ? "strftime('%m-%d', joining_date)" : "DATE_FORMAT(joining_date, '%m-%d')";

        $empQuery = \Illuminate\Support\Facades\Auth::user()->hasAnyRole(['admin', 'hr', 'receptionist']) 
            ? Employee::query() 
            : Employee::accessible();

        $celebrations = $empQuery
            ->whereNull('resign_date')
            ->where(function($query) use ($start, $end, $dobSql, $jdSql) {
                // Birthday logic
                $startMD = $start->format('m-d');
                $endMD = $end->format('m-d');
                $query->where(function($q) use ($startMD, $endMD, $dobSql) {
                    if ($startMD <= $endMD) {
                        $q->whereRaw("$dobSql BETWEEN ? AND ?", [$startMD, $endMD]);
                    } else {
                        $q->whereRaw("$dobSql >= ?", [$startMD])
                          ->orWhereRaw("$dobSql <= ?", [$endMD]);
                    }
                })
                // Anniversary logic
                ->orWhere(function($q) use ($startMD, $endMD, $jdSql) {
                    if ($startMD <= $endMD) {
                        $q->whereRaw("$jdSql BETWEEN ? AND ?", [$startMD, $endMD]);
                    } else {
                        $q->whereRaw("$jdSql >= ?", [$startMD])
                          ->orWhereRaw("$jdSql <= ?", [$endMD]);
                    }
                });
            })
            ->get();

        // 🟢 Process and Merge Events
        $processedCelebrations = collect();

        foreach ($celebrations as $emp) {
            // Check Birthday
            if ($emp->dob) {
                $dob = Carbon::parse($emp->dob, $timezone);
                $bday = $dob->copy()->year($start->year)->startOfDay();
                if ($bday->format('m-d') < $start->format('m-d')) $bday->addYear();

                $daysUntil = calendar_days_until($start, $bday);
                if ($daysUntil >= 0 && $daysUntil <= 7) {
                    $processedCelebrations->push([
                        'employee' => $emp,
                        'type' => 'birthday',
                        'date' => $bday,
                        'days_until' => $daysUntil,
                        'is_today' => $daysUntil === 0,
                    ]);
                }
            }

            // Check Anniversary
            if ($emp->joining_date) {
                $jd = Carbon::parse($emp->joining_date, $timezone);
                $anniv = $jd->copy()->year($start->year)->startOfDay();
                if ($anniv->format('m-d') < $start->format('m-d')) $anniv->addYear();

                $years = $anniv->year - $jd->year;
                $daysUntil = calendar_days_until($start, $anniv);

                // Only count if it's at least the first anniversary
                if ($years > 0 && $daysUntil >= 0 && $daysUntil <= 7) {
                    $processedCelebrations->push([
                        'employee' => $emp,
                        'type' => 'anniversary',
                        'date' => $anniv,
                        'years' => $years,
                        'days_until' => $daysUntil,
                        'is_today' => $daysUntil === 0,
                    ]);
                }
            }
        }

        $latestEotmRecord = \App\Models\Performance\EmployeeOfTheMonth::orderBy('year', 'desc')
                                ->orderBy('month', 'desc')
                                ->first();

        if ($latestEotmRecord) {
            $eotmQuery = \App\Models\Performance\EmployeeOfTheMonth::with(['employee.team', 'employee.user'])
                                ->where('year', $latestEotmRecord->year)
                                ->where('month', $latestEotmRecord->month);

            if (!\Illuminate\Support\Facades\Auth::user()->hasAnyRole(['admin', 'hr', 'receptionist'])) {
                $userTeamId = \Illuminate\Support\Facades\Auth::user()->employee?->team_id ?? null;
                if ($userTeamId) {
                    $eotmQuery->whereHas('employee', function($q) use ($userTeamId) {
                        $q->where('team_id', $userTeamId);
                    });
                }
            }

            $employeesOfTheMonth = $eotmQuery->get();

            foreach ($employeesOfTheMonth as $eotm) {
                $processedCelebrations->push([
                    'employee' => $eotm->employee,
                    'type' => 'eotm',
                    'date' => \Carbon\Carbon::create($eotm->year, $eotm->month, 1),
                    'days_until' => -1,
                    'is_today' => false,
                    'eotm_month' => $eotm->month,
                    'eotm_year' => $eotm->year,
                    'bio_comments' => $eotm->bio_comments,
                ]);
            }
        }

        $sortedCelebrations = $processedCelebrations->sortBy('days_until')->values();

        $canCheckoutEmployee = auth()->user()->can('checkout-employee');

        $viewData = [
            'data' => [
                'total_employees' => $totalEmployees,
                'on_time_today' => $punctualToday,
                'late_today' => $lateToday,
                'half_day_today' => $halfDayToday,
                'absent_today' => $absentToday,
                'on_time_percentage' => round($onTimePercentage),
                'filter_date' => $today,
                'is_live_mode' => $isLiveMode,
                'can_checkout_employee' => $canCheckoutEmployee,
            ],
            'employees' => $employees,
            'celebrations' => $sortedCelebrations,
        ];

        if ($isLiveMode) {
            $metricsCacheKey = 'admin_dashboard_metrics:' . Auth::id() . ':' . $today;
            Cache::put($metricsCacheKey, $viewData['data'], 60);
        }

        return view('admin.dashboard', $viewData);
    }

    public function checkoutEmployee()
    {
        $this->authorize('checkout-employee');

        request()->validate([
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        try {
            $result = $this->adminCheckoutService->checkout((int) request('employee_id'));

            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (\Throwable $e) {
            Log::error('Admin checkout employee failed', [
                'employee_id' => request('employee_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Checkout could not be completed. Please try again.',
            ], 500);
        }
    }
}
