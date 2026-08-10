<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use App\Models\EmployeeShift;
use App\Models\Attendance;
use App\Observers\AttendanceObserver;
use App\Observers\EmployeeObserver;
use App\Observers\LeaveObserver;
use App\Models\Leave;
use App\Models\LateArrival;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment() !== 'local') {
            URL::forceScheme('https');
        }

        /* ───── Time‑zone handling ─────────────────────────────────── */
        // 1. Pull the admin‑defined zone from app_settings()
        $tz = app_settings('app_timezone') ?? config('app.timezone', 'Asia/Karachi');

        // 2. Tell Laravel + PHP to use it
        Config::set('app.timezone', $tz);      // for framework helpers
        date_default_timezone_set($tz);        // for native PHP + Carbon::now()

        // 3. (Optional) Carbon locale
        Carbon::setLocale('en');               // keep English month/day names

        /* ───── Blade components ───────────────────────────────────── */
        Blade::component('components.datatable', 'datatable');
        Blade::component('components.filter-form', 'filter-form');
        Blade::component('components.pagination', 'pagination');
        Employee::observe(EmployeeObserver::class);
        Leave::observe(LeaveObserver::class);
        Attendance::observe(AttendanceObserver::class);

        /* ───── Share break requests count with sidebar ─────────────── */
        View::composer('admin.layouts.sidebar', function ($view) {
            $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
            $today = Carbon::today($timezone)->toDateString();
            $yesterday = Carbon::today($timezone)->subDay()->toDateString();

            $breakRequestsCount = EmployeeBreak::accessible()
                ->where('type', 'Official')
                ->whereNotNull('reason')
                ->where('status', 'Pending')
                ->whereIn('shift_date', [$today, $yesterday])
                ->count();

            $pendingLeavesCount = Leave::accessible()
                ->where('status', 'Pending')
                ->whereDate('created_at', '>=', Carbon::today($timezone)->subDays(90))
                ->count();

            $lateArrivalsCount = LateArrival::accessible()
                ->whereDate('date', $today)
                ->count();

            $pendingCplCount = Schema::hasTable('compensatory_leaves')
                ? \App\Models\CompensatoryLeave::accessible()->pending()->count()
                : 0;

            $view->with([
                'breakRequestsCount' => $breakRequestsCount,
                'pendingLeavesCount' => $pendingLeavesCount,
                'lateArrivalsCount' => $lateArrivalsCount,
                'pendingCplCount' => $pendingCplCount,
            ]);
        });

        /* ───── Permissions & Security ─────────────────────────────── */
        // We removed the global Super-Admin bypass to enforce strict granular permissions.
        // Ensure your Admin role has explicit permissions assigned via the Access Control panel.
    }

    /**
     * Helper to resolve shift date for sidebar
     */
    private function resolveShiftDateForSidebar($employeeShift, $timezone)
    {
        if (!$employeeShift || !$employeeShift->shift) {
            return Carbon::today($timezone)->toDateString();
        }

        $shift = $employeeShift->shift;
        $now = Carbon::now($timezone);
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        $bufferMinutes = 180;

        $startToday = Carbon::parse("{$today->toDateString()} {$shift->start_time}", $timezone);
        $endToday = Carbon::parse("{$today->toDateString()} {$shift->end_time}", $timezone)->addMinutes($bufferMinutes);
        if ($shift->crosses_midnight && $endToday->lt($startToday)) {
            $endToday->addDay();
        }

        $startYesterday = Carbon::parse("{$yesterday->toDateString()} {$shift->start_time}", $timezone);
        $endYesterday = Carbon::parse("{$yesterday->toDateString()} {$shift->end_time}", $timezone)->addMinutes($bufferMinutes);
        if ($shift->crosses_midnight && $endYesterday->lt($startYesterday)) {
            $endYesterday->addDay();
        }

        if ($now->between($startYesterday, $endYesterday)) {
            return $yesterday->toDateString();
        }

        if ($now->between($startToday, $endToday)) {
            return $today->toDateString();
        }

        return $today->toDateString();
    }
}
