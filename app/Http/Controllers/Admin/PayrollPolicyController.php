<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollPolicy;
use App\Models\Team;
use App\Models\Employee;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayrollPolicyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PayrollPolicy::query(); // Policies can be global or cross-branch, or scoped by logic
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('policy_type', fn($p) => '<span class="badge bg-secondary">'.$p->policy_type.'</span>')
                ->addColumn('target', function($p) {
                    if ($p->policy_type === 'Global') return 'All Branches';
                    $target = $p->target();
                    return $target ? ($target->name ?? $target->title ?? 'N/A') : 'N/A';
                })
                ->rawColumns(['policy_type'])
                ->make(true);
        }
        
        $teams = Team::accessible()->get();
        return view('admin.payroll_policies.index', compact('teams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'policy_type' => 'required|in:Global,Team,Individual',
            'late_policy' => 'required|array',
        ]);

        PayrollPolicy::create([
            'policy_type' => $request->policy_type,
            'model_id' => $request->model_id,
            'late_policy' => $request->late_policy,
            'break_policy' => $request->break_policy,
            'leave_policy' => $request->leave_policy,
            'is_active' => true,
        ]);

        return back()->with('success', 'Payroll policy created successfully.');
    }
}
