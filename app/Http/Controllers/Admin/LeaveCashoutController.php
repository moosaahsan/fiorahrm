<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveCashout;
use App\Services\LeaveCashoutService;
use Illuminate\Http\Request;

/**
 * HR-driven year-end encashment of unused leave.
 *
 * HR picks the employee, the leave year and type, how many days to pay out and
 * the amount. There is no rate formula — the figure is entered by hand.
 */
class LeaveCashoutController extends Controller
{
    public function index()
    {
        $this->authorize('view-leave-cashouts');

        $summary = [
            'pending' => LeaveCashout::accessible()->pending()->count(),
            'pending_amount' => (float) LeaveCashout::accessible()->pending()->sum('amount'),
            'paid_amount' => (float) LeaveCashout::accessible()->where('status', LeaveCashout::STATUS_PAID)->sum('amount'),
            'days_cashed' => (float) LeaveCashout::accessible()->where('status', '!=', LeaveCashout::STATUS_CANCELLED)->sum('days'),
        ];

        $employees = Employee::accessible()->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveCashoutService::cashableTypes();

        return view('admin.leave_cashouts.index', compact('summary', 'employees', 'leaveTypes'));
    }

    public function data(Request $request)
    {
        $this->authorize('view-leave-cashouts');

        $query = LeaveCashout::accessible()
            ->with(['employee:id,name', 'leaveType:id,name,slug', 'processedBy:id,name'])
            ->latest('id');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $canManage = auth()->user()->can('manage-leave-cashouts');

        return \DataTables::of($query)
            ->addColumn('employee_name', fn ($r) => $r->employee?->name ?? '-')
            ->addColumn('leave_type_name', fn ($r) => $r->leaveType?->name ?? ucfirst($r->leave_type))
            ->addColumn('days_out', fn ($r) => rtrim(rtrim(number_format((float) $r->days, 2), '0'), '.'))
            ->addColumn('amount_out', fn ($r) => 'Rs. ' . number_format((float) $r->amount, 2))
            ->addColumn('payroll_run', fn ($r) => \Carbon\Carbon::create($r->payroll_year, $r->payroll_month, 1)->format('M Y'))
            ->addColumn('processed_by_name', fn ($r) => $r->processedBy?->name ?? '-')
            ->addColumn('action', function ($r) use ($canManage) {
                if (! $canManage || $r->status !== LeaveCashout::STATUS_PENDING) {
                    return '';
                }

                return '<div class="d-flex gap-2 justify-content-end">'
                    . '<button class="btn-saas-action cashout-cancel text-danger" title="Cancel and return the days" data-id="' . $r->id . '"><i class="fas fa-undo"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Balances still available to cash out — drives the create form.
     */
    public function balances(Request $request)
    {
        $this->authorize('view-leave-cashouts');

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer',
        ]);

        $employee = Employee::accessible()->findOrFail($request->employee_id);

        $balances = LeaveCashoutService::eligibleBalances($employee, (int) $request->year)
            ->map(fn ($balance) => [
                'leave_type' => $balance->leave_type,
                'label' => $balance->leaveType?->name ?? ucfirst($balance->leave_type),
                'remaining' => (float) $balance->remaining,
            ])
            ->values();

        return response()->json(['balances' => $balances]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-leave-cashouts');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer|min:2000|max:2100',
            'leave_type' => 'required|string|exists:leave_types,slug',
            'days' => 'required|numeric|min:0.5',
            'amount' => 'required|numeric|min:0',
            'payroll_month' => 'required|integer|between:1,12',
            'payroll_year' => 'required|integer|min:2000|max:2100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $employee = Employee::accessible()->findOrFail($validated['employee_id']);

        try {
            LeaveCashoutService::create(
                employee: $employee,
                year: (int) $validated['year'],
                leaveTypeSlug: $validated['leave_type'],
                days: (float) $validated['days'],
                amount: (float) $validated['amount'],
                payrollMonth: (int) $validated['payroll_month'],
                payrollYear: (int) $validated['payroll_year'],
                processedBy: auth()->id(),
                notes: $validated['notes'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'A cashout for that employee, leave type and year already exists.')->withInput();
        }

        return redirect()->route('admin.leave_cashouts.index')
            ->with('success', 'Leave encashment recorded. It will appear on the selected payroll run.');
    }

    public function cancel(Request $request, $id)
    {
        $this->authorize('manage-leave-cashouts');

        $cashout = LeaveCashout::accessible()->findOrFail($id);

        try {
            LeaveCashoutService::cancel($cashout, auth()->id(), $request->input('reason'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cashout cancelled and the days returned to the balance.',
        ]);
    }
}
