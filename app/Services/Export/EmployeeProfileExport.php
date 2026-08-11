<?php

namespace App\Services\Export;

use App\Models\CompensatoryLeave;
use App\Models\Employee;
use App\Models\LeaveCashout;
use Carbon\Carbon;

/**
 * Assembles one employee's full record into a multi-sheet workbook.
 *
 * Salary and bank details only appear when the person asking is allowed to see
 * them on the profile page — the export must not become a way around that.
 */
class EmployeeProfileExport
{
    public function __construct(
        protected Employee $employee,
        protected bool $includeSensitive = false,
    ) {
    }

    /**
     * Build the workbook and return the path it was written to.
     */
    public function writeTo(string $path): string
    {
        $writer = new XlsxWriter();

        $writer->addSheet('Profile', $this->profileRows(), [0 => 32, 1 => 46]);
        $writer->addSheet('Leave Balances', $this->leaveBalanceRows(), [0 => 10, 1 => 24, 2 => 14, 3 => 12, 4 => 14, 5 => 12]);
        $writer->addSheet('Career History', $this->careerRows(), [0 => 14, 1 => 20, 2 => 34, 3 => 40, 4 => 20]);
        $writer->addSheet('Leave Records', $this->leaveRows(), [0 => 14, 1 => 14, 2 => 22, 3 => 12, 4 => 8, 5 => 14, 6 => 40]);
        $writer->addSheet('Compensatory Leave', $this->compensatoryRows(), [0 => 14, 1 => 28, 2 => 12, 3 => 14, 4 => 14, 5 => 20, 6 => 34]);
        $writer->addSheet('Leave Encashment', $this->cashoutRows(), [0 => 12, 1 => 22, 2 => 10, 3 => 16, 4 => 16, 5 => 12, 6 => 34]);
        $writer->addSheet('Attendance Summary', $this->attendanceRows(), [0 => 16, 1 => 12, 2 => 12, 3 => 12, 4 => 12]);

        if ($this->employee->exitRecords->isNotEmpty()) {
            $writer->addSheet('Exit Records', $this->exitRows(), [0 => 14, 1 => 16, 2 => 14, 3 => 46, 4 => 20]);
        }

        $writer->save($path);

        return $path;
    }

    public function filename(): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', trim($this->employee->name ?: 'employee'));
        $name = trim($name, '-') ?: 'employee';

