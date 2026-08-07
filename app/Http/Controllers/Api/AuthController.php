<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\EmployeeService;
use App\Models\Attendance;
use App\Models\LateArrival;


class AuthController extends BaseController
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }


    public function login(Request $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->sendError('Invalid email or password', [], 401);
        }
        $user = Auth::user()->load(['employee.team.department', 'employee.branch']);

        reactivateExpiredSuspensions();
        $user->load('employee');

        $blockReason = $user->employee ? getEmployeeAccessBlockReason($user->employee) : null;
        if ($blockReason) {
            Auth::logout();
            return $this->sendError($blockReason, [], 403);
        }

        if (!$user->employee) {
            return $this->sendError('Employee record not found', [], 403);
        }

        $user->employee->profile_pic_url = get_profile_picture_url($user->employee->profile_pic);
        $token = $user->createToken('MyApp')->plainTextToken;
        $dashboardData = $this->employeeService->getAppData($user->employee->id);


        return $this->sendResponse([
            'token' => $token,
            'user' => $user,
            ...$dashboardData,
            'app_settings' => get_app_settings(),
        ], 'User login successfully.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
