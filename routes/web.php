<?php

use App\Models\Attendance;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
use App\Http\Controllers\Employee\EmployeeAttendanceController;
use App\Http\Controllers\Employee\EmployeeBreakController;


use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Employee\CheckInController as EmployeeCheckInController;
use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\HalfDayController;
use App\Http\Controllers\Admin\LateArrivalController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\MonthlyAttendanceController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\BreakController;
use App\Http\Controllers\Admin\EmployeeBreakController as AdminBreakController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\DepartmentController;

use App\Models\Shift;
use App\Models\EmployeeShift;




use App\Http\Middleware\IsAdmin;

// ✅ LOGIN ROUTES
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ✅ LOGOUT
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ✅ SHARED ADMIN/EMPLOYEE ROUTES
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::middleware('permission:checkout-employee')->group(function () {
        Route::post('/dashboard/checkout-employee', [AdminDashboardController::class, 'checkoutEmployee'])->name('admin.dashboard.checkout_employee');
    });
});

// ✅ EXCLUSIVE ADMIN ROUTES
Route::middleware(['auth', 'is_admin'])->prefix('admin')->group(function () {


    // Attendance
    Route::get('/attendance/all/{year?}', [AdminAttendanceController::class, 'getAttendanceForAllEmployeesWithMidnightShiftHandling']);
    Route::get('/attendance/employee/{empId}/{year?}', [AdminAttendanceController::class, 'getAttendanceForSpecificEmployeeWithMidnightShiftHandling']);

    // App Settings
    Route::middleware('permission:manage-app-settings')->group(function () {
        Route::get('/app_settings', [AppSettingsController::class, 'index'])->name('app_settings.index');
        Route::post('/app_settings/update', [AppSettingsController::class, 'update'])->name('app_settings.update');
    });

    // Team-Based Employee Settings
    Route::middleware('permission:manage-employee-settings')->group(function () {
        Route::get('/settings/employee', [\App\Http\Controllers\Admin\TeamSettingsController::class, 'index'])->name('admin.settings.employee');
        Route::get('/settings/employee/team/{id}', [\App\Http\Controllers\Admin\TeamSettingsController::class, 'getTeamSettings'])->name('admin.settings.getTeamSettings');
        Route::post('/settings/employee/apply-team', [\App\Http\Controllers\Admin\TeamSettingsController::class, 'applyToTeam'])->name('admin.settings.applyToTeam');
    });

    // Employees
    Route::middleware('permission:view-employee')->group(function () {
        Route::get('/employee_data/{id}', [EmployeeController::class, 'employee_data'])->name('employee_data');
        Route::get('employees', [EmployeeController::class, 'index'])->name('admin.employees.index');
        // Registered before the resource so it is not swallowed by employees/{employee}.
        Route::get('employees/{id}/export', [EmployeeController::class, 'exportExcel'])->name('admin.employees.export');
        Route::resource('employees', EmployeeController::class)->except(['index', 'show'])->names('admin.employees');
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('admin.employees.show');
        Route::get('employees/{id}/cnic/{side}', [EmployeeController::class, 'viewCnic'])->name('admin.employees.cnic.view');
    });

    // Career milestones: increments, promotions, confirmation off probation
    Route::middleware('permission:edit-employee')->group(function () {
        Route::post('/employees/{id}/career-events', [EmployeeController::class, 'storeCareerEvent'])->name('admin.employees.career_events.store');
        Route::delete('/employees/{id}/career-events/{event}', [EmployeeController::class, 'destroyCareerEvent'])->name('admin.employees.career_events.destroy');
    });

    Route::middleware('permission:offboard-employee|manage-employees|delete-employee')->group(function () {
        Route::post('/employees/{id}/resign', [EmployeeController::class, 'resign'])->name('admin.employees.resign');
        Route::post('/employees/{id}/rejoin', [EmployeeController::class, 'rejoin'])->name('admin.employees.rejoin');
        Route::post('/employees/assign-shift', [EmployeeController::class, 'assignShift'])->name('admin.employees.assignShift');
        Route::post('/employees/assign-team', [EmployeeController::class, 'assignTeam'])->name('admin.employees.assignTeam');
        Route::get('/employees/{id}/assign-leaves', [EmployeeController::class, 'assignLeaves'])->name('admin.employees.assignLeaves');
        Route::post('/employees/{id}/assign-leaves', [EmployeeController::class, 'assignLeaveBalance'])->name('admin.employees.assignLeaveBalance');
    });
 
    // Interviews
    Route::middleware('permission:view-employee|view-interview')->group(function () {
        Route::get('/interviews/data', [\App\Http\Controllers\Admin\InterviewController::class, 'data'])->name('admin.interviews.data');
        Route::get('/interviews/check-cnic', [\App\Http\Controllers\Admin\InterviewController::class, 'checkCnic'])->name('admin.interviews.check-cnic');
        Route::post('/interviews/{id}/onboard', [\App\Http\Controllers\Admin\InterviewController::class, 'onboard'])->name('admin.interviews.onboard');
        Route::post('/interviews/{id}/revert-onboarding', [\App\Http\Controllers\Admin\InterviewController::class, 'revertOnboarding'])->name('admin.interviews.revertOnboarding');
        Route::post('/interviews/{id}/change-status', [\App\Http\Controllers\Admin\InterviewController::class, 'changeStatus'])->name('admin.interviews.changeStatus');
        Route::get('/interviews/cv/{filename}', [\App\Http\Controllers\Admin\InterviewController::class, 'viewCv'])->name('admin.interviews.view-cv');
        Route::get('/interviews/{id}/cnic/{side}', [\App\Http\Controllers\Admin\InterviewController::class, 'viewCnicDoc'])->name('admin.interviews.view-cnic-doc');
        Route::post('/interviews/{id}/conduct-round', [\App\Http\Controllers\Admin\InterviewController::class, 'conductRound'])->name('admin.interviews.conductRound');
        Route::post('/interviews/{id}/reassign', [\App\Http\Controllers\Admin\InterviewController::class, 'reassign'])->name('admin.interviews.reassign');
        Route::post('/interviews/{id}/update-team-lead', [\App\Http\Controllers\Admin\InterviewController::class, 'updateTeamLead'])->name('admin.interviews.updateTeamLead');
        Route::post('/interviews/{id}/update-training-status', [\App\Http\Controllers\Admin\InterviewController::class, 'updateTrainingStatus'])->name('admin.interviews.updateTrainingStatus');
        Route::resource('interviews', \App\Http\Controllers\Admin\InterviewController::class)->names('admin.interviews');
    });

    // Job Postings
    Route::middleware('permission:manage-job-postings')->group(function () {
        Route::get('/job-postings/data', [\App\Http\Controllers\Admin\JobPostingController::class, 'data'])->name('admin.job-postings.data');
        Route::resource('job-postings', \App\Http\Controllers\Admin\JobPostingController::class)->names('admin.job-postings');
    });

    // Teams
    Route::middleware('permission:view-team')->group(function () {
        Route::get('/teams/data', [\App\Http\Controllers\Admin\TeamController::class, 'teamData'])->name('admin.teams.data');
        Route::get('/teams/{id}/members', [\App\Http\Controllers\Admin\TeamController::class, 'getMembers'])->name('admin.teams.members');
        Route::post('/teams/{id}/members', [\App\Http\Controllers\Admin\TeamController::class, 'updateMembers'])->name('admin.teams.members.update');
        Route::resource('teams', \App\Http\Controllers\Admin\TeamController::class)->names('admin.teams');
    });

    // Departments
    Route::middleware('permission:view-department')->group(function () {
        Route::get('/departments/data', [\App\Http\Controllers\Admin\DepartmentController::class, 'data'])->name('admin.departments.data');
        Route::resource('departments', \App\Http\Controllers\Admin\DepartmentController::class)->names('admin.departments');
    });

    // Branches
    Route::middleware('auth')->group(function () {
        Route::get('/branches/data', [\App\Http\Controllers\Admin\BranchController::class, 'data'])->name('admin.branches.data');
        Route::post('/branches/set-context', [\App\Http\Controllers\Admin\BranchController::class, 'setContext'])->name('admin.branches.set_context');
        Route::resource('branches', \App\Http\Controllers\Admin\BranchController::class)->names('admin.branches');
    });

    // Roles & Permissions
    Route::middleware('permission:manage-settings')->group(function () {
        Route::get('/roles/data', [\App\Http\Controllers\Admin\RoleController::class, 'data'])->name('admin.roles.data');
        Route::get('/roles/{role}/permissions', [\App\Http\Controllers\Admin\RoleController::class, 'permissions'])->name('admin.roles.permissions');
        Route::post('/roles/{role}/permissions', [\App\Http\Controllers\Admin\RoleController::class, 'updatePermissions'])->name('admin.roles.update_permissions');
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class)->names('admin.roles');

        // Permissions Management
        Route::get('/permissions/data', [\App\Http\Controllers\Admin\PermissionController::class, 'data'])->name('admin.permissions.data');
        Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class)->names('admin.permissions');

        // Admin/User Management
        Route::get('/user-management', [\App\Http\Controllers\Admin\AdminManageController::class, 'index'])->name('admin.user_management.index');
        Route::get('/user-management/data', [\App\Http\Controllers\Admin\AdminManageController::class, 'data'])->name('admin.user_management.data');
        Route::post('/user-management/promote/{user}', [\App\Http\Controllers\Admin\AdminManageController::class, 'promote'])->name('admin.user_management.promote');
        Route::post('/user-management/update-role/{user}', [\App\Http\Controllers\Admin\AdminManageController::class, 'updateRole'])->name('admin.user_management.update_role');
        Route::post('/user-management/update-password/{user}', [\App\Http\Controllers\Admin\AdminManageController::class, 'updatePassword'])->name('admin.user_management.update_password');
        Route::delete('/user-management/terminate/{user}', [\App\Http\Controllers\Admin\AdminManageController::class, 'destroy'])->name('admin.user_management.destroy');
        Route::get('/user-management/roles', [\App\Http\Controllers\Admin\AdminManageController::class, 'getRoles'])->name('admin.user_management.roles');
        Route::post('/user-management/create-admin', [\App\Http\Controllers\Admin\AdminManageController::class, 'storeAdmin'])->name('admin.user_management.store_admin');
        Route::get('/user-management/user-teams/{user}', [\App\Http\Controllers\Admin\AdminManageController::class, 'getUserTeams'])->name('admin.user_management.user_teams');
    });

    // Shifts
    Route::middleware('permission:view-shift')->group(function () {
        Route::resource('shift', ShiftController::class)->names('admin.shifts');
        Route::get('/shift_data/{id}', [ShiftController::class, 'show'])->name('shift_data');
        Route::post('/shift/{id}/toggle-status', [ShiftController::class, 'toggleStatus'])->name('admin.shifts.toggle-status');
    });

    // Attendance
    Route::middleware('permission:view-attendance')->group(function () {
        Route::get('/attendance/all/{year?}', [AdminAttendanceController::class, 'getAttendanceForAllEmployeesWithMidnightShiftHandling']);
        Route::get('/attendance/employee/{empId}/{year?}', [AdminAttendanceController::class, 'getAttendanceForSpecificEmployeeWithMidnightShiftHandling']);
        Route::get('/attendance/logs', [AdminAttendanceController::class, 'logs'])->name('admin.attendance.logs');
        Route::get('/attendance/logs/data', [AdminAttendanceController::class, 'logsData'])->name('admin.attendance.logs.data');
        Route::get('/attendance/details/{id}', [AdminAttendanceController::class, 'viewDetails'])->name('admin.attendance.details');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
        Route::get('/attendance-sheet', [MonthlyAttendanceController::class, 'monthlyView'])->name('admin.attendance.monthly');
        Route::match(['get', 'post'], '/attendance-sheet/data', [MonthlyAttendanceController::class, 'monthlyData'])->name('admin.attendance.monthly.data');
        Route::get('/attendance-sheet/export', [MonthlyAttendanceController::class, 'exportMonthly'])->name('admin.attendance.monthly.export');
    });

    Route::middleware('permission:manage-attendance')->group(function () {
        Route::get('/attendance/manual/create', [\App\Http\Controllers\Admin\ManualAttendanceController::class, 'create'])->name('admin.attendance.manual.create');
        Route::get('/attendance/manual/edit/{id}', [\App\Http\Controllers\Admin\ManualAttendanceController::class, 'edit'])->name('admin.attendance.manual.edit');
        Route::post('/attendance/manual/store', [\App\Http\Controllers\Admin\ManualAttendanceController::class, 'store'])->name('admin.attendance.manual.store');
        Route::post('/attendance/manual/update/{id}', [\App\Http\Controllers\Admin\ManualAttendanceController::class, 'update'])->name('admin.attendance.manual.update');
        Route::get('/employee/{id}/shift-on-date', [\App\Http\Controllers\Admin\ManualAttendanceController::class, 'getShiftOnDate']);

        Route::get('/attendance/import/modal', [\App\Http\Controllers\Admin\ImportAttendanceController::class, 'modal'])->name('admin.attendance.import.modal');
        Route::get('/attendance/import/template', [\App\Http\Controllers\Admin\ImportAttendanceController::class, 'template'])->name('admin.attendance.import.template');
        Route::post('/attendance/import/store', [\App\Http\Controllers\Admin\ImportAttendanceController::class, 'store'])->name('admin.attendance.import.store');
    });

    Route::middleware('permission:view-half-days')->group(function () {
        Route::get('/half-days', [HalfDayController::class, 'index'])->name('admin.half_days.index');
        Route::get('/half-days/data', [HalfDayController::class, 'data'])->name('admin.half_days.data');
    });

    // Late Arrivals
    Route::middleware('permission:view-late-arrivals')->group(function () {
        Route::resource('late-arrivals', LateArrivalController::class)->except(['show', 'create', 'store'])->names('admin.late_arrivals');
        Route::get('/late-arrivals/trash', [LateArrivalController::class, 'trash'])->name('admin.late_arrivals.trash');
        Route::post('/late-arrivals/{id}/restore', [LateArrivalController::class, 'restore'])->name('admin.late_arrivals.restore');
    });

    // Breaks & Requests
    Route::middleware('permission:view-attendance')->group(function () {
        Route::get('/breaks', [AdminBreakController::class, 'breakRequests'])->name('admin.breaks.index');
        Route::patch('/breaks/{id}/approve', [AdminBreakController::class, 'approve'])->name('admin.breaks.approve');
        Route::patch('/breaks/{id}/reject', [AdminBreakController::class, 'reject'])->name('admin.breaks.reject');
        Route::post('/breaks/manual', [AdminBreakController::class, 'storeManual'])->name('admin.breaks.storeManual');
        Route::post('/breaks/instant-start', [AdminBreakController::class, 'instantStart'])->name('admin.breaks.instantStart');
        Route::post('/breaks/instant-end', [AdminBreakController::class, 'instantEnd'])->name('admin.breaks.instantEnd');
    });

    // Settings
    Route::middleware('permission:manage-settings')->group(function () {
        Route::get('/settings', [AppSettingsController::class, 'index'])->name('admin.settings');
        Route::post('/settings', [AppSettingsController::class, 'update'])->name('admin.settings.update');
    });

    Route::get('/department/{id}/employees', [DepartmentController::class, 'getEmployees']);

    // Leaves
    Route::middleware('permission:view-leaves')->group(function () {
        Route::get('leaves', [LeaveController::class, 'index'])->name('admin.leaves.index');
        Route::get('leaves/calculate-days', [LeaveController::class, 'calculateDays'])->name('admin.leaves.calculate');
        Route::get('leaves/data', [LeaveController::class, 'data'])->name('admin.leaves.data');
        Route::get('leaves/{leave}', [LeaveController::class, 'show'])->name('admin.leaves.show');
        Route::post('leaves', [LeaveController::class, 'store'])->name('admin.leaves.store');
        Route::post('leaves/bulk-update', [LeaveController::class, 'bulkUpdate'])->name('admin.leaves.bulk_update');
        Route::post('leaves/{id}/approve', [LeaveController::class, 'approve'])->name('admin.leaves.approve');
        Route::post('leaves/{id}/reject', [LeaveController::class, 'reject'])->name('admin.leaves.reject');
        Route::post('leaves/{id}/update-decision', [LeaveController::class, 'updateDecision'])->name('admin.leaves.update_decision');
    });

    // Holidays
    Route::middleware('permission:view-holidays')->group(function () {
        Route::get('holidays', [HolidayController::class, 'index'])->name('admin.holidays.index');
        Route::get('holidays/data', [HolidayController::class, 'data'])->name('admin.holidays.data');
    });
    Route::middleware('permission:create-holiday')->group(function () {
        Route::post('holidays', [HolidayController::class, 'store'])->name('admin.holidays.store');
    });
    Route::middleware('permission:edit-holiday')->group(function () {
        Route::put('holidays/{holiday}', [HolidayController::class, 'update'])->name('admin.holidays.update');
    });
    Route::middleware('permission:delete-holiday')->group(function () {
        Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('admin.holidays.destroy');
    });

    // Leave Adjustments
    Route::middleware('permission:view-leave-adjustments')->group(function () {
        Route::get('/leave-adjustments', [\App\Http\Controllers\Admin\LeaveAdjustmentController::class, 'index'])->name('admin.leave_adjustments.index');
        Route::get('/leave-adjustments/data', [\App\Http\Controllers\Admin\LeaveAdjustmentController::class, 'data'])->name('admin.leave_adjustments.data');
    });

    // Leave Balance Sheet — every employee, every leave type, for a chosen year
    Route::middleware('permission:manage-leave-balances')->group(function () {
        Route::get('/leave-balances', [\App\Http\Controllers\Admin\LeaveBalanceReportController::class, 'index'])->name('admin.leave_balances.index');
        Route::get('/leave-balances/data', [\App\Http\Controllers\Admin\LeaveBalanceReportController::class, 'data'])->name('admin.leave_balances.data');
        Route::get('/leave-balances/export', [\App\Http\Controllers\Admin\LeaveBalanceReportController::class, 'export'])->name('admin.leave_balances.export');
    });

    // Compensatory Leave (earned by working public holidays)
    Route::middleware('permission:view-compensatory-leaves')->group(function () {
        Route::get('/compensatory-leaves', [\App\Http\Controllers\Admin\CompensatoryLeaveController::class, 'index'])->name('admin.compensatory_leaves.index');
        Route::get('/compensatory-leaves/data', [\App\Http\Controllers\Admin\CompensatoryLeaveController::class, 'data'])->name('admin.compensatory_leaves.data');
    });

    Route::middleware('permission:manage-compensatory-leaves')->group(function () {
        Route::post('/compensatory-leaves', [\App\Http\Controllers\Admin\CompensatoryLeaveController::class, 'store'])->name('admin.compensatory_leaves.store');
        Route::post('/compensatory-leaves/{id}/approve', [\App\Http\Controllers\Admin\CompensatoryLeaveController::class, 'approve'])->name('admin.compensatory_leaves.approve');
        Route::post('/compensatory-leaves/{id}/reject', [\App\Http\Controllers\Admin\CompensatoryLeaveController::class, 'reject'])->name('admin.compensatory_leaves.reject');
    });

    // Year-end leave encashment
    Route::middleware('permission:view-leave-cashouts')->group(function () {
        Route::get('/leave-cashouts', [\App\Http\Controllers\Admin\LeaveCashoutController::class, 'index'])->name('admin.leave_cashouts.index');
        Route::get('/leave-cashouts/data', [\App\Http\Controllers\Admin\LeaveCashoutController::class, 'data'])->name('admin.leave_cashouts.data');
        Route::get('/leave-cashouts/balances', [\App\Http\Controllers\Admin\LeaveCashoutController::class, 'balances'])->name('admin.leave_cashouts.balances');
    });

    Route::middleware('permission:manage-leave-cashouts')->group(function () {
        Route::post('/leave-cashouts', [\App\Http\Controllers\Admin\LeaveCashoutController::class, 'store'])->name('admin.leave_cashouts.store');
        Route::post('/leave-cashouts/{id}/cancel', [\App\Http\Controllers\Admin\LeaveCashoutController::class, 'cancel'])->name('admin.leave_cashouts.cancel');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');

    // Payroll Management
    Route::middleware('permission:view-payroll|generate-payroll')->prefix('payroll')->name('admin.payroll.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('index')->middleware('permission:view-payroll');
        Route::get('/generate', [\App\Http\Controllers\Admin\PayrollController::class, 'create'])->name('generate')->middleware('permission:generate-payroll');
        Route::post('/generate', [\App\Http\Controllers\Admin\PayrollController::class, 'store'])->name('store')->middleware('permission:generate-payroll');
        Route::get('/{id}', [\App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('show')->middleware('permission:view-payroll');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\PayrollController::class, 'destroy'])->name('destroy')->middleware('permission:generate-payroll');
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('approve')->middleware('permission:approve-payroll');
        Route::get('/payslip/{itemId}', [\App\Http\Controllers\Admin\PayrollController::class, 'payslip'])->name('payslip')->middleware('permission:view-payroll');
    });

    // Salary Structures & Policies
    Route::resource('salary-structures', \App\Http\Controllers\Admin\SalaryStructureController::class)->names('admin.salary_structures');
    Route::resource('payroll-policies', \App\Http\Controllers\Admin\PayrollPolicyController::class)->names('admin.payroll_policies');
});


