<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\EmployeeBreakController;
use App\Http\Controllers\Api\AppDataController;
use App\Http\Controllers\BreakController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\CelebrationController;


Route::get('/ping', function () {
    return response()->json(['pong']);
});

Route::get('/debug-token', function (Request $request) {
    return response()->json([
        'Authorization' => $request->header('Authorization'),
    ]);
})->middleware('auth:sanctum');



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

// ✅ PUBLIC EXTERNAL INTERVIEW APPLICATION
Route::get('/external/jobs', [\App\Http\Controllers\Admin\JobPostingController::class, 'getPublicJobs']);
Route::post('/external/apply', [\App\Http\Controllers\Admin\InterviewController::class, 'storePublic'])->middleware('throttle:3,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('checkin', [EmployeeController::class, 'checkIn']);
    Route::get('get-activity', [EmployeeController::class, 'getActivity']);
    Route::get('attendance/status', [AttendanceController::class, 'getTodayStatus']);
    Route::post('employee/attendance-status', [EmployeeController::class, 'getAttendanceStatus']);

    Route::post('employee/mark-break', [EmployeeBreakController::class, 'markBreak']);
    Route::post('employee/end-break', [EmployeeBreakController::class, 'endBreak']);
    Route::get('/break-status/{employeeId}', [EmployeeBreakController::class, 'breakStatus']);

    Route::get('/employee/break-stats', [EmployeeBreakController::class, 'getBreakStats']);
    Route::get('/employee/app-settings', [EmployeeBreakController::class, 'getAppSettings']);
    Route::post('/refresh-data', [AppDataController::class, 'refresh']);
    Route::get('/employee/upcoming-holidays', [AppDataController::class, 'upcomingHolidays']);
    Route::get('/employee/profile', [AppDataController::class, 'employeeProfile']);
    Route::get('/employee/dashboard-attendance', [AppDataController::class, 'dashboardAttendance']);
    Route::get('/employee/attendance/today-attendance-status', [AttendanceController::class, 'todayAttendanceStatus']);
    Route::get('/employee/attendance/monthly', [AttendanceController::class, 'thisMonthAttendanceStatus']);
    Route::get('/employee/attendance/details/{id}', [AttendanceController::class, 'getAttendanceDetails']);
    // for broadcasting 
    Route::post('/break/update', [BreakController::class, 'updateBreak'])->name('api.break.update');
    Route::get('breaks/active', [EmployeeBreakController::class, 'getActiveBreak'])->name('break.active');
    Route::get('breaks/{id}/duration', [EmployeeBreakController::class, 'getBreakDuration'])->name('break.duration');
    Route::get('breaks/live', [EmployeeBreakController::class, 'getBreakLive'])->name('break.live');
    Route::get('employee/current-attendance', [EmployeeController::class, 'currentAttendance']);

    // Leave Management
    Route::get('employee/leaves/stats', [LeaveController::class, 'getStats']);
    Route::get('employee/leaves/history', [LeaveController::class, 'getHistory']);
    Route::get('employee/leaves/types', [LeaveController::class, 'getTypes']);
    Route::post('employee/leaves/apply', [LeaveController::class, 'apply']);

    Route::get('employee/celebrations', [CelebrationController::class, 'index'])
        ->middleware('throttle:60,1');
});
