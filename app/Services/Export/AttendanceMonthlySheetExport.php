<?php

namespace App\Services\Export;

use App\Models\CompanyOffDay;
use App\Models\Employee;
use App\Services\WorkingDayService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * The bulk-download counterpart to the on-screen monthly attendance sheet
 * (admin/attendance-sheet).
 *
 * Built independently from MonthlyAttendanceController@monthlyData rather than
 * sharing its column callbacks — that method renders HTML badges for
 * DataTables, this needs plain text for a spreadsheet, and the sheet is a
 * heavily-relied-on screen that should not be touched to make room for the
 * export. The day-status rules below are intentionally kept in the same order
 * the on-screen version uses, so a cell here reads the same way it does there.
 */
class AttendanceMonthlySheetExport
{
    protected array $offDaysByYear = [];

    public function __construct(
        protected Carbon $start,
        protected Carbon $end,
        protected ?int $employeeId = null,
        protected ?int $shiftId = null,
    ) {
    }

    public function writeTo(string $path): string
    {
        $writer = new XlsxWriter();
        $writer->addSheet('Attendance', $this->rows(), $this->columnWidths());
        $writer->save($path);

        return $path;
    }

    public function filename(): string
    {
        return 'attendance-' . $this->start->format('Y-m-d') . '-to-' . $this->end->format('Y-m-d') . '.xlsx';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function rows(): array
    {
        $employees = $this->employees();
        $dates = CarbonPeriod::create($this->start->copy()->startOfDay(), $this->end->copy()->startOfDay());

        $header = ['Sr#', 'Employee Name', 'Position'];

        foreach ($dates as $date) {
            $header[] = $date->format('d M (D)');
        }

        $header = array_merge($header, ['Paid Leaves', 'Unpaid Leaves', 'Allocated', 'Remaining', 'Assigned Check-in']);

        $rows = [$header];

        foreach ($employees as $index => $employee) {
            $attendanceByDate = $employee->attendances->keyBy(
                fn ($attendance) => Carbon::parse($attendance->shift_date)->format('Y-m-d')
            );

            $leaveByDate = collect();
            foreach ($employee->leaves as $leave) {
                $cursor = Carbon::parse($leave->start_date)->startOfDay();
                $leaveEnd = Carbon::parse($leave->end_date)->endOfDay();

                while ($cursor->lte($leaveEnd)) {
                    $leaveByDate->put($cursor->format('Y-m-d'), $leave->status);
                    $cursor->addDay();
                }
            }

            $row = [$index + 1, $employee->name, $employee->position ?? '-'];

            foreach (CarbonPeriod::create($this->start->copy()->startOfDay(), $this->end->copy()->startOfDay()) as $date) {
                $row[] = $this->dayStatus($employee, $date, $attendanceByDate, $leaveByDate);
            }

            $balances = $employee->leaveBalances;
            $row[] = (float) $balances->where('leaveType.is_paid', true)->sum('used');
            $row[] = (float) $balances->where('leaveType.is_paid', false)->sum('used');
            $row[] = (float) $balances->where('leaveType.is_paid', true)->sum('allocated');
            $row[] = (float) $balances->where('leaveType.is_paid', true)->sum('remaining');
            $row[] = optional($employee->currentShift?->shift)->start_time
                ? Carbon::parse($employee->currentShift->shift->start_time)->format('h:i A')
                : 'Unassigned';

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Mirrors the status rules in MonthlyAttendanceController@monthlyData, in
     * the same precedence order, but returns plain text instead of a badge.
     */
    protected function dayStatus(Employee $employee, Carbon $date, $attendanceByDate, $leaveByDate): string
    {
        $key = $date->format('Y-m-d');
        $attendance = $attendanceByDate->get($key);

        if ($attendance && $attendance->check_in) {
            if ($attendance->halfDay) {
                return 'Half Day';
            }

            if ($attendance->status === 'Early Leave') {
                return 'Early Leave';
            }

            if ($attendance->lateArrival) {
                $minutes = (int) ($attendance->lateArrival->late_minutes ?? 0);

                return $minutes > 0 ? 'Late (' . format_minutes_to_hm($minutes) . ')' : 'Late';
            }

            return 'Present';
        }

        $leaveStatus = $leaveByDate->get($key);

        if ($leaveStatus) {
            return $leaveStatus === 'Approved' ? 'Approved Leave' : 'Pending Leave';
        }

        $offDay = $this->offDaysFor((int) $date->year)->first(function ($offDay) use ($date, $employee) {
            if (! $date->between($offDay->start_date, $offDay->end_date)) {
                return false;
            }

            return $offDay->teams->isEmpty() || $offDay->teams->contains('id', $employee->team_id);
        });

        if ($offDay) {
            return $offDay->type === 'Holiday' ? 'Holiday' : 'Off';
        }

        if (WorkingDayService::isWeeklyOffDay($date)) {
            return 'Off';
        }

        if ($employee->joining_date && $date->lt(Carbon::parse($employee->joining_date)->startOfDay())) {
            return '';
        }

        if ($date->isFuture()) {
            return 'Upcoming';
        }

        if ($employee->resign_date && $date->gt(Carbon::parse($employee->resign_date))) {
            return '';
        }

        return 'Absent';
    }

    /**
     * @return \Illuminate\Support\Collection<int, CompanyOffDay>
     */
    protected function offDaysFor(int $year)
    {
        if (isset($this->offDaysByYear[$year])) {
            return $this->offDaysByYear[$year];
        }

        return $this->offDaysByYear[$year] = CompanyOffDay::with('teams')
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start, $this->end])
                    ->orWhereBetween('end_date', [$this->start, $this->end]);
            })
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    protected function employees()
    {
        $query = Employee::accessible()
            ->orderBy('name')
            ->select('employees.id', 'employees.name', 'employees.position', 'employees.team_id', 'employees.resign_date', 'employees.joining_date')
            ->with([
                'leaves' => function ($query) {
                    $query->select('id', 'employee_id', 'start_date', 'end_date', 'status')
                        ->whereIn('status', ['Approved', 'Pending'])
                        ->where(function ($q) {
                            $q->whereBetween('start_date', [$this->start, $this->end])
                                ->orWhereBetween('end_date', [$this->start, $this->end])
                                ->orWhere(function ($q2) {
                                    $q2->where('start_date', '<=', $this->end)
                                        ->where('end_date', '>=', $this->start);
                                });
                        });
                },
                'attendances' => function ($query) {
                    $query->select('id', 'emp_id', 'shift_id', 'shift_date', 'check_in', 'check_out', 'status')
                        ->with(['lateArrival', 'halfDay'])
                        ->whereBetween('shift_date', [$this->start->toDateString(), $this->end->toDateString()]);
                },
                'leaveBalances' => function ($query) {
                    $query->where('year', now()->year)->with('leaveType');
                },
                'currentShift.shift',
            ]);

        if ($this->employeeId) {
            $query->where('employees.id', $this->employeeId);
        }

        if ($this->shiftId) {
            $query->whereHas('attendances', function ($query) {
                $query->where('shift_id', $this->shiftId);
            });
        }

        return $query->get();
    }

    /**
     * @return array<int, float>
     */
    protected function columnWidths(): array
    {
        $days = $this->start->diffInDays($this->end) + 1;

        $widths = [0 => 6, 1 => 24, 2 => 18];

        for ($i = 0; $i < $days; $i++) {
            $widths[3 + $i] = 13;
        }

        $summaryStart = 3 + $days;
        $widths[$summaryStart] = 11;
        $widths[$summaryStart + 1] = 12;
        $widths[$summaryStart + 2] = 11;
        $widths[$summaryStart + 3] = 11;
        $widths[$summaryStart + 4] = 15;

        return $widths;
    }
}
