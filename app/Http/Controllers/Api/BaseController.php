<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\EmployeeShift;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 401)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    // protected function getTodayAssignedShift($employeeId)
    // {
    //     $employeeShift = EmployeeShift::where('emp_id', $employeeId)
    //         ->with('shift')
    //         ->first();

    //     return $employeeShift?->shift;
    // }

    protected function getTodayAssignedShift($employeeId)
    {
        $employeeShift = EmployeeShift::with('shift')
            ->where('emp_id', $employeeId)
            ->latest('assigned_at') // get most recent assignment
            ->first();

        return $employeeShift; // ✅ return full model, not just shift
    }


}