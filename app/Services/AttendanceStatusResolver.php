<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanyOffDay;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;

class AttendanceStatusResolver
{
    public const CHECKED_OUT = 'CHECKED_OUT';
    public const PRESENT = 'PRESENT';
    public const ON_BREAK = 'ON_BREAK';
    public const BREAK_EXCEEDED = 'BREAK_EXCEEDED';
    public const LATE = 'LATE';
    public const HALF_DAY = 'HALF_DAY';
    public const ABSENT = 'ABSENT';
    public const ON_LEAVE = 'ON_LEAVE';
    public const HOLIDAY = 'HOLIDAY';
    public const OFF = 'OFF';

    public function resolve(
        Employee $employee,
        ?Attendance $attendance,
        string $shiftDate,
        array $breakStats,
        string $timezone,
        ?Carbon $now = null,
    ): array {
        $now = $now ?? Carbon::now($timezone);

        if ($attendance && $attendance->check_in && $attendance->check_out) {
            return $this->buildCheckedOut($attendance, $timezone);
        }

        if ($attendance && $attendance->check_in) {
            return $this->buildActiveShift($attendance, $breakStats, $timezone, $now);
        }

        return $this->buildNonAttendanceDay($employee, $shiftDate, $timezone);
    }

    private function buildCheckedOut(Attendance $attendance, string $timezone): array
    {
        $checkoutTime = Carbon::parse($attendance->check_out, $timezone)->format('h:i A');
        
        $presenceType = 'punctual';
        if ($attendance->halfDay) {
            $presenceType = 'half_day';
        } elseif ($attendance->status === 'Early Leave') {
            $presenceType = 'early_leave';
        } elseif ($attendance->lateArrival && $attendance->lateArrival->late_minutes > 0) {
            $presenceType = 'late';
        }

        $label = 'Checked Out';
        if ($presenceType === 'half_day') {
            $label = 'Checked Out (Half Day)';
        } elseif ($presenceType === 'early_leave') {
            $label = 'Checked Out (Early Leave)';
        } elseif ($presenceType === 'late') {
            $label = 'Checked Out (Late)';
        }

        $pulseLine = 'Checked out for the day';
        if ($attendance->checked_out_by) {
            $adminName = $attendance->checkedOutBy?->name ?? 'Admin';
            $pulseLine = "Checked out by: {$adminName}";
        }

        return [
            'code' => self::CHECKED_OUT,
            'presence_type' => $presenceType,
            'label' => $label,
            'badge_class' => 'badge-checked-out',
            'pulse_line' => $pulseLine,
            'pulse_class' => 'text-secondary',
            'dot_class' => 'dot-offline',
            'is_online' => false,
            'show_checkout_time' => true,
            'checkout_time' => $checkoutTime,
            'alert' => null,
            'late_minutes' => ($presenceType === 'late') ? $attendance->lateArrival->late_minutes : null,
        ];
    }

    private function buildActiveShift(
        Attendance $attendance,
        array $breakStats,
        string $timezone,
        Carbon $now,
    ): array {
        $exceeded = (int) ($breakStats['exceeded'] ?? 0);
        $isOnBreak = (bool) ($breakStats['isOnBreak'] ?? false);

        if ($exceeded > 0) {
            return [
                'code' => self::BREAK_EXCEEDED,
                'presence_type' => 'break_exceeded',
                'label' => 'Break Time Exceeded',
                'badge_class' => 'badge-soft-danger',
                'show_checkout_time' => false,
                'checkout_time' => null,
                'alert' => 'break_exceeded',
                'break_exceeded_minutes' => $exceeded,
            ];
        }

        if ($isOnBreak) {
            return [
                'code' => self::ON_BREAK,
                'presence_type' => 'on_break',
                'label' => 'On Break',
                'badge_class' => 'badge-soft-warning',
                'show_checkout_time' => false,
                'checkout_time' => null,
                'alert' => null,
            ];
        }

        if ($attendance->halfDay) {
            return $this->attendanceVariant(self::HALF_DAY, 'half_day', 'Half Day', 'badge-soft-warning');
        }

        if ($attendance->lateArrival) {
            return array_merge(
                $this->attendanceVariant(self::LATE, 'late', 'Late', 'badge-soft-danger'),
                ['late_minutes' => (int) $attendance->lateArrival->late_minutes]
            );
        }

        return [
            'code' => self::PRESENT,
            'presence_type' => 'punctual',
            'label' => 'Present',
            'badge_class' => 'badge-soft-success',
            'show_checkout_time' => false,
            'checkout_time' => null,
            'alert' => null,
        ];
    }

    private function attendanceVariant(
        string $code,
        string $presenceType,
        string $label,
        string $badgeClass,
    ): array {
        return [
            'code' => $code,
            'presence_type' => $presenceType,
            'label' => $label,
            'badge_class' => $badgeClass,
            'show_checkout_time' => false,
            'checkout_time' => null,
            'alert' => null,
        ];
    }

    private function buildNonAttendanceDay(Employee $employee, string $shiftDate, string $timezone): array
    {
        $leave = Leave::where('employee_id', $employee->id)
            ->whereIn('status', ['Approved', 'Pending'])
            ->whereDate('start_date', '<=', $shiftDate)
            ->whereDate('end_date', '>=', $shiftDate)
            ->orderByRaw("FIELD(status, 'Approved', 'Pending')")
            ->first();

        if ($leave) {
            return [
                'code' => self::ON_LEAVE,
                'presence_type' => 'workforce',
                'label' => $leave->status === 'Approved' ? 'On Leave' : 'Leave Pending',
                'badge_class' => 'badge-soft-primary',
                'pulse_line' => 'Not checked in today',
                'pulse_class' => 'text-muted',
                'dot_class' => 'dot-offline',
                'is_online' => false,
                'show_checkout_time' => false,
                'checkout_time' => null,
                'alert' => null,
            ];
        }

        $teamId = $employee->team_id;
        if (CompanyOffDay::isOffDay($shiftDate, 'Holiday', $teamId)) {
            return $this->dayOff(self::HOLIDAY, 'workforce', 'Holiday', 'badge-soft-info', 'Holiday');
        }
        if (CompanyOffDay::isOffDay($shiftDate, null, $teamId)) {
            return $this->dayOff(self::OFF, 'workforce', 'OFF', 'badge-soft-secondary', 'Day off');
        }

        return [
            'code' => self::ABSENT,
            'presence_type' => 'absent',
            'label' => 'Absent',
            'badge_class' => 'badge-soft-danger',
            'pulse_line' => 'Not checked in today',
            'pulse_class' => 'text-muted',
            'dot_class' => 'dot-offline',
            'is_online' => false,
            'show_checkout_time' => false,
            'checkout_time' => null,
            'alert' => null,
        ];
    }

    private function dayOff(string $code, string $presenceType, string $label, string $badgeClass, string $pulseLine): array
    {
        return [
            'code' => $code,
            'presence_type' => $presenceType,
            'label' => $label,
            'badge_class' => $badgeClass,
            'pulse_line' => $pulseLine,
            'pulse_class' => 'text-muted',
            'dot_class' => 'dot-offline',
            'is_online' => false,
            'show_checkout_time' => false,
            'checkout_time' => null,
            'alert' => null,
        ];
    }

    public static function isTestEmployee(Employee $employee): bool
    {
        $email = strtolower((string) $employee->email);

        return str_starts_with($employee->name ?? '', 'Checkout Test')
            || str_ends_with($email, '@test.local')
            || str_ends_with($email, '@test.com');
    }
}
