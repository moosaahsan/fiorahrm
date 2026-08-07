<?php

namespace App\Services;

use App\Events\AttendanceUpdated;
use App\Events\BreakUpdated;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminCheckoutService
{
    public function checkout(int $employeeId): array
    {
        $employee = Employee::accessible()
            ->whereNull('resign_date')
            ->findOrFail($employeeId);

        $attendance = Attendance::with('shift')->where('emp_id', $employee->id)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->orderByDesc('id')
            ->first();

        if (!$attendance) {
            return [
                'success' => false,
                'message' => 'Employee is not checked in or is already checked out.',
            ];
        }

        $timezone = (function_exists('get_employee_settings')
            ? get_employee_settings($employee->id, 'time_zone')
            : null)
            ?? app_settings('app_timezone')
            ?? 'Asia/Karachi';
        $now = Carbon::now($timezone);

        $attendance->check_out = $now;
        $attendance->shift_over_at = $now;
        $attendance->checked_out_by = auth()->id();

        // Evaluate Early Checkout
        if ($attendance->shift) {
            $shiftDate = Carbon::parse($attendance->shift_date, $timezone);
            $shiftEnd = $shiftDate->copy()->setTimeFromTimeString($attendance->shift->end_time);
            $shiftStart = $shiftDate->copy()->setTimeFromTimeString($attendance->shift->start_time);
            
            if ($shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }

            if ($now->lt($shiftEnd)) {
                $earlyMinutes = abs((int) $shiftEnd->diffInMinutes($now));
                if ($earlyMinutes > 60) {
                    $attendance->status = 'Half Day';
                    \App\Models\HalfDay::firstOrCreate([
                        'attendance_id' => $attendance->id,
                    ], [
                        'emp_id' => $employee->id,
                        'shift_id' => $attendance->shift_id,
                        'date' => $attendance->shift_date,
                        'reason' => 'Early Checkout (>1 hour)',
                    ]);
                } elseif ($earlyMinutes > 0) {
                    $attendance->status = 'Early Leave';
                }
            } else {
                if (in_array($attendance->status, ['Present', 'Half Day'], true)) {
                    $attendance->status = 'Present';
                }
            }
        } else {
            if (in_array($attendance->status, ['Present', 'Half Day'], true)) {
                $attendance->status = 'Present';
            }
        }

        $attendance->save();

        Cache::forget("app_data_refresh:v2:{$employee->id}");

        $endedBreak = $this->endActiveBreaks($employee->id, $attendance, $now, $timezone);

        $this->runSafely(function () use ($employee) {
            if (function_exists('clear_employee_app_data_cache')) {
                clear_employee_app_data_cache($employee->id);
            }
        }, 'clear app data cache', $employee->id);

        $this->runSafely(function () use ($employee) {
            if (function_exists('standardize_attendance_data_for_pusher')) {
                event(new AttendanceUpdated(
                    $employee->id,
                    standardize_attendance_data_for_pusher($employee->id)
                ));
            }
        }, 'attendance broadcast', $employee->id);

        $message = "{$employee->name} checked out successfully.";
        if ($endedBreak) {
            $message .= ' Active break ended.';
        }

        return [
            'success' => true,
            'message' => $message,
            'check_out' => $now->format('h:i A'),
            'attendance_id' => $attendance->id,
            'break_ended' => $endedBreak,
        ];
    }

    private function endActiveBreaks(int $employeeId, Attendance $attendance, Carbon $endAt, string $timezone): bool
    {
        $activeBreaks = EmployeeBreak::where('emp_id', $employeeId)
            ->whereIn('status', ['On Break', 'On break', 'Ongoing'])
            ->where(function ($query) {
                $query->whereNull('end_time')
                    ->orWhere('end_time', '');
            })
            ->orderByDesc('start_time')
            ->get();

        if ($activeBreaks->isEmpty()) {
            return false;
        }

        $endedAny = false;

        foreach ($activeBreaks as $break) {
            if ($this->endSingleBreak($break, $endAt, $timezone)) {
                $endedAny = true;
            }
        }

        if ($endedAny) {
            $this->runSafely(function () use ($employeeId, $attendance, $endAt) {
                $shiftDate = $attendance->shift_date instanceof Carbon
                    ? $attendance->shift_date->toDateString()
                    : Carbon::parse($attendance->shift_date ?? $endAt)->toDateString();

                if (function_exists('standardize_break_data_for_pusher')) {
                    event(new BreakUpdated(
                        $employeeId,
                        standardize_break_data_for_pusher($employeeId, $shiftDate, null, $attendance->id)
                    ));
                }
            }, 'break broadcast', $employeeId);
        }

        return $endedAny;
    }

    private function endSingleBreak(EmployeeBreak $break, Carbon $endAt, string $timezone): bool
    {
        try {
            $break->endBreak(['end_time' => $endAt->copy()]);
            Log::info("Admin checkout ended break {$break->id} for Emp {$break->emp_id}");

            return true;
        } catch (\Throwable $e) {
            Log::warning("Admin checkout endBreak failed for break {$break->id}: {$e->getMessage()}");
        }

        try {
            $spentMinutes = 1;
            if ($break->start_time) {
                $start = Carbon::parse($break->start_time, $timezone);
                $end = $endAt->copy();
                if ($end->lt($start)) {
                    $end->addDay();
                }
                $spentMinutes = max(1, (int) $start->diffInMinutes($end));
            }

            DB::table('employee_breaks')
                ->where('id', $break->id)
                ->update([
                    'end_time' => $endAt->format('Y-m-d H:i:s'),
                    'status' => 'Completed',
                    'spent_minutes' => $spentMinutes,
                    'updated_at' => now(),
                ]);

            Log::info("Admin checkout force-ended break {$break->id} via fallback for Emp {$break->emp_id}");

            return true;
        } catch (\Throwable $e) {
            Log::error("Admin checkout force-end failed for break {$break->id}: {$e->getMessage()}");

            return false;
        }
    }

    private function runSafely(callable $callback, string $context, int $employeeId): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning("Admin checkout {$context} failed for Emp {$employeeId}: {$e->getMessage()}");
        }
    }
}
