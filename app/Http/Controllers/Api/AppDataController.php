<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use App\Models\EmployeeShift;
use App\Models\Leave;
use Illuminate\Http\Request;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AppDataController extends BaseController
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function refresh(Request $request)
    {
        $employeeId = (int) $request->input('employee_id');
        $force = $request->boolean('force');

        if (!$employeeId) {
            return response()->json(['error' => 'employee_id is required'], 400);
        }

        $cacheKey = "app_data_refresh:v4:{$employeeId}";

        if (!$force && Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        $data = $this->employeeService->getAppData($employeeId);

        if (!$data) {
            return response()->json(['error' => 'Employee data not found or no assigned shift'], 404);
        }

        Cache::put($cacheKey, $data, 45);

        return response()->json($data);
    }

    public function upcomingHolidays(Request $request)
    {
        $employee = $request->user()?->employee;

        if (! $employee) {
            return $this->sendError('Employee record not found.', [], 404);
        }

        $holidays = \App\Models\CompanyOffDay::upcomingForEmployee($employee);

        return $this->sendResponse(
            $holidays,
            'Upcoming holidays fetched.'
        );
    }

    public function employeeProfile(Request $request)
    {
        $authEmployee = $request->user()?->employee;

        if (! $authEmployee) {
            return $this->sendError('Employee record not found.', [], 404);
        }

        $employee = Employee::with(['team.department', 'branch', 'user'])->find($authEmployee->id);

        if (! $employee) {
            return $this->sendError('Employee record not found.', [], 404);
        }

        return $this->sendResponse(
            $this->employeeService->buildEmployeeProfile($employee),
            'Employee profile fetched.'
        );
    }

    public function dashboardAttendance(Request $request)
    {
        $employee = $request->user()?->employee;

        if (! $employee) {
            return $this->sendError('Employee record not found.', [], 404);
        }

        $tz = get_employee_settings($employee->id, 'time_zone') ?? get_app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = Carbon::now($tz);
        $from = $now->copy()->subDays(35)->toDateString();
        $to = $now->toDateString();

        $attendances = Attendance::query()
            ->where('emp_id', $employee->id)
            ->whereBetween('shift_date', [$from, $to])
            ->with(['shift', 'lateArrival', 'halfDay'])
            ->orderByDesc('shift_date')
            ->get();

        $recent = $attendances->take(7)->map(function ($attendance) use ($employee) {
            $statusLabel = $this->resolveAttendanceStatusLabel($attendance, $employee);
            $holidayTitle = resolve_attendance_holiday_title($attendance, $employee, $statusLabel);

            return [
                'attendance_status' => $statusLabel,
                'holiday_title' => $holidayTitle,
                'shift_name' => $attendance->shift->shift_name ?? 'N/A',
                'attendance' => [
                    'id' => $attendance->id,
                    'shift_date' => $attendance->shift_date?->toDateString() ?? $attendance->shift_date,
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'status' => $attendance->status,
                ],
            ];
        })->values();

        // Precompute the employee's holiday dates once for the whole window
        // (team + branch scoped) — avoids a DB query per absent row and keeps
        // the exclusion identical everywhere.
        $holidayDateSet = [];
        foreach (\App\Models\CompanyOffDay::holidaysForEmployeeInRange($employee, $from, $to) as $holiday) {
            $range = \Carbon\CarbonPeriod::create(
                max(Carbon::parse($holiday['start_date']), Carbon::parse($from)),
                min(Carbon::parse($holiday['end_date']), Carbon::parse($to))
            );
            foreach ($range as $day) {
                $holidayDateSet[$day->toDateString()] = true;
            }
        }

        $weeklyTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd = $now->copy()->subWeeks($i)->endOfWeek(Carbon::MONDAY);

            $weekItems = $attendances->filter(function ($attendance) use ($weekStart, $weekEnd, $tz) {
                $date = Carbon::parse($attendance->shift_date)->timezone($tz);

                return $date->between($weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay());
            });

            $onTime = 0;
            $late = 0;
            $absent = 0;

            foreach ($weekItems as $attendance) {
                $shiftDateStr = Carbon::parse($attendance->shift_date)->timezone($tz)->toDateString();

                // Holiday / company off — never count in weekly absent/on-time/late
                if (strcasecmp((string) $attendance->status, 'Holiday') === 0) {
                    continue;
                }
                if (isset($holidayDateSet[$shiftDateStr])) {
                    continue;
                }
                if (\App\Models\CompanyOffDay::getHolidayForEmployee($attendance->shift_date, $employee)) {
                    continue;
                }

                if ($attendance->status === 'Absent' || ! $attendance->check_in) {
                    $absent++;
                } elseif ($attendance->lateArrival) {
                    $late++;
                } else {
                    $onTime++;
                }
            }

            $weeklyTrend[] = [
                'label' => $i === 0 ? 'This week' : 'W-'.$i,
                'on_time' => $onTime,
                'late' => $late,
                'absent' => $absent,
            ];
        }

        $shiftDate = $this->resolveCurrentShiftDate($employee->id, $tz);
        $todayAttendance = Attendance::query()
            ->where('emp_id', $employee->id)
            ->whereDate('shift_date', $shiftDate)
            ->with(['lateArrival', 'halfDay'])
            ->first();

        $todayBreaks = EmployeeBreak::filterForDisplay(
            EmployeeBreak::query()
                ->where('emp_id', $employee->id)
                ->where(function ($query) use ($todayAttendance, $shiftDate) {
                    if ($todayAttendance) {
                        $query->where('attendance_id', $todayAttendance->id)
                            ->orWhereDate('shift_date', $shiftDate);
                    } else {
                        $query->whereDate('shift_date', $shiftDate);
                    }
                })
                ->orderByDesc('created_at')
                ->get()
        )
            ->map(fn (EmployeeBreak $break) => [
                'id' => $break->id,
                'type' => $break->type,
                'status' => $break->status,
                'reason' => $break->reason,
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
                'spent_minutes' => $break->spent_minutes ?? $break->duration,
            ])
            ->values();

        $employeeShift = EmployeeShift::where('emp_id', $employee->id)->latest()->first();
        $breakStats = calculate_break_stats($employee, $employeeShift, $shiftDate, $todayAttendance?->id);
        $pendingLeaves = Leave::where('employee_id', $employee->id)->where('status', 'Pending')->count();
        $upcomingHolidays = \App\Models\CompanyOffDay::upcomingForEmployee($employee);
        $periodHolidays = \App\Models\CompanyOffDay::holidaysForEmployeeInRange($employee, $from, $to);

        return $this->sendResponse([
            'recent' => $recent,
            'weekly_trend' => $weeklyTrend,
            'today_breaks' => $todayBreaks,
            'upcoming_holidays' => $upcomingHolidays,
            'period_holidays' => $periodHolidays,
            'notifications' => $this->buildDashboardNotifications(
                $employee,
                $todayAttendance,
                $breakStats,
                $pendingLeaves,
                $shiftDate,
                $upcomingHolidays
            ),
        ], 'Dashboard attendance fetched.');
    }

    private function resolveCurrentShiftDate(int $employeeId, string $tz): string
    {
        $employeeShift = EmployeeShift::where('emp_id', $employeeId)->latest()->first();
        $shiftDate = $employeeShift ? resolve_shift_date($employeeShift) : null;

        return $shiftDate ?: Carbon::now($tz)->toDateString();
    }

    private function buildDashboardNotifications(
        $employee,
        ?Attendance $todayAttendance,
        array $breakStats,
        int $pendingLeaves,
        string $shiftDate,
        $upcomingHolidays = null
    ): array {
        $notifications = [];

        if (($breakStats['exceeded_minutes'] ?? 0) > 0) {
            $notifications[] = [
                'tone' => 'rose',
                'title' => 'Break limit exceeded',
                'text' => 'You have exceeded your break allowance by '.($breakStats['exceeded_minutes'] ?? 0).' min.',
            ];
        } elseif (($breakStats['on_break'] ?? false) === true) {
            $notifications[] = [
                'tone' => 'amber',
                'title' => 'Break in progress',
                'text' => 'Your live timer is paused while you are on break.',
            ];
        }

        if ($todayAttendance && $todayAttendance->check_in) {
            $statusLabel = $this->resolveAttendanceStatusLabel($todayAttendance, $employee);
            $notifications[] = [
                'tone' => str_contains(strtolower($statusLabel), 'late') ? 'amber' : 'blue',
                'title' => 'Today\'s attendance',
                'text' => 'You are marked as '.$statusLabel.' for '.$shiftDate.'.',
            ];
        }

        $allowed = (int) ($breakStats['allowed_break_minutes'] ?? 0);
        $spent = (int) ($breakStats['total_spent_minutes'] ?? 0);
        if ($allowed > 0 && $spent > 0 && ($breakStats['exceeded_minutes'] ?? 0) <= 0) {
            $remaining = max($allowed - $spent, 0);
            if ($remaining <= 15) {
                $notifications[] = [
                    'tone' => 'amber',
                    'title' => 'Break allowance running low',
                    'text' => 'Only '.$remaining.' minute'.($remaining === 1 ? '' : 's').' of break time left today.',
                ];
            }
        }

        if ($pendingLeaves > 0) {
            $notifications[] = [
                'tone' => 'blue',
                'title' => 'Pending leave requests',
                'text' => $pendingLeaves.' request'.($pendingLeaves > 1 ? 's' : '').' waiting for approval.',
            ];
        }

        $upcomingHoliday = collect($upcomingHolidays ?? [])
            ->first(function ($holiday) use ($shiftDate) {
                $start = Carbon::parse($holiday['start_date'] ?? $holiday['startDate'] ?? null);
                $end = Carbon::parse($holiday['end_date'] ?? $holiday['endDate'] ?? $holiday['start_date'] ?? $holiday['startDate'] ?? null);

                return $start->lte(Carbon::parse($shiftDate)->addDays(3))
                    && $end->gte(Carbon::parse($shiftDate));
            });

        if (! $upcomingHoliday) {
            $upcomingHoliday = \App\Models\CompanyOffDay::query()
                ->upcoming($shiftDate)
                ->forTeam($employee->team_id)
                ->forBranch($employee->branch_id)
                ->whereDate('start_date', '<=', Carbon::parse($shiftDate)->addDays(3)->toDateString())
                ->orderBy('start_date')
                ->first();
        }

        if ($upcomingHoliday) {
            $title = is_array($upcomingHoliday)
                ? ($upcomingHoliday['title'] ?? 'Holiday')
                : ($upcomingHoliday->note ?: ($upcomingHoliday->title ?: 'Holiday'));
            $startDate = is_array($upcomingHoliday)
                ? ($upcomingHoliday['start_date'] ?? $upcomingHoliday['startDate'] ?? null)
                : ($upcomingHoliday->start_date?->toDateString() ?? $upcomingHoliday->start_date);

            $notifications[] = [
                'tone' => 'blue',
                'title' => 'Upcoming holiday',
                'text' => $title.' on '.Carbon::parse($startDate)->format('M d, Y').'.',
            ];
        }

        if (empty($notifications)) {
            $notifications[] = [
                'tone' => 'blue',
                'title' => 'All clear',
                'text' => 'No alerts right now. Keep up the good work.',
            ];
        }

        return $notifications;
    }

    private function resolveAttendanceStatusLabel(Attendance $attendance, ?Employee $employee = null): string
    {
        return resolve_attendance_status_label($attendance, $employee);
    }
}
