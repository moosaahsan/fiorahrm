<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Performance\PerformanceEvaluation;
use App\Models\Performance\PerformanceSetting;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PerformanceEvaluationController extends Controller
{
    protected $calculator;

    public function __construct(PerformanceScoreCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Show evaluations dashboard.
     */
    public function index(Request $request)
    {
        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));
        
        $settings = PerformanceSetting::all()->keyBy('key');
        
        return view('admin.performance.evaluations.index', compact('month', 'year', 'settings'));
    }

    /**
     * Fetch evaluations data for DataTable.
     */
    public function data(Request $request)
    {
        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        // Query active employees
        $query = Employee::where('status', 1)->with(['attendances', 'team', 'branch']);

        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            $query->accessible();
            
            // Strict enforcement for HR: if no teams assigned, they shouldn't fallback to full access
            if ($user->hasRole('hr')) {
                $assignedTeamIds = \DB::table('user_teams')->where('user_id', $user->id)->pluck('team_id')->toArray();
                if (empty($assignedTeamIds)) {
                    $query->whereRaw('1=0');
                }
            }
        }

        $employees = $query->get();

        // Get existing evaluations for this month/year
        $evaluations = PerformanceEvaluation::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        $data = $employees->map(function ($employee) use ($evaluations, $month, $year) {
            $evaluation = $evaluations->get($employee->id);
            
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position ?? '-',
                'team' => $employee->team->name ?? '-',
                'branch' => $employee->branch->name ?? '-',
                'attendance_score' => $evaluation ? (double) $evaluation->attendance_score : 0.0,
                'leave_score' => $evaluation ? (double) $evaluation->leave_score : 0.0,
                'break_score' => $evaluation ? (double) $evaluation->break_score : 0.0,
                'late_score' => $evaluation ? (double) $evaluation->late_score : 0.0,
                'dress_code_score' => $evaluation ? (double) $evaluation->dress_code_score : 0.0,
                'work_performance_score' => $evaluation ? (double) $evaluation->work_performance_score : 0.0,
                'behavior_score' => $evaluation ? (double) $evaluation->behavior_score : 0.0,
                'total_score' => $evaluation ? (double) $evaluation->total_score : 0.0,
                'comments' => $evaluation ? $evaluation->comments : '',
                'is_evaluated' => $evaluation ? true : false,
            ];
        });

        return DataTables::of($data)
            ->addColumn('action', function ($row) use ($month, $year) {
                return '<button class="btn btn-sm btn-primary edit-evaluation-btn" data-id="' . $row['id'] . '" data-name="' . e($row['name']) . '" data-dress="' . $row['dress_code_score'] . '" data-work="' . $row['work_performance_score'] . '" data-behavior="' . $row['behavior_score'] . '" data-comments="' . e($row['comments']) . '">
                            <i class="fa fa-edit me-1"></i>Evaluate
                        </button>';
            })
            ->make(true);
    }

    /**
     * Compute automated metrics for ALL employees.
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2050',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;

        $query = Employee::where('status', 1);

        $user = auth()->user();
        if (!$user->hasRole('admin')) {
            $query->accessible();
            
            if ($user->hasRole('hr')) {
                $assignedTeamIds = \DB::table('user_teams')->where('user_id', $user->id)->pluck('team_id')->toArray();
                if (empty($assignedTeamIds)) {
                    $query->whereRaw('1=0');
                }
            }
        }

        $employees = $query->get();

        foreach ($employees as $employee) {
            $metrics = $this->calculator->calculate($employee, $year, $month);

            $evaluation = PerformanceEvaluation::firstOrNew([
                'employee_id' => $employee->id,
                'month'       => $month,
                'year'        => $year,
            ]);

            $evaluation->attendance_score = $metrics['attendance_score'];
            $evaluation->leave_score      = $metrics['leave_score'];
            $evaluation->break_score      = $metrics['break_score'];
            $evaluation->late_score       = $metrics['late_score'];
            
            // Recalculate total score (adding any existing manual scores)
            $manualScore = (double) $evaluation->dress_code_score + 
                            (double) $evaluation->work_performance_score + 
                            (double) $evaluation->behavior_score;
                            
            $evaluation->total_score = $metrics['auto_score'] + $manualScore;
            
            // Set evaluator if not set
            if (!$evaluation->evaluator_id) {
                $evaluation->evaluator_id = auth()->id();
            }

            $evaluation->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Automated performance scores calculated successfully for all employees.'
        ]);
    }

    /**
     * Save manual scores (Dress Code, Performance, Behavior) & comments for an employee.
     */
    public function saveManualScore(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2050',
            'dress_code_score' => 'required|numeric|min:0',
            'work_performance_score' => 'required|numeric|min:0',
            'behavior_score' => 'required|numeric|min:0',
            'comments' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $month = (int) $request->month;
        $year = (int) $request->year;

        // Fetch max allowed weights for validation
        $dressWeight = (double) PerformanceSetting::getVal('dress_code_weight', 10.00);
        $workWeight = (double) PerformanceSetting::getVal('work_performance_weight', 20.00);
        $behaviorWeight = (double) PerformanceSetting::getVal('behavior_weight', 20.00);

        if ($request->dress_code_score > $dressWeight) {
            return response()->json(['status' => 'error', 'message' => "Dress Code score cannot exceed weight limit of {$dressWeight}."], 422);
        }
        if ($request->work_performance_score > $workWeight) {
            return response()->json(['status' => 'error', 'message' => "Work Performance score cannot exceed weight limit of {$workWeight}."], 422);
        }
        if ($request->behavior_score > $behaviorWeight) {
            return response()->json(['status' => 'error', 'message' => "Behavior score cannot exceed weight limit of {$behaviorWeight}."], 422);
        }

        $evaluation = PerformanceEvaluation::firstOrNew([
            'employee_id' => $employee->id,
            'month'       => $month,
            'year'        => $year,
        ]);

        // Run calculation of auto metrics on the fly if not already run
        if (!$evaluation->exists) {
            $metrics = $this->calculator->calculate($employee, $year, $month);
            $evaluation->attendance_score = $metrics['attendance_score'];
            $evaluation->leave_score      = $metrics['leave_score'];
            $evaluation->break_score      = $metrics['break_score'];
            $evaluation->late_score       = $metrics['late_score'];
            $autoScore = $metrics['auto_score'];
        } else {
            $autoScore = (double) $evaluation->attendance_score + 
                         (double) $evaluation->leave_score + 
                         (double) $evaluation->break_score + 
                         (double) $evaluation->late_score;
        }

        $evaluation->dress_code_score = $request->dress_code_score;
        $evaluation->work_performance_score = $request->work_performance_score;
        $evaluation->behavior_score = $request->behavior_score;
        $evaluation->comments = $request->comments;
        $evaluation->evaluator_id = auth()->id();
        
        $evaluation->total_score = $autoScore + 
                                   (double) $request->dress_code_score + 
                                   (double) $request->work_performance_score + 
                                   (double) $request->behavior_score;

        $evaluation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Manual feedback and ratings saved successfully.'
        ]);
    }
}
