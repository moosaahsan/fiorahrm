<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Performance\PerformanceEvaluation;
use App\Models\Performance\EmployeeOfTheMonth;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeeOfTheMonthController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        // Query top-scoring employees for the select dropdown list
        $candidates = PerformanceEvaluation::where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->orderBy('total_score', 'desc')
            ->get();

        return view('admin.performance.eotm.index', compact('candidates', 'month', 'year'));
    }

    public function data(Request $request)
    {
        $query = EmployeeOfTheMonth::with(['employee.team', 'employee.branch', 'performanceEvaluation.evaluator'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        return DataTables::of($query)
            ->addColumn('month_name', function ($row) {
                return date('F', mktime(0, 0, 0, $row->month, 10)) . ' ' . $row->year;
            })
            ->addColumn('employee_name', fn($row) => $row->employee->name ?? '-')
            ->addColumn('team', fn($row) => $row->employee->team->name ?? '-')
            ->addColumn('branch', fn($row) => $row->employee->branch->name ?? '-')
            ->addColumn('score', fn($row) => (double) ($row->performanceEvaluation->total_score ?? 0.0))
            ->addColumn('selected_by', fn($row) => $row->performanceEvaluation->evaluator->name ?? 'Admin')
            ->addColumn('action', function ($row) {
                $html = '<button class="btn btn-sm btn-outline-primary edit-btn" data-id="'.$row->id.'" data-reason="'.htmlspecialchars($row->bio_comments, ENT_QUOTES).'"><i class="fas fa-edit"></i></button>';
                if (auth()->user()->can('delete-eotm')) {
                    $html .= ' <button class="btn btn-sm btn-outline-danger delete-btn" data-id="'.$row->id.'"><i class="fas fa-trash"></i></button>';
                }
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function selectWinner(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2050',
            'reason' => 'nullable|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;

        // Check if there is already an Employee of the Month for this period
        $existing = EmployeeOfTheMonth::where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee of the Month has already been selected for ' . date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year
            ], 422);
        }

        // Get the evaluation total score for this employee in the month
        $evaluation = PerformanceEvaluation::where('employee_id', $request->employee_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$evaluation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate must have an existing performance evaluation for the selected month to be chosen.'
            ], 422);
        }

        $score = $evaluation->total_score;

        EmployeeOfTheMonth::create([
            'employee_id' => $request->employee_id,
            'performance_evaluation_id' => $evaluation->id,
            'month'       => $month,
            'year'        => $year,
            'bio_comments'=> $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee of the Month selected successfully!'
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $eotm = EmployeeOfTheMonth::findOrFail($id);
        $eotm->update([
            'bio_comments' => $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee of the Month record updated successfully!'
        ]);
    }

    public function destroy($id)
    {
        abort_if(!auth()->user()->can('delete-eotm'), 403, 'Unauthorized action.');

        $eotm = EmployeeOfTheMonth::findOrFail($id);
        $eotm->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee of the Month record deleted successfully!'
        ]);
    }
}
