<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonTimeZone;
class AttendanceController extends BaseController
{

    public function getTodayStatus(Request $request)
    {
        $empId = $request->query('emp_id');
        $tz = get_employee_settings($empId, 'time_zone') ?? 'Asia/Karachi';

        // Get today's date in Asia/Karachi timezone
        $today = Carbon::today($tz);

        // Get assigned shift for employee today
        $assignedShift = $this->getTodayAssignedShift($empId);

        if (!$assignedShift) {
            return response()->json(['status' => 'no_shift_assigned']);
        }

        // Assuming $assignedShift has 'start_time' and 'end_time' as HH:mm:ss strings
        // Build shift start and end datetime in Asia/Karachi timezone for today
        $shiftStart = Carbon::parse($assignedShift->start_time, $tz)->setDate($today->year, $today->month, $today->day);
        $shiftEnd = Carbon::parse($assignedShift->end_time, $tz)->setDate($today->year, $today->month, $today->day);

        // If end time is before start time, assume overnight shift crossing midnight
        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        // Query attendance for emp_id where check_in is between shiftStart and shiftEnd
        $attendance = Attendance::where('emp_id', $empId)
            ->whereBetween('check_in', [$shiftStart, $shiftEnd])
            ->first();

        return response()->json([
            'status' => $attendance ? 'Checked In' : 'Not Checked In',
            'assignedShift' => $assignedShift,
            'shiftStart' => $shiftStart->toDateTimeString(),
            'shiftEnd' => $shiftEnd->toDateTimeString(),
        ]);
    }

    public function todayAttendanceStatus(Request $request)
    {
        $employeeId = $request->query('emp_id');

        if (!$employeeId) {
            return response()->json(['status' => 'Invalid Request'], 400);
        }

        $tz = get_employee_settings($employeeId, 'time_zone') ?? get_app_settings('app_timezone') ?? 'Asia/Karachi';

        // $today = Carbon::today($tz);

        $assignedShift = $this->getTodayAssignedShift($employeeId);
        $today = Carbon::parse($assignedShift->shift_date, $tz)->toDateString();

        // echo $today;
        // die;
        if (!$assignedShift) {
            return response()->json(['status' => 'no_shift_assigned']);
        }

        $attendance = Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $today)
            ->with(['shift', 'lateArrival', 'halfDay', 'breaks'])
            ->first();

        if ($attendance && $attendance->check_in) {
            $employee = Employee::find($employeeId) ?? $request->user()?->employee;
            if (! $employee) {
                return response()->json(['status' => 'Employee not found'], 404);
            }

            $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)->latest()->first();
            $breakStats = calculate_break_stats($employee, $employeeShift, $today, $attendance->id);
            
            $workDuration = $breakStats['working_hours']; // HH:MM:SS
            // Convert HH:MM:SS to Xh Ym
            $parts = explode(':', $workDuration);
            $h = (int)$parts[0];
            $m = (int)$parts[1];
            
            $formattedDuration = "";
            if ($h > 0) $formattedDuration .= $h . "h ";
            $formattedDuration .= $m . "m";
            if ($formattedDuration === "") $formattedDuration = "0m";

            // Determine specific status label
            $statusLabel = resolve_attendance_status_label($attendance, $employee);
            $holidayTitle = resolve_attendance_holiday_title($attendance, $employee, $statusLabel);

