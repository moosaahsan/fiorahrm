<?php

namespace Database\Seeders;

use App\Models\AppResponse;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds predictable admin-dashboard checkout test scenarios.
 *
 * Run: php artisan db:seed --class=AdminCheckoutTestSeeder
 */
class AdminCheckoutTestSeeder extends Seeder
{
    private const EMAIL_PREFIX = 'checkout-test-';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('AdminCheckoutTestSeeder skipped in production.');

            return;
        }

        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = Carbon::now($timezone);

        $reference = Employee::whereNull('resign_date')
            ->whereNotNull('team_id')
            ->whereNotNull('branch_id')
            ->first();

        if (!$reference) {
            $this->command->error('No active employee with team/branch found. Seed base employees first.');

            return;
        }

        $shift = Shift::where('crosses_midnight', false)->first() ?? Shift::first();
        if (!$shift) {
            $this->command->error('No shift found. Run ShiftSeeder first.');

            return;
        }

        $scenarios = [
            [
                'suffix' => 'on-break',
                'name' => 'Checkout Test (On Break)',
                'position' => 'QA — Active Break',
                'checked_in' => true,
                'on_break' => true,
            ],
            [
                'suffix' => 'present',
                'name' => 'Checkout Test (Present)',
                'position' => 'QA — Checked In',
                'checked_in' => true,
                'on_break' => false,
            ],
            [
                'suffix' => 'absent',
                'name' => 'Checkout Test (Absent)',
                'position' => 'QA — Not Checked In',
                'checked_in' => false,
                'on_break' => false,
            ],
        ];

        $this->command->info('Seeding admin checkout test data...');
        $this->command->newLine();

        foreach ($scenarios as $scenario) {
            $employee = $this->upsertTestEmployee($scenario, $reference);
            $employeeShift = $this->ensureShiftAssignment($employee, $shift, $now);
            $shiftDate = web_resolve_shift_date($employeeShift);

            $this->resetEmployeeDayState($employee->id, $shiftDate);

            if ($scenario['checked_in']) {
                $attendance = $this->createCheckedInAttendance(
                    $employee,
                    $shift,
                    $shiftDate,
                    $now,
                    $scenario['suffix'] === 'on-break' ? 15 : 0
                );

                if ($scenario['on_break']) {
                    $this->createActiveBreak($employee, $attendance, $shiftDate, $now);
                }

                $this->createHeartbeat($employee->id, $shiftDate, $now, false);
            }

            $this->command->line(sprintf(
                '  ✓ %-32s  emp_id=%d  shift_date=%s  %s',
                $scenario['name'],
                $employee->id,
                $shiftDate,
                $scenario['checked_in']
                    ? ($scenario['on_break'] ? 'CHECKED IN + ON BREAK' : 'CHECKED IN')
                    : 'NOT CHECKED IN'
            ));
        }

        $this->command->newLine();
        $this->command->info('Done. Open /admin/dashboard (live mode) and look for "Checkout Test" rows.');
        $this->command->info('Checkout button should appear only on checked-in rows.');
        $this->command->info('Test login (if needed): checkout-test-on-break@test.local / password123');
    }

    private function upsertTestEmployee(array $scenario, Employee $reference): Employee
    {
        $email = self::EMAIL_PREFIX . $scenario['suffix'] . '@test.local';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $scenario['name'],
                'password' => Hash::make('password123'),
            ]
        );

        if (!$user->hasRole('employee')) {
            $user->assignRole('employee');
        }

        $employee = Employee::updateOrCreate(
            ['email' => $email],
            [
                'user_id' => $user->id,
                'name' => $scenario['name'],
                'position' => $scenario['position'],
                'joining_date' => now()->subMonths(6)->toDateString(),
                'probation' => '3',
                'contact_no' => '0300' . random_int(1000000, 9999999),
                'status' => 1,
                'tracking' => 1,
                'team_id' => $reference->team_id,
                'branch_id' => $reference->branch_id,
                'resign_date' => null,
            ]
        );

        return $employee;
    }

    private function ensureShiftAssignment(Employee $employee, Shift $shift, Carbon $now): EmployeeShift
    {
        EmployeeShift::where('emp_id', $employee->id)->delete();

        return EmployeeShift::create([
            'emp_id' => $employee->id,
            'shift_id' => $shift->id,
            'assigned_at' => $now->copy()->subDay(),
        ]);
    }

    private function resetEmployeeDayState(int $employeeId, string $shiftDate): void
    {
        EmployeeBreak::where('emp_id', $employeeId)
            ->where(function ($query) use ($shiftDate) {
                $query->where('shift_date', $shiftDate)
                    ->orWhereIn('status', ['On Break', 'On break', 'Ongoing']);
            })
            ->delete();

        Attendance::where('emp_id', $employeeId)
            ->where('shift_date', $shiftDate)
            ->delete();

        AppResponse::where('emp_id', $employeeId)
            ->where('shift_date', $shiftDate)
            ->delete();
    }

    private function createCheckedInAttendance(
        Employee $employee,
        Shift $shift,
        string $shiftDate,
        Carbon $now,
        int $lateMinutes = 0
    ): Attendance {
        $checkIn = Carbon::parse("{$shiftDate} {$shift->start_time}", $now->timezoneName)
            ->addMinutes($lateMinutes);

        if ($checkIn->gt($now)) {
            $checkIn = $now->copy()->subHours(2);
        }

        return Attendance::create([
            'emp_id' => $employee->id,
            'shift_id' => $shift->id,
            'shift_date' => $shiftDate,
            'branch_id' => $employee->branch_id,
            'check_in' => $checkIn,
            'check_out' => null,
            'shift_over_at' => null,
            'status' => $lateMinutes > 0 ? 'Present' : 'Present',
            'late_duration' => $lateMinutes,
            'is_manual' => 0,
        ]);
    }

    private function createActiveBreak(
        Employee $employee,
        Attendance $attendance,
        string $shiftDate,
        Carbon $now
    ): EmployeeBreak {
        return EmployeeBreak::create([
            'emp_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'shift_date' => $shiftDate,
            'branch_id' => $employee->branch_id,
            'start_time' => $now->copy()->subMinutes(12),
            'end_time' => null,
            'type' => 'General',
            'status' => 'On Break',
            'reason' => 'Checkout test — active break',
            'spent_minutes' => 0,
            'remaining_minutes' => 0,
            'exceeded_minutes' => 0,
        ]);
    }

    private function createHeartbeat(int $employeeId, string $shiftDate, Carbon $now, bool $checkedOut): void
    {
        $payload = [
            'host_name' => 'TEST-DESKTOP',
            'response_update' => $now->copy()->utc(),
            'app_version' => '1.2.3',
        ];

        if (Schema::hasColumn('app_response', 'is_checked_out')) {
            $payload['is_checked_out'] = $checkedOut;
        }

        AppResponse::updateOrCreate(
            [
                'emp_id' => $employeeId,
                'shift_date' => $shiftDate,
            ],
            $payload
        );
    }
}
