<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Performance\PerformanceEvaluationController;
use App\Http\Controllers\Performance\EmployeePerformanceController;
use App\Http\Controllers\Performance\PerformanceSettingController;
use App\Http\Controllers\Performance\EmployeeOfTheMonthController;

// 1. Admin/Manager Routes
Route::middleware(['auth', 'is_admin'])
    ->prefix('admin/performance')
    ->name('admin.performance.')
    ->group(function () {
        
        // Root / View Evaluations dashboard (requires view permission)
        Route::middleware('permission:view-performance-evaluation')->group(function () {
            Route::get('/evaluations', [PerformanceEvaluationController::class, 'index'])->name('evaluations.index');
            Route::get('/evaluations/data', [PerformanceEvaluationController::class, 'data'])->name('evaluations.data');
            
            Route::get('/employee-of-the-month', [EmployeeOfTheMonthController::class, 'index'])->name('eotm.index');
            Route::get('/employee-of-the-month/data', [EmployeeOfTheMonthController::class, 'data'])->name('eotm.data');
        });

        // Manage actions (requires manage permission)
        Route::middleware('permission:manage-performance-evaluation')->group(function () {
            Route::post('/evaluations/calculate', [PerformanceEvaluationController::class, 'calculate'])->name('evaluations.calculate');
            Route::post('/evaluations/save-manual', [PerformanceEvaluationController::class, 'saveManualScore'])->name('evaluations.save_manual');
            
            Route::post('/employee-of-the-month/select', [EmployeeOfTheMonthController::class, 'selectWinner'])->name('eotm.select');
            Route::delete('/employee-of-the-month/{id}', [EmployeeOfTheMonthController::class, 'destroy'])->name('eotm.destroy');
            Route::put('/employee-of-the-month/{id}', [EmployeeOfTheMonthController::class, 'update'])->name('eotm.update');
            
            Route::get('/settings', [PerformanceSettingController::class, 'index'])->name('settings.index');
            Route::post('/settings', [PerformanceSettingController::class, 'update'])->name('settings.update');
        });
    });

// 2. Employee Routes (view own performance)
Route::middleware(['auth', 'is_employee', 'permission:view-own-performance'])
    ->prefix('employee/performance')
    ->name('employee.performance.')
    ->group(function () {
        Route::get('/my-evaluations', [EmployeePerformanceController::class, 'index'])->name('index');
        Route::get('/my-evaluations/data', [EmployeePerformanceController::class, 'data'])->name('data');
    });
