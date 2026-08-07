<?php

namespace App\Http\Controllers\Employee;

use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\LateArrivalService;
use Illuminate\Support\Facades\Log;


class CheckInController extends Controller
{
    protected $lateArrivalService;

    public function __construct(LateArrivalService $lateArrivalService)
    {
        $this->lateArrivalService = $lateArrivalService;
    }
    public function show(Request $request)
    {
        $employee = auth()->user()->employee;
        $shiftAssignment = \App\Models\EmployeeShift::where('emp_id', $employee->id)
            ->with('shift')
            ->latest('created_at')
            ->first();

        if (!$shiftAssignment || !$shiftAssignment->shift) {
            return redirect()->back()->with('error', 'No shift assigned for today.');
        }

        $shift = $shiftAssignment;
        $settings = get_employee_settings($employee->id);

        return view('employee.checkin.checkin', compact('shift', 'settings'));
    }


    public function store(Request $request)
    {
        try {
            $reason = $request->input('late_reason');
            $data = $this->lateArrivalService->performCheckIn(auth()->user()->employee->id, $reason);
            
            if ($request->ajax()) {
                return response()->json($data);
            }
            
            return redirect()->route('employee.dashboard')->with('success', 'Checked in successfully.');
        } catch (\Exception $e) {
            \Log::error('Check-in failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            
            return redirect()->route('employee.checkin')->with('error', $e->getMessage());
        }
    }


}
