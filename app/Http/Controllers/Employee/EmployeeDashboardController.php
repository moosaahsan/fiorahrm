<?php
// app/Http/Controllers/Employee/EmployeeDashboardController.php
namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use App\Models\LateArrival;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeDashboardController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function index(Request $request)
    {
        $employee = $this->employeeService->getByUserId(auth()->id());
        $dashboardData = $this->employeeService->getAppData($employee->id);
        
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        
        // Date Filter Logic
        $filterDate = request('date');
        $isLiveMode = empty($filterDate);
        $today = $filterDate ?? now($timezone)->toDateString();
        
        $now = now($timezone);
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($now);
        $startOfMonth = $period['start']->toDateString();
        $endOfMonth = $period['end']->toDateString();

        // Target Upcoming Holidays/Events
        $upcomingHolidays = \App\Models\CompanyOffDay::where('start_date', '>=', $now->startOfDay())
            ->where(function ($q) use ($employee) {
                $q->doesntHave('teams');
                if ($employee->team_id ?? null) {
                    $q->orWhereHas('teams', function ($sq) use ($employee) {
                        $sq->where('teams.id', $employee->team_id);
                    });
                }
            })
            ->with('teams')
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get();

        // Fetch Latest Employee of the Month
        $latestEotm = \App\Models\Performance\EmployeeOfTheMonth::with('employee')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        $stats = $dashboardData['attendance_stats'] ?? get_attendance_stats($employee->id);
        
        $totalDays = $stats['total_work_days'] ?? 0;
        $presentDays = $stats['present_days'] ?? 0;
        $lateCount = $stats['late_days'] ?? 0;
        $onTimeCount = $stats['on_time_days'] ?? 0;
        $onTimePercentage = $stats['on_time_percentage'] ?? 0;
        $absentDays = $stats['absent_days'] ?? 0;
        
        // Fetch Month's Attendance Records for Stats
        $monthlyAttendances = Attendance::with(['lateArrival', 'halfDay'])
            ->where('emp_id', $employee->id)
            ->whereBetween('shift_date', [$startOfMonth, $endOfMonth])
            ->get();
            
        $halfDayCount = $monthlyAttendances->filter(fn($att) => $att->halfDay)->count();
        
        $workDaysInMonth = collect(CarbonPeriod::create($period['start'], $period['end']))
            ->filter(fn($date) => \App\Services\WorkingDayService::isWorkingDayForEmployee($date, $employee) && !$date->isFuture())
            ->count();

        // Weekly attendance data for chart (last 4 weeks)
        $weeklyAttendance = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();
            
            $weekAttendances = Attendance::where('emp_id', $employee->id)
                ->whereBetween('shift_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get();
            
            $weekPresent = $weekAttendances->filter(fn($a) => $a->check_in)->count();
            $weekLate = $weekAttendances->filter(fn($a) => $a->lateArrival && $a->lateArrival->late_minutes > 0)->count();
            $weekOnTime = max(0, $weekPresent - $weekLate);
            
            $weeklyAttendance[] = [
                'label' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M'),
                'short_label' => 'W' . (4 - $i),
                'present' => $weekPresent,
                'late' => $weekLate,
                'on_time' => $weekOnTime,
            ];
        }

        // Recent leave requests (last 5)
        $recentLeaves = Leave::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Pending leave requests count
        $pendingLeaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'Pending')
            ->count();

        // Today's breaks
        $todayBreaks = EmployeeBreak::where('emp_id', $employee->id)
            ->where('shift_date', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        // Recent late arrivals (last 5)
        $recentLateArrivals = LateArrival::where('emp_id', $employee->id)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        // Monthly attendance history (this month)
        $monthlyHistory = Attendance::with(['lateArrival'])
            ->where('emp_id', $employee->id)
            ->whereBetween('shift_date', [$startOfMonth, $endOfMonth])
            ->orderBy('shift_date', 'desc')
            ->take(10)
            ->get();

        // Average working hours this month
        $avgWorkingHours = '0h 0m';
        $checkedInDays = $monthlyAttendances->filter(fn($a) => $a->check_in && $a->check_out);
        if ($checkedInDays->count() > 0) {
            $totalMinutes = $checkedInDays->sum(function($a) {
                $checkIn = Carbon::parse($a->check_in);
                $checkOut = Carbon::parse($a->check_out);
                if ($checkOut->lt($checkIn)) $checkOut->addDay();
                return $checkIn->diffInMinutes($checkOut);
            });
            $avgMinutes = intdiv($totalMinutes, $checkedInDays->count());
            $avgWorkingHours = intdiv($avgMinutes, 60) . 'h ' . ($avgMinutes % 60) . 'm';
        }

        // Fetch Birthdays & Work Anniversaries (Next 7 Days, including Self)
        $start = now($timezone);
        $end = now($timezone)->addDays(7);
        $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
        $dobSql = $isSqlite ? "strftime('%m-%d', dob)" : "DATE_FORMAT(dob, '%m-%d')";
        $jdSql = $isSqlite ? "strftime('%m-%d', joining_date)" : "DATE_FORMAT(joining_date, '%m-%d')";

        $user = \Illuminate\Support\Facades\Auth::user();
        $teamId = $user->employee?->team_id ?? null;
        $employeeId = $user->employee?->id ?? null;

        if ($teamId) {
            $empQuery = Employee::where('team_id', $teamId);
        } elseif ($employeeId) {
            $empQuery = Employee::where('id', $employeeId);
        } else {
            $empQuery = Employee::whereRaw('1=0');
        }

        $celebrations = $empQuery
            ->whereNull('resign_date')
            ->where(function($query) use ($start, $end, $dobSql, $jdSql) {
                $startMD = $start->format('m-d');
                $endMD = $end->format('m-d');
                $query->where(function($q) use ($startMD, $endMD, $dobSql) {
                    if ($startMD <= $endMD) {
                        $q->whereRaw("$dobSql BETWEEN ? AND ?", [$startMD, $endMD]);
                    } else {
                        $q->whereRaw("$dobSql >= ?", [$startMD])
                          ->orWhereRaw("$dobSql <= ?", [$endMD]);
                    }
                })->orWhere(function($q) use ($startMD, $endMD, $jdSql) {
                    if ($startMD <= $endMD) {
                        $q->whereRaw("$jdSql BETWEEN ? AND ?", [$startMD, $endMD]);
                    } else {
                        $q->whereRaw("$jdSql >= ?", [$startMD])
                          ->orWhereRaw("$jdSql <= ?", [$endMD]);
                    }
                });
            })
            ->get();

        $processedCelebrations = collect();
        foreach ($celebrations as $emp) {
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
            if ($emp->joining_date) {
                $jd = Carbon::parse($emp->joining_date, $timezone);
                $anniv = $jd->copy()->year($start->year)->startOfDay();
                if ($anniv->format('m-d') < $start->format('m-d')) $anniv->addYear();
                $years = $anniv->year - $jd->year;
                $daysUntil = calendar_days_until($start, $anniv);
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

            $userTeamId = \Illuminate\Support\Facades\Auth::user()->employee?->team_id ?? null;
            $userEmployeeId = \Illuminate\Support\Facades\Auth::user()->employee?->id ?? null;

            if ($userTeamId) {
                $eotmQuery->whereHas('employee', function ($q) use ($userTeamId) {
                    $q->where('team_id', $userTeamId);
                });
            } elseif ($userEmployeeId) {
                $eotmQuery->where('employee_id', $userEmployeeId);
            } else {
                $employeesOfTheMonth = collect();
            }

            if (! isset($employeesOfTheMonth)) {
                $employeesOfTheMonth = $eotmQuery->get();
            }

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

        return view('employee.dashboard', [
            'dashboardData' => $dashboardData,
            'upcomingHolidays' => $upcomingHolidays,
            'employee' => $employee,
            'data' => [
                'total_days' => $totalDays,
                'on_time_count' => $onTimeCount,
                'late_count' => $lateCount,
                'half_day_count' => $halfDayCount,
                'on_time_percentage' => $onTimePercentage,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'work_days_in_month' => $workDaysInMonth,
                'filter_date' => $today,
                'is_live_mode' => $isLiveMode,
                'avg_working_hours' => $avgWorkingHours,
            ],
            'celebrations' => $sortedCelebrations,
            'weeklyAttendance' => $weeklyAttendance,
            'recentLeaves' => $recentLeaves,
            'pendingLeaves' => $pendingLeaves,
            'todayBreaks' => $todayBreaks,
            'recentLateArrivals' => $recentLateArrivals,
            'monthlyHistory' => $monthlyHistory,
        ]);
    }
}
