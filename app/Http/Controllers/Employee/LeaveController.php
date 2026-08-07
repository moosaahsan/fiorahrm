<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index()
    {
        $employee = Auth::user()->employee;
        $employeeId = $employee->id;

        // Fetch Leave Types for the dropdown
        $leaveTypes = LeaveType::where('is_active', true)->get();

        // Fetch current balances
        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', date('Y'))
            ->get();

        $summary = [
            'total' => Leave::where('employee_id', $employeeId)->count(),
            'approved' => Leave::where('employee_id', $employeeId)->where('status', 'Approved')->whereYear('start_date', date('Y'))->count(),
            'pending' => Leave::where('employee_id', $employeeId)->where('status', 'Pending')->count(),
            'active_today' => Leave::where('employee_id', $employeeId)->where('status', 'Approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count(),
        ];

        return view('employee.leaves.index', compact('leaveTypes', 'balances', 'summary'));
    }

    public function data()
    {
        $employeeId = Auth::user()->employee->id;
        $leaves = Leave::with(['leaveType', 'shift'])
            ->where('employee_id', $employeeId)
            ->latest();

        return DataTables::eloquent($leaves)
            ->addColumn('leave_type_name', function ($leave) {
                return $leave->leaveType ? $leave->leaveType->name : ucfirst($leave->leave_type);
            })
            ->editColumn('start_date', function ($leave) {
                return $leave->start_date->format('d-M-Y');
            })
            ->editColumn('end_date', function ($leave) {
                return $leave->end_date->format('d-M-Y');
            })
            ->addColumn('duration', function ($leave) {
                $days = $leave->start_date->diffInDays($leave->end_date) + 1;
                if ($leave->day_type !== 'full_day') {
                    return '0.5 Day';
                }
                return $days . ' Day(s)';
            })
            ->editColumn('status', function ($leave) {
                $statusClass = match ($leave->status) {
                    'Approved' => 'success',
                    'Pending' => 'warning',
                    'Rejected' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $statusClass . '">' . $leave->status . '</span>';
            })
            ->rawColumns(['status'])
            ->with([
                'summary' => [
                    'total' => Leave::where('employee_id', $employeeId)->count(),
                    'approved' => Leave::where('employee_id', $employeeId)->where('status', 'Approved')->whereYear('start_date', date('Y'))->count(),
                    'pending' => Leave::where('employee_id', $employeeId)->where('status', 'Pending')->count(),
                    'active_today' => Leave::where('employee_id', $employeeId)->where('status', 'Approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count(),
                ]
            ])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'day_type' => 'required|in:full_day,first_half,second_half',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $employee = Auth::user()->employee;
                
                Leave::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $employee->shift_id,
                    'leave_type' => $request->leave_type,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'day_type' => $request->day_type,
                    'reason' => $request->reason,
                    'status' => 'Pending',
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Leave request submitted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
