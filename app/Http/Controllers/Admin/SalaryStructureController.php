<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalaryStructureController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalaryStructure::with('branch')->accessible();
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('basic_salary', fn($s) => number_format($s->basic_salary, 2))
                ->addColumn('action', function($s) {
                    return '<button class="btn btn-sm btn-outline-info edit-btn" data-id="'.$s->id.'"><i class="fa fa-edit"></i></button>';
                })
                ->make(true);
        }
        $branches = \App\Models\Branch::where('is_active', true)->get();
        return view('admin.salary_structures.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'basic_salary' => 'required|numeric',
            'branch_id' => 'required|exists:branches,id',
            'effective_date' => 'nullable|date',
            'employee_type' => 'nullable|string',
        ]);

        \DB::transaction(function() use ($request) {
            SalaryStructure::create([
                'branch_id' => $request->branch_id,
                'name' => $request->name,
                'basic_salary' => $request->basic_salary,
                'allowances' => $request->earnings ?? [],
                'deductions' => $request->deductions ?? [],
                'config' => $request->config ?? [],
                'effective_date' => $request->effective_date,
                'employee_type' => $request->employee_type,
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Salary structure configuration saved successfully.');
    }
}
