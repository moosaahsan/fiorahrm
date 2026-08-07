<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceEvaluation;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeePerformanceController extends Controller
{
    /**
     * View history of own evaluations.
     */
    public function index()
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            abort(403, 'Employee profile not associated with this user.');
        }
        
        return view('employee.performance.index', compact('employee'));
    }

    /**
     * Get DataTable data for own evaluations.
     */
    public function data(Request $request)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = PerformanceEvaluation::where('employee_id', $employee->id)
            ->with('evaluator')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        return DataTables::of($query)
            ->addColumn('month_name', function ($row) {
                return date('F', mktime(0, 0, 0, $row->month, 10)) . ' ' . $row->year;
            })
            ->addColumn('evaluator_name', fn($row) => $row->evaluator->name ?? '-')
            ->addColumn('auto_score', fn($row) => (double) $row->attendance_score + (double) $row->leave_score + (double) $row->break_score + (double) $row->late_score)
            ->addColumn('manual_score', fn($row) => (double) $row->dress_code_score + (double) $row->work_performance_score + (double) $row->behavior_score)
            ->make(true);
    }
}
