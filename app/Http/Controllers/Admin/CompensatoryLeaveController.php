<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompensatoryLeave;
use App\Models\Employee;
use App\Services\CompensatoryLeaveService;
use Illuminate\Http\Request;

/**
 * HR-facing management of compensatory leave earned on public holidays.
 *
 * Credits are usually created automatically when attendance lands on a holiday;
 * HR reviews them here, and can also grant one by hand.
 */
class CompensatoryLeaveController extends Controller
{
    public function index()
    {
        $this->authorize('view-compensatory-leaves');

        $summary = [
            'pending' => CompensatoryLeave::accessible()->pending()->count(),
            'approved' => CompensatoryLeave::accessible()->approved()->count(),
            'days_approved' => (float) CompensatoryLeave::accessible()->approved()->sum('days_earned'),
            'this_year' => CompensatoryLeave::accessible()->whereYear('worked_date', now()->year)->count(),
        ];

        $employees = Employee::accessible()->where('status', 1)->orderBy('name')->get(['id', 'name']);

        return view('admin.compensatory_leaves.index', compact('summary', 'employees'));
    }

    public function data(Request $request)
    {
        $this->authorize('view-compensatory-leaves');

        $query = CompensatoryLeave::accessible()
            ->with(['employee:id,name', 'approver:id,name', 'holiday'])
            ->latest('worked_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('year')) {
            $query->whereYear('worked_date', $request->year);
        }

        $canManage = auth()->user()->can('manage-compensatory-leaves');

        return \DataTables::of($query)
            ->addColumn('employee_name', fn ($r) => $r->employee?->name ?? '-')
            ->addColumn('worked_on', fn ($r) => $r->worked_date?->format('d M Y') ?? '-')
            ->addColumn('holiday', fn ($r) => $r->holiday_title ?: ($r->holiday?->note ?? 'Manual grant'))
            ->addColumn('days', fn ($r) => rtrim(rtrim(number_format((float) $r->days_earned, 2), '0'), '.'))
            ->addColumn('approved_by_name', fn ($r) => $r->approver?->name ?? '-')
            ->addColumn('balance_state', fn ($r) => $r->is_credited ? 'Credited' : 'Not credited')
            ->addColumn('action', function ($r) use ($canManage) {
                if (! $canManage || $r->status !== CompensatoryLeave::STATUS_PENDING) {
                    return '';
                }

                return '<div class="d-flex gap-2 justify-content-end">'
                    . '<button class="btn-saas-action cpl-approve text-success" title="Approve" data-id="' . $r->id . '"><i class="fas fa-check"></i></button>'
                    . '<button class="btn-saas-action cpl-reject text-danger" title="Reject" data-id="' . $r->id . '"><i class="fas fa-times"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-compensatory-leaves');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'worked_date' => 'required|date',
            'days_earned' => 'nullable|numeric|min:0.5|max:30',
            'notes' => 'nullable|string|max:1000',
        ]);

        $employee = Employee::accessible()->findOrFail($validated['employee_id']);

        $credit = CompensatoryLeaveService::grant(
            employee: $employee,
            workedDate: $validated['worked_date'],
            days: isset($validated['days_earned']) ? (float) $validated['days_earned'] : null,
            notes: $validated['notes'] ?? 'Granted manually by HR',
        );

        if (! $credit->wasRecentlyCreated) {
            return redirect()->route('admin.compensatory_leaves.index')
                ->with('error', 'A compensatory leave credit already exists for that employee on that date.');
        }

        return redirect()->route('admin.compensatory_leaves.index')
            ->with('success', 'Compensatory leave credit added.');
    }

    public function approve($id)
    {
        $this->authorize('manage-compensatory-leaves');

        $credit = CompensatoryLeave::accessible()->findOrFail($id);

        CompensatoryLeaveService::approve($credit, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Compensatory leave approved and credited to the balance.',
        ]);
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('manage-compensatory-leaves');

        $credit = CompensatoryLeave::accessible()->findOrFail($id);

        CompensatoryLeaveService::reject($credit, auth()->id(), $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Compensatory leave rejected.',
        ]);
    }
}
