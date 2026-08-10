<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\EvaluateHalfDays;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\MarkAbsentees;
use App\Console\Commands\ReactivateSuspendedEmployees;

// Lightweight jobs — staggered to avoid CPU spikes on shared hosting.
// withoutOverlapping prevents stacked runs if a job exceeds its interval.

Schedule::command(EvaluateHalfDays::class)
    ->dailyAt('18:00')
    ->withoutOverlapping(30)
    ->before(function () {
        Log::info('Starting EvaluateHalfDays', ['time' => now()->toDateTimeString()]);
    })
    ->after(function () {
        Log::info('Finished EvaluateHalfDays', ['time' => now()->toDateTimeString()]);
    })
    ->onFailure(function () {
        Log::error('EvaluateHalfDays failed', ['time' => now()->toDateTimeString()]);
    });

// Heavy full-employee scan — hourly only (was everyMinute, caused server overload).
Schedule::command(MarkAbsentees::class)
    ->hourly()
    ->withoutOverlapping(45)
    ->before(function () {
        Log::info('Starting MarkAbsentees', ['time' => now()->toDateTimeString()]);
    })
    ->after(function () {
        Log::info('Finished MarkAbsentees', ['time' => now()->toDateTimeString()]);
    })
    ->onFailure(function () {
        Log::error('MarkAbsentees failed', ['time' => now()->toDateTimeString()]);
    });

Schedule::command('leaves:process-monthly-policies')
    ->monthlyOn(1, '00:05')
    ->withoutOverlapping(60)
    ->before(function () {
        Log::info('Starting ProcessMonthlyLeavePolicies', ['time' => now()->toDateTimeString()]);
    })
    ->after(function () {
        Log::info('Finished ProcessMonthlyLeavePolicies', ['time' => now()->toDateTimeString()]);
    })
    ->onFailure(function () {
        Log::error('ProcessMonthlyLeavePolicies FAILED', ['time' => now()->toDateTimeString()]);
    });

// Catches holiday work that the AttendanceObserver missed — e.g. a holiday
// configured after the attendance was already entered.
Schedule::command('cpl:scan')
    ->dailyAt('00:30')
    ->withoutOverlapping(30)
    ->onFailure(function () {
        Log::error('ScanCompensatoryLeave failed', ['time' => now()->toDateTimeString()]);
    });

// Unlocks entitlement once the eligibility waiting period completes, and rolls
// balances over into a new calendar year. Idempotent, so a daily run is cheap.
Schedule::command('leaves:sync-balances')
    ->dailyAt('00:20')
    ->withoutOverlapping(30)
    ->onFailure(function () {
        Log::error('SyncLeaveBalances failed', ['time' => now()->toDateTimeString()]);
    });

Schedule::command(ReactivateSuspendedEmployees::class)
    ->dailyAt('00:10')
    ->withoutOverlapping(30);