// ✅ PROFILE ROUTES
Route::middleware('auth')->group(function () {
    // Activity Log routes 
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity_logs.index');
    // for broadcasting 
    Route::post('/break/update', [BreakController::class, 'updateBreak'])->name('break.update');
});


Route::middleware(['auth', 'is_employee'])
    ->prefix('employee')
    ->name('employee.') // 👈 Add this line to prefix all names
    ->group(function () {
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::post('shift-over', [EmployeeAttendanceController::class, 'markShiftOver'])->name('shift.over');

        Route::get('breaks', [EmployeeBreakController::class, 'index'])->name('break.index');
        // Route::post('breaks/start', [EmployeeBreakController::class, 'startBreak'])->name('break.start');
        // Route::post('breaks/end/{id}', [EmployeeBreakController::class, 'endBreak'])->name('break.end');
        Route::get('breaks/data', [EmployeeBreakController::class, 'data'])->name('breaks.data');
        Route::post('breaks/start', [EmployeeBreakController::class, 'startBreak'])->name('break.start');
        Route::post('breaks/end/{id}', [EmployeeBreakController::class, 'endBreak'])->name('break.end');
        // Logs & Details
        Route::get('/attendance/logs', [EmployeeAttendanceController::class, 'logs'])->name('attendance.logs');
        Route::get('/attendance/logs/data', [EmployeeAttendanceController::class, 'logsData'])->name('attendance.logs.data');
        Route::get('/attendance/details/{id}', [EmployeeAttendanceController::class, 'viewDetails'])->name('attendance.details');

        Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance.index');

        Route::get('checkin', [EmployeeCheckInController::class, 'show'])->name('checkin');
        Route::post('checkin', [EmployeeCheckInController::class, 'store'])->name('checkin.submit');

        // pusher 
    
        // web.php
        Route::get('breaks/active', [EmployeeBreakController::class, 'getActiveBreak'])->name('break.active');
        Route::get('breaks/{id}/duration', [EmployeeBreakController::class, 'getBreakDuration'])->name('break.duration');
        Route::get('breaks/live', [EmployeeBreakController::class, 'getBreakLive'])->name('break.live');

        // Timesheet (Monthly Grid)
        Route::get('/timesheet', [EmployeeAttendanceController::class, 'timesheet'])->name('timesheet');
        Route::match(['get', 'post'], '/timesheet/data', [EmployeeAttendanceController::class, 'timesheetData'])->name('timesheet.data');

        // Monthly Report
        Route::get('/report/{month?}/{year?}', [EmployeeAttendanceController::class, 'report'])->name('report');

        // Leave Requests
        Route::get('/leave/request', [\App\Http\Controllers\Employee\LeaveController::class, 'index'])->name('leave.index');
        Route::get('/leave/data', [\App\Http\Controllers\Employee\LeaveController::class, 'data'])->name('leave.data');
        Route::post('/leave/store', [\App\Http\Controllers\Employee\LeaveController::class, 'store'])->name('leave.store');

        // Late History
        Route::get('/attendance/late-history', [EmployeeAttendanceController::class, 'lateHistory'])->name('attendance.late_history');
        Route::get('/attendance/late-history/data', [EmployeeAttendanceController::class, 'lateHistoryData'])->name('attendance.late_history.data');

        // Profile
        Route::get('/profile', [App\Http\Controllers\Employee\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [App\Http\Controllers\Employee\ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/cover-photo', [App\Http\Controllers\Employee\ProfileController::class, 'updateCoverPhoto'])->name('profile.cover.update');
        Route::patch('/profile/cover-photo/position', [App\Http\Controllers\Employee\ProfileController::class, 'updateCoverPosition'])->name('profile.cover.position');
        Route::delete('/profile/cover-photo', [App\Http\Controllers\Employee\ProfileController::class, 'removeCoverPhoto'])->name('profile.cover.remove');
    });


// ✅ LANDING ROUTE
Route::get('/', function () {
    return redirect()->route('login');
});

// ✅ Dashboard fallback based on role
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('dashboard');

// ✅ PUBLIC INTERVIEW APPLICATION
Route::get('/apply', [\App\Http\Controllers\Admin\InterviewController::class, 'publicForm'])->name('interviews.apply');
Route::post('/apply', [\App\Http\Controllers\Admin\InterviewController::class, 'storePublic'])
    ->middleware('throttle:3,1') // Rate limiting: 3 attempts per minute
    ->name('interviews.storePublic');

use App\Models\Employee;

use App\Models\LeaveType;

// ✅ AJAX MODAL ROUTES (Secured)
Route::middleware(['auth', 'permission:view-employee'])->get('/ajax_modal_contents/{page}', function ($page) {
    $id = request()->get('id');
    $viewData = [];

    if ($page === 'edit_employee' || $page === 'view_employee') {
        if ($page === 'edit_employee' && !auth()->user()->hasAnyPermission(['manage-employees', 'edit-employee']))
            abort(403);
        $employee = \App\Models\Employee::with(['shifts', 'currentShiftAssignment.shift', 'salaryStructure'])->findOrFail($id);
        $viewData['data'] = $employee;
        $viewData['schedules'] = \App\Models\Shift::all();
        $viewData['branches'] = \App\Models\Branch::where('is_active', true)->get();
        $viewData['salary_structures'] = \App\Models\SalaryStructure::accessible()->get();
    } elseif ($page === 'add_employee') {
        if (!auth()->user()->hasAnyPermission(['manage-employees', 'create-employee']))
            abort(403);
        $viewData['schedules'] = \App\Models\Shift::all();
        $viewData['branches'] = \App\Models\Branch::where('is_active', true)->get();
        $viewData['salary_structures'] = \App\Models\SalaryStructure::accessible()->get();
        $viewData['roles'] = \App\Models\Role::all();
        $viewData['teams'] = \App\Models\Team::all();
    } elseif ($page === 'manage_leaves') {
        if (!auth()->user()->hasAnyPermission(['manage-leave-balances', 'manage-employees', 'edit-employee']))
            abort(403);
        $employee = \App\Models\Employee::with('leaveBalances')->findOrFail($id);
        $viewData['data'] = $employee;
        $viewData['leaveTypes'] = \App\Models\LeaveType::where('is_active', true)->get();
    }

    return view('ajax.' . $page)->with($viewData);
})->name('ajax_modal_contents');

// Leave Balance Routes
Route::middleware(['auth', 'permission:manage-leave-balances|manage-employees|edit-employee'])->prefix('admin')->group(function () {
    Route::post('/employees/leave-balance/store', [EmployeeController::class, 'saveLeaveBalance'])->name('admin.employees.leave_balance.store');
    Route::put('/employees/leave-balance/{id}', [EmployeeController::class, 'updateLeaveBalance'])->name('admin.employees.leave_balance.update');
    Route::delete('/employees/leave-balance/{id}', [EmployeeController::class, 'deleteLeaveBalance'])->name('admin.employees.leave_balance.delete');
});


require __DIR__ . '/auth.php';
require __DIR__ . '/performance.php';

