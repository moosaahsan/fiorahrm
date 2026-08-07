<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
use App\Http\Controllers\Employee\EmployeeAttendanceController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AttendanceController;

Route::middleware(['auth', 'is_employee'])->prefix('employee')->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::post('shift-over', [EmployeeAttendanceController::class, 'markShiftOver'])->name('shift.over');
    Route::get('/dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');
    Route::post('/shift-over', [AttendanceController::class, 'markShiftOver'])->name('shift.over');
});
