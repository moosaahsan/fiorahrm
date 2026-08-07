<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveAdjustment;
use App\Helpers\ActivityLogger;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class LeaveAdjustmentController extends Controller
{
    /**
     * Get scoped employee IDs based on current user's role.
     */
    private function getScopedEmployeeIds()
    {
        $user = auth()->user();

        // Admin / HR → all employees
        if ($user->hasRole(['admin', 'hr', 'administrator'])) {
            return null; // null = no restriction
        }

        $selfId = $user->employee->id ?? 0;

        // Managed department employees
        $deptEmpIds = \App\Models\Employee::select('employees.id')
            ->join('teams', 'employees.team_id', '=', 'teams.id')
            ->join('departments', 'teams.department_id', '=', 'departments.id')
            ->where('departments.manager_id', $user->id)
            ->pluck('id')->toArray();

        // Led team employees
        $teamEmpIds = \App\Models\Employee::select('employees.id')
            ->join('teams', 'employees.team_id', '=', 'teams.id')
            ->where('teams.leader_id', $user->id)
            ->pluck('id')->toArray();

        return collect(array_merge([$selfId], $deptEmpIds, $teamEmpIds))->unique()->values()->toArray();
    }

    public function index()
    {
        ActivityLogger::log('view', 'LeaveAdjustment', ActivityLogger::format('view', 'LeaveAdjustment', 'All Audit Logs', 'Listing'));

        $scopedIds = $this->getScopedEmployeeIds();
        $employees = \App\Models\Employee::where('status', 1)
            ->when($scopedIds !== null, fn($q) => $q->whereIn('id', $scopedIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.leave_adjustments.index', compact('employees'));
    }

    public function data(Request $request)
    {
        $scopedIds = $this->getScopedEmployeeIds();

        $query = LeaveAdjustment::with(['employee'])->select('leave_adjustments.*');

        // Scope data to accessible employees
        if ($scopedIds !== null) {
            $query->whereIn('leave_adjustments.emp_id', $scopedIds);
        }

        if ($request->filled('employee_id')) {
            $query->where('emp_id', $request->employee_id);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        return DataTables::of($query)
            ->addColumn('employee_name', function($row) {
                $name = $row->employee->name ?? 'N/A';
                $initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                return '<div class="d-flex align-items-center" style="gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #eef2ff; color: #6366f1; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; justify-content: center;">' . $initials . '</div>
                    <span style="font-weight: 700; color: #0f172a;">' . e($name) . '</span>
                </div>';
            })
            ->editColumn('month', fn($row) => '<span style="font-weight: 600;">' . Carbon::create()->month($row->month)->format('F') . '</span>')
            ->editColumn('adjustment_amount', function($row) {
                $class = $row->adjustment_amount > 0 ? 'adjustment-positive' : 'adjustment-negative';
                $sign = $row->adjustment_amount > 0 ? '+' : '';
                return '<span class="' . $class . '">' . $sign . $row->adjustment_amount . ' days</span>';
            })
            ->editColumn('applied_at', fn($row) => $row->applied_at ? '<span style="font-weight: 600; color: #475569;">' . $row->applied_at->format('d M, Y g:i A') . '</span>' : '<span class="text-muted">-</span>')
            ->editColumn('policy_name', fn($row) => '<span class="policy-pill">' . ucfirst(str_replace('_', ' ', $row->policy_name)) . '</span>')
            ->editColumn('notes', fn($row) => '<span style="color: #64748b; font-size: 0.85rem;">' . e($row->notes ?? '-') . '</span>')
            ->rawColumns(['employee_name', 'adjustment_amount', 'applied_at', 'policy_name', 'month', 'notes'])
            ->make(true);
    }
}
