<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\LeaveBalanceReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Company-wide leave balance sheet — every employee's allocated, used and
 * remaining days across every leave type, for a chosen year.
 *
 * Read-only. Balances themselves are changed from the employee's own profile
 * (per-employee allocation override) or earned automatically (compensatory
 * leave, cashout); this report only shows where things stand.
 */
class LeaveBalanceReportController extends Controller
{
    public function index()
    {
        $this->authorize('manage-leave-balances');

        $employees = Employee::accessible()->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveBalanceReportService::reportableLeaveTypes();
        $years = range(now()->year, now()->year - 3);

        return view('admin.leave_balances.index', compact('employees', 'leaveTypes', 'years'));
    }

    public function data(Request $request)
    {
        $this->authorize('manage-leave-balances');

        $year = (int) ($request->input('year') ?: now()->year);
        $leaveTypes = LeaveBalanceReportService::reportableLeaveTypes();

        $query = $this->employeesForYear($year, $request);

        $dataTable = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', fn ($e) => $e->name)
            ->addColumn('position', fn ($e) => $e->position ?? '-');

        foreach ($leaveTypes as $type) {
            $dataTable->addColumn($type->slug . '_allocated', function ($employee) use ($type) {
                return $this->fmt(LeaveBalanceReportService::cell(
                    LeaveBalanceReportService::balancesByType($employee), $type->slug
                )['allocated']);
            });
            $dataTable->addColumn($type->slug . '_used', function ($employee) use ($type) {
                return $this->fmt(LeaveBalanceReportService::cell(
                    LeaveBalanceReportService::balancesByType($employee), $type->slug
                )['used']);
            });
            $dataTable->addColumn($type->slug . '_remaining', function ($employee) use ($type) {
                return $this->fmt(LeaveBalanceReportService::cell(
                    LeaveBalanceReportService::balancesByType($employee), $type->slug
                )['remaining']);
            });
        }

        $dataTable->addColumn('total_remaining', function ($employee) use ($leaveTypes) {
            return $this->fmt(LeaveBalanceReportService::totalRemaining(
                LeaveBalanceReportService::balancesByType($employee), $leaveTypes
            ));
        });

        $dataTable->filter(function ($query) use ($request) {
            $search = trim((string) $request->input('search.value', ''));

            if ($search === '') {
                return;
            }

            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

            $query->where(function ($inner) use ($escaped) {
                $inner->where('employees.name', 'LIKE', "%{$escaped}%")
                    ->orWhere('employees.position', 'LIKE', "%{$escaped}%");
            });
        }, true);

        return $dataTable->make(true);
    }

    public function export(Request $request)
    {
        $this->authorize('manage-leave-balances');

        $year = (int) ($request->input('year') ?: now()->year);

        $export = new \App\Services\Export\LeaveBalanceSheetExport(
            $year,
            $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
        );

        $path = tempnam(sys_get_temp_dir(), 'leave-balances-') . '.xlsx';
        $export->writeTo($path);

        return response()->download($path, $export->filename(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    protected function employeesForYear(int $year, Request $request)
    {
        $query = Employee::accessible()
            ->where('status', 1)
            ->select('employees.id', 'employees.name', 'employees.position')
            ->with(['leaveBalances' => fn ($q) => $q->where('year', $year)->with('leaveType')])
            ->orderBy('name');

        if ($request->filled('employee_id')) {
            $query->where('employees.id', $request->input('employee_id'));
        }

        return $query;
    }

    protected function fmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }
}