            return response()->json([
                'status' => $attendance->check_out ? 'Checked Out' : 'Checked In',
                'attendance' => $attendance->toArray(),
                'breaks' => $attendance->breaks
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn ($break) => [
                        'id' => $break->id,
                        'type' => $break->type,
                        'status' => $break->status,
                        'reason' => $break->reason,
                        'start_time' => $break->start_time,
                        'end_time' => $break->end_time,
                        'spent_minutes' => $break->spent_minutes ?? $break->duration,
                    ]),
                'work_duration' => $formattedDuration,
                'break_duration' => $breakStats['total_spent'],
                'attendance_status' => $statusLabel,
                'holiday_title' => $holidayTitle,
            ]);
        } else {
            return response()->json(['status' => 'Not Checked In']);
        }

    }

    public function thisMonthAttendanceStatus(Request $request)
    {
        $employeeId = $request->query('emp_id');

        if (!$employeeId) {
            return response()->json(['status' => 'Invalid Request'], 400);
        }

        $tz = get_employee_settings($employeeId, 'time_zone') ?? get_app_settings('app_timezone') ?? 'Asia/Karachi';

        $from = $request->query('from');
        $to = $request->query('to');

        if ($from && $to) {
            $startDate = Carbon::parse($from, $tz)->startOfDay();
            $endDate = Carbon::parse($to, $tz)->endOfDay();
        } else {
            $period = \App\Services\PayrollPeriodService::getPeriodForDate(Carbon::now($tz));
            $startDate = $period['start']->copy();
            $endDate = $period['end']->copy();
        }

        // Get paginated attendance records
        $perPage = $request->query('per_page', 15);
        $attendances = Attendance::where('emp_id', $employeeId)
            ->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with(['shift', 'breaks', 'lateArrival', 'halfDay'])
            ->orderBy('shift_date', 'desc')
            ->paginate($perPage);

        $employee = Employee::find($employeeId);
        $authEmployee = $request->user()?->employee;

        if (! $employee) {
            return response()->json(['status' => 'Employee not found'], 404);
        }

        if ($authEmployee && (int) $authEmployee->id !== (int) $employeeId) {
            return response()->json(['status' => 'Unauthorized'], 403);
        }

        $employeeShift = \App\Models\EmployeeShift::where('emp_id', $employeeId)->latest()->first();

        $records = [];

        foreach ($attendances as $attendance) {
            $shiftDate = $attendance->shift_date instanceof \Carbon\Carbon
                ? $attendance->shift_date->toDateString()
                : (string) $attendance->shift_date;

            try {
                $breakStats = calculate_break_stats($employee, $employeeShift, $shiftDate, $attendance->id);
            } catch (\Throwable $e) {
                $breakStats = [
                    'working_hours' => '00:00:00',
                    'total_spent' => '0 minutes',
                ];
            }
            
            $workDuration = $breakStats['working_hours']; // HH:MM:SS
            // Convert HH:MM:SS to Xh Ym
            $parts = explode(':', $workDuration);
            $h = (int)$parts[0];
            $m = (int)$parts[1];
            
            $formattedLogDuration = "";
            if ($h > 0) $formattedLogDuration .= $h . "h ";
            $formattedLogDuration .= $m . "m";
            if ($formattedLogDuration === "") $formattedLogDuration = "0m";

            // Determine specific status label
            $statusLabel = resolve_attendance_status_label($attendance, $employee);
            $holidayTitle = resolve_attendance_holiday_title($attendance, $employee, $statusLabel);

            $records[] = [
                'status' => $attendance->check_out ? 'Checked Out' : 'Checked In',
                'attendance' => $attendance,
                'work_duration' => $formattedLogDuration,
                'break_duration' => $breakStats['total_spent'],
                'shift_name' => $attendance->shift->shift_name ?? 'N/A',
                'attendance_status' => $statusLabel,
                'holiday_title' => $holidayTitle,
            ];
        }

        $periodHolidays = \App\Models\CompanyOffDay::holidaysForEmployeeInRange(
            $employee,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        return response()->json([
            'status' => 'success',
            'records' => $records,
            'period_holidays' => $periodHolidays,
            'pagination' => [
                'total' => $attendances->total(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'from' => $attendances->firstItem(),
                'to' => $attendances->lastItem()
            ]
        ]);
    }

    public function getAttendanceDetails(Request $request, $id)
    {
        $employeeId = $request->user()->employee->id;
        $attendance = Attendance::where('emp_id', $employeeId)
            ->with(['shift', 'breaks', 'lateArrival', 'halfDay'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $attendance
        ]);
    }
}