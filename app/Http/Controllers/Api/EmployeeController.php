<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController as BaseController;

use Illuminate\Http\Request;
use App\Models\Employee; // Assuming you have an Employee model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\LateArrival;
use App\Models\EmployeeShift;
use App\Models\CompanyOffDay;
use App\Models\HalfDay;
use Carbon\Carbon;
use App\Services\EmployeeService;
use App\Services\LateArrivalService;
use Illuminate\Support\Facades\Log;
use App\Events\AttendanceUpdated;


class EmployeeController extends BaseController
{
    protected $employeeService;
    protected $lateArrivalService;

    public function __construct(EmployeeService $employeeService, LateArrivalService $lateArrivalService)
    {
        $this->employeeService = $employeeService;
        $this->lateArrivalService = $lateArrivalService;
    }

    public function checkIn(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'employee_id' => 'required|integer|exists:employees,id',
                'late_reason' => 'nullable|string|max:500',
            ]);

            $employeeId = $request->input('employee_id');
            $lateReason = $request->input('late_reason');

            $data = $this->lateArrivalService->performCheckIn($employeeId, $lateReason);
            
            return $this->sendResponse($data, $data['message'] ?? 'Checked in successfully.');

        } catch (\Exception $e) {
            Log::error('API Check-in error:', ['message' => $e->getMessage()]);
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    public function clockIn(Request $request)
    {
        // TODO: Start tracking activity (you can implement activity tracking here)
        return response()->json(['message' => 'Clock-in started']);
    }

    public function getActivity(Request $request)
    {
        // Get the latest activity of the authenticated user (you can expand this logic)
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        return response()->json([
            'timestamp' => $employee ? $employee->check_in_time : null,
            'status' => $employee ? $employee->status : 'No data',
        ]);
    }

    public function getAttendanceStatus(Request $request)
    {
        $employeeId = $request->input('employee_id');
        if (!$employeeId) {
            return response()->json(['error' => 'employee_id is required'], 400);
        }

        // Get today's assigned shift
        $assignedShift = $this->getTodayAssignedShift($employeeId);


        if (!$assignedShift) {
            return response()->json(['error' => 'No shift assigned for today'], 404);
        }

        $shiftDate = Carbon::parse($assignedShift->shift_date, 'Asia/Karachi')->toDateString();

        $attendance = Attendance::where('emp_id', $employeeId)
            ->whereDate('shift_date', $shiftDate)
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'No attendance record found for today'], 404);
        }

        // Call service to get attendance status
        $status = $this->employeeService->getAttendanceStatus(
            $attendance->check_in,
            $assignedShift->start_time,
            60, // early threshold minutes (configurable)
            5   // late threshold minutes
        );

        return response()->json([
            'employee_id' => $employeeId,
            'shift_start' => $assignedShift->start_time,
            'check_in' => $attendance->check_in,
            'today_status' => $status,
        ]);
    }
}
