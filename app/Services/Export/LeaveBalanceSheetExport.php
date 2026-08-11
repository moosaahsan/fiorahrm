<?php

namespace App\Services\Export;

use App\Models\Employee;
use App\Services\LeaveBalanceReportService;

/**
 * Excel counterpart to the on-screen leave balance sheet (Admin → Leave
 * Balances). Same shape as the matrix — every employee, every leave type's
 * allocated/used/remaining — via LeaveBalanceReportService, so the download
 * always matches what HR sees on screen.
 */
class LeaveBalanceSheetExport
{
    public function __construct(
        protected int $year,
        protected ?int $employeeId = null,
    ) {
    }

    public function writeTo(string $path): string
    {
        $writer = new XlsxWriter();
        $writer->addSheet('Leave Balances', $this->rows(), $this->columnWidths());
        $writer->save($path);

        return $path;
    }

    public function filename(): string
    {
        return 'leave-balances-' . $this->year . '.xlsx';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function rows(): array
    {
        $leaveTypes = LeaveBalanceReportService::reportableLeaveTypes();

        $header = ['Sr#', 'Employee Name', 'Position'];

        foreach ($leaveTypes as $type) {
            $header[] = $type->name . ' — Allocated';
            $header[] = $type->name . ' — Used';
            $header[] = $type->name . ' — Remaining';
        }

        $header[] = 'Total Remaining';

        $rows = [$header];

        foreach ($this->employees($this->year) as $index => $employee) {
            $balances = LeaveBalanceReportService::balancesByType($employee);

            $row = [$index + 1, $employee->name, $employee->position ?? '-'];

            foreach ($leaveTypes as $type) {
                $cell = LeaveBalanceReportService::cell($balances, $type->slug);
                $row[] = $cell['allocated'];
                $row[] = $cell['used'];
                $row[] = $cell['remaining'];
            }

            $row[] = LeaveBalanceReportService::totalRemaining($balances, $leaveTypes);

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    protected function employees(int $year)
    {
        $query = Employee::accessible()
            ->where('status', 1)
            ->select('employees.id', 'employees.name', 'employees.position')
            ->with(['leaveBalances' => fn ($q) => $q->where('year', $year)->with('leaveType')])
            ->orderBy('name');

        if ($this->employeeId) {
            $query->where('employees.id', $this->employeeId);
        }

        return $query->get();
    }

    /**
     * @return array<int, float>
     */
    protected function columnWidths(): array
    {
        $leaveTypeCount = LeaveBalanceReportService::reportableLeaveTypes()->count();

        $widths = [0 => 6, 1 => 24, 2 => 18];

        for ($i = 0; $i < $leaveTypeCount * 3; $i++) {
            $widths[3 + $i] = 13;
        }

        $widths[3 + $leaveTypeCount * 3] = 15;

        return $widths;
    }
}
