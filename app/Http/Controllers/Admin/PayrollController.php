<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Services\PayrollCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PayrollController extends Controller
{
    protected $calculator;

    public function __construct(PayrollCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function index(Request $request)
    {
        $this->authorize('view-payroll');
        if ($request->ajax()) {
            $query = Payroll::with('branch')->accessible();
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('month', fn($p) => date('F', mktime(0, 0, 0, $p->month, 1)))
                ->addColumn('branch_name', fn($p) => $p->branch->name ?? 'Global')
                ->addColumn('created_at_human', fn($p) => $p->created_at->diffForHumans())
                ->editColumn('status', fn($p) => strtolower($p->status))
                ->addColumn('action', function($p) {
                    return '<div class="d-flex justify-content-center gap-2">
                        <a href="'.route('admin.payroll.show', $p->id).'" class="btn-saas-action" title="View Details"><i class="fa fa-eye"></i></a>
                        <button class="btn-saas-action delete-payroll-btn" data-id="'.$p->id.'" title="Delete Ledger Record"><i class="fa fa-trash-alt"></i></button>
                    </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.payroll.index');
    }

    public function create()
    {
        $this->authorize('generate-payroll');
        $branches = \App\Models\Branch::accessible()->get();
        return view('admin.payroll.generate', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('generate-payroll');
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $branchId = $request->branch_id;

        // Prevent duplicate generation for same month/branch
        $exists = Payroll::where('branch_id', $branchId)->where('month', $request->month)->where('year', $request->year)->exists();
        if ($exists) {
            return back()->with('error', 'Payroll for this month and branch already exists.');
        }

        DB::beginTransaction();
        try {
            $payroll = Payroll::create([
                'branch_id' => $branchId,
                'month' => $request->month,
                'year' => $request->year,
                'status' => 'Draft',
                'generated_by' => auth()->id(),
            ]);

            $employees = Employee::accessible()->whereNull('resign_date')->get();
            
            $employeeIds = $employees->pluck('id')->toArray();
            $batchCalculations = $this->calculator->calculateBatchPayroll($employeeIds, $request->month, $request->year);
            
            $totalGross = 0;
            $totalDeduction = 0;
            $totalNet = 0;

            foreach ($employees as $employee) {
                $calc = $batchCalculations[$employee->id] ?? null;
                if (!$calc) continue;
                
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'gross_salary' => $calc['gross_salary'],
                    'total_deductions' => $calc['total_deductions'],
                    'net_salary' => $calc['net_salary'],
                    'earnings_detail' => $calc['earnings_detail'],
                    'deductions_detail' => $calc['deductions_detail'],
                ]);

                $totalGross += $calc['gross_salary'];
                $totalDeduction += $calc['total_deductions'];
                $totalNet += $calc['net_salary'];
            }

            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeduction,
                'total_net' => $totalNet,
            ]);

            DB::commit();
            return redirect()->route('admin.payroll.show', $payroll->id)->with('success', 'Payroll generated successfully as Draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $this->authorize('view-payroll');
        $payroll = Payroll::accessible()->findOrFail($id);

        if ($request->ajax()) {
            $query = PayrollItem::accessible()
                        ->with('employee')
                        ->where('payroll_id', $id)
                        ->select('payroll_items.*');
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function($item) {
                    return '<a href="'.route('admin.payroll.payslip', $item->id).'" target="_blank" class="btn btn-sm btn-outline-indigo"><i class="fa fa-file-invoice"></i> Payslip</a>';
                })
                ->make(true);
        }

        return view('admin.payroll.show', compact('payroll'));
    }

    public function payslip($itemId)
    {
        // Security Hardening: Ensure user has access through monitoring clusters, not just session
        $item = PayrollItem::whereHas('payroll', function($q) {
            $q->accessible();
        })->with(['employee.branch', 'payroll'])->findOrFail($itemId);

        return view('admin.payroll.payslip', compact('item'));
    }

    public function approve($id)
    {
        $this->authorize('approve-payroll');
        $payroll = Payroll::accessible()->findOrFail($id);
        $payroll->update(['status' => 'Approved']);
        return back()->with('success', 'Payroll approved successfully.');
    }

    public function destroy($id)
    {
        $this->authorize('generate-payroll');
        $payroll = Payroll::accessible()->findOrFail($id);
        
        if ($payroll->status === 'Approved') {
            return response()->json(['success' => false, 'message' => 'Approved payroll cycles cannot be deleted.']);
        }
        
        $payroll->delete();
        return response()->json(['success' => true, 'message' => 'Payroll cycle deleted successfully.']);
    }
}