        return strtolower($name) . '-profile-' . now()->format('Y-m-d') . '.xlsx';
    }

    // ──────────────────────────────────────────────
    // Sheets
    // ──────────────────────────────────────────────

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function profileRows(): array
    {
        $employee = $this->employee;

        $rows = [
            ['Field', 'Value'],

            ['— Personal —', ''],
            ['Employee ID', $employee->id],
            ['Full Name', $employee->name],
            ['CNIC', $employee->cnic],
            ['Gender', $employee->gender ? ucfirst($employee->gender) : null],
            ['Date of Birth', $this->date($employee->dob)],
            ['Contact Number', $employee->contact_no],
            ['Emergency Contact', $employee->emergency_no],
            ['Email', $employee->email],

            ['— Employment —', ''],
            ['Designation', $employee->position],
            ['Department / Team', $employee->team->name ?? 'Global Pool'],
            ['Branch', $employee->branch->name ?? null],
            ['Shift', optional($employee->currentShiftAssignment)->shift->shift_name ?? 'Floating'],
            ['Role', $employee->user->roles->first()->name ?? null],
            ['Joining Date', $this->date($employee->joining_date)],
            ['Service Tenure', $this->tenure()],
            ['Probation (months)', $employee->probation],
            ['Probation Ends', $this->date($employee->probationEndsOn())],
            ['Confirmed On', $this->date($employee->confirmed_at)],
            ['Employment Status', $this->employmentStatus()],
            ['Active', $employee->status ? 'Yes' : 'No'],

            ['— Career —', ''],
            ['Last Increment', $this->lastIncrementSummary()],
            ['Last Promotion', $this->lastPromotionSummary()],
        ];

        if ($this->includeSensitive) {
            $rows[] = ['— Compensation —', ''];
            $rows[] = ['Current Salary', (float) $employee->salary];
            $rows[] = ['Salary Structure', $employee->salaryStructure->name ?? null];

            $rows[] = ['— Bank Details —', ''];
            $rows[] = ['Bank Name', $employee->bank_name];
            $rows[] = ['Account Title', $employee->bank_account_name];
            $rows[] = ['Account Number', $employee->account_number];
            $rows[] = ['IBAN', $employee->iban];
            $rows[] = ['Branch Code', $employee->branch_code];
        }

        if ($employee->resign_date) {
            $rows[] = ['— Exit —', ''];
            $rows[] = ['Exit Date', $this->date($employee->resign_date)];
            $rows[] = ['Exit Type', $employee->exit_type ? ucfirst($employee->exit_type) : null];
            $rows[] = ['Served Notice', $employee->served_notice ? 'Yes' : 'No'];
            $rows[] = ['Reason', $employee->resign_reason];
        }

        $rows[] = ['', ''];
        $rows[] = ['Exported On', now()->format('d M Y, H:i')];

        return $rows;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function leaveBalanceRows(): array
    {
        $rows = [['Year', 'Leave Type', 'Allocated', 'Used', 'Remaining', 'Custom']];

        $balances = $this->employee->leaveBalances
            ->sortByDesc('year')
            ->sortBy('leave_type', SORT_REGULAR)
            ->sortByDesc('year');

        foreach ($balances as $balance) {
            $rows[] = [
                $balance->year,
                $balance->leaveType->name ?? ucfirst($balance->leave_type),
                (float) $balance->allocated,
                (float) $balance->used,
                (float) $balance->remaining,
                $balance->is_override ? 'Yes' : 'No',
            ];
        }

        return $this->withEmptyNote($rows, 'No leave balances on record.');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function careerRows(): array
    {
        $rows = [['Effective Date', 'Record', 'Change', 'Notes', 'Recorded By']];

        foreach ($this->employee->careerEvents as $event) {
            $change = [];

            if ($event->new_position) {
                $change[] = ($event->previous_position ?: '—') . ' → ' . $event->new_position;
            }

            if ($event->new_salary !== null) {
                $change[] = $this->includeSensitive
                    ? number_format((float) $event->previous_salary) . ' → ' . number_format((float) $event->new_salary)
                    : 'Salary change (restricted)';
            }

            if ($event->type === 'confirmation') {
                $change[] = 'Confirmed off probation';
            }

            $rows[] = [
                $this->date($event->effective_date),
                $event->label(),
                implode(' | ', $change),
                $event->notes,
                $event->recordedBy->name ?? 'System',
            ];
        }

        return $this->withEmptyNote($rows, 'No increments, promotions or confirmation on record.');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function leaveRows(): array
    {
        $rows = [['Start Date', 'End Date', 'Leave Type', 'Day Type', 'Days', 'Status', 'Reason']];

        $leaves = $this->employee->leaves()
            ->with('leaveType')
            ->orderByDesc('start_date')
            ->get();

        foreach ($leaves as $leave) {
            $rows[] = [
                $this->date($leave->start_date),
                $this->date($leave->end_date),
                $leave->leaveType->name ?? ucfirst((string) $leave->leave_type),
                str_replace('_', ' ', ucfirst((string) $leave->day_type)),
                $leave->durationInDays(),
                $leave->status,
                $leave->reason,
            ];
        }

        return $this->withEmptyNote($rows, 'No leave records.');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function compensatoryRows(): array
    {
        $rows = [['Holiday Worked', 'Occasion', 'Days', 'Status', 'Credited', 'Approved By', 'Notes']];

        $credits = CompensatoryLeave::where('employee_id', $this->employee->id)
            ->with('approver')
            ->orderByDesc('worked_date')
            ->get();

        foreach ($credits as $credit) {
            $rows[] = [
                $this->date($credit->worked_date),
                $credit->holiday_title ?: 'Manual grant',
                (float) $credit->days_earned,
                $credit->status,
                $credit->is_credited ? 'Yes' : 'No',
                $credit->approver->name ?? '—',
                $credit->notes,
            ];
        }

        return $this->withEmptyNote($rows, 'No compensatory leave earned.');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function cashoutRows(): array
    {
        $rows = [['Leave Year', 'Leave Type', 'Days', 'Amount', 'Payroll Run', 'Status', 'Notes']];

        $cashouts = LeaveCashout::where('employee_id', $this->employee->id)
            ->with('leaveType')
            ->orderByDesc('year')
            ->get();

        foreach ($cashouts as $cashout) {
            $rows[] = [
                $cashout->year,
                $cashout->leaveType->name ?? ucfirst($cashout->leave_type),
                (float) $cashout->days,
                $this->includeSensitive ? (float) $cashout->amount : 'Restricted',
                Carbon::create($cashout->payroll_year, $cashout->payroll_month, 1)->format('M Y'),
                $cashout->status,
                $cashout->notes,
            ];
        }

        return $this->withEmptyNote($rows, 'No leave encashment on record.');
    }

    /**
     * Attendance is summarised by month rather than listed day by day — a full
     * log would swamp the workbook and is available from the attendance module.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function attendanceRows(): array
    {
        $rows = [['Month', 'Present', 'Late', 'Absent', 'Total Records']];

        $byMonth = $this->employee->attendances()
            ->orderByDesc('shift_date')
            ->get()
            ->groupBy(fn ($attendance) => Carbon::parse($attendance->shift_date)->format('Y-m'));

        foreach ($byMonth as $month => $records) {
            $rows[] = [
                Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                $records->where('status', 'Present')->count(),
                $records->filter(fn ($a) => (int) $a->late_duration > 0)->count(),
                $records->where('status', 'Absent')->count(),
                $records->count(),
            ];
        }

        return $this->withEmptyNote($rows, 'No attendance records.');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function exitRows(): array
    {
        $rows = [['Exit Date', 'Type', 'Served Notice', 'Reason', 'Processed By']];

        foreach ($this->employee->exitRecords as $record) {
            $rows[] = [
                $this->date($record->exit_date),
                $record->exit_type ? ucfirst($record->exit_type) : null,
                $record->served_notice ? 'Yes' : 'No',
                $record->exit_reason,
                $record->processedBy->name ?? 'System',
            ];
        }

        return $rows;
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Keep a sheet readable when there is nothing to list.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<int, mixed>>
     */
    protected function withEmptyNote(array $rows, string $note): array
    {
        if (count($rows) === 1) {
            $rows[] = [$note];
        }

        return $rows;
    }

    protected function date($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('d M Y');
    }

    protected function tenure(): ?string
    {
        if (! $this->employee->joining_date) {
            return null;
        }

        $diff = Carbon::parse($this->employee->joining_date)->diff(now());
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }

        return $parts ? implode(', ', $parts) : 'Less than a month';
    }

    protected function employmentStatus(): string
    {
        $employee = $this->employee;

        if ($employee->resign_date) {
            return 'Exited';
        }

        if ($employee->confirmed_at) {
            return 'Confirmed';
        }

        $endsOn = $employee->probationEndsOn();

        if (! $endsOn) {
            return 'Unknown';
        }

        return $endsOn->isFuture() ? 'On Probation' : 'Awaiting Confirmation';
    }

    protected function lastIncrementSummary(): ?string
    {
        $event = $this->employee->lastIncrement;

        if (! $event) {
            return 'None recorded';
        }

        $summary = $event->effective_date->format('d M Y');

        if ($this->includeSensitive) {
            $rise = $event->increase();
            $summary .= ' — ' . number_format((float) $event->previous_salary)
                . ' → ' . number_format((float) $event->new_salary);

            if ($rise['percent'] !== null) {
                $summary .= ' (+' . $rise['percent'] . '%)';
            }
        }

        return $summary;
    }

    protected function lastPromotionSummary(): ?string
    {
        $event = $this->employee->lastPromotion;

        if (! $event) {
            return 'None recorded';
        }

        return $event->effective_date->format('d M Y')
            . ' — ' . ($event->previous_position ?: '—')
            . ' → ' . $event->new_position;
    }
}
