<?php

namespace App\Console\Commands;

use App\Models\CompanyOffDay;
use App\Models\CompensatoryLeave;
use App\Models\Employee;
use App\Models\EmployeeSetting;
use App\Models\HalfDay;
use App\Models\LateArrival;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Role;
use App\Models\User;
use App\Services\CompensatoryLeaveService;
use App\Services\LeaveService;
use App\Services\WorkingDayService;
use App\Traits\HandlesManualAttendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds (or removes) a realistic dummy dataset for demoing the leave,
 * attendance, holiday and compensatory-leave workflow to HR.
 *
 * Deliberately reuses the exact code paths a real HR action goes through
 * rather than hand-rolling the business rules a second time:
 *   - Attendance rows go through the same Attendance model + AttendanceObserver
 *     that a check-in or a manual entry would, so compensatory leave is earned
 *     automatically on a worked holiday, exactly as it would for a real shift.
 *   - Late / half-day / unpaid-absence records are created via
 *     HandlesManualAttendance — the same trait ManualAttendanceController uses
 *     — so a dummy "Late" day is indistinguishable from one HR entered by hand.
 *   - Leave records are plain Leave::create() calls with status=Approved,
 *     which LeaveObserver picks up and deducts from the balance automatically.
 *
 * Every dummy employee's email ends in @dummy.fiorahotel.test and their name is
 * prefixed "[TEST] " — `--remove` finds everything by that email domain, so
 * cleanup can never touch a real employee no matter what else changed.
 */
class SeedHotelDemoData extends Command
{
    use HandlesManualAttendance;

    protected $signature = 'demo:hotel-data {--remove : Remove previously seeded demo data instead of creating it}';

    protected $description = 'Seed a realistic dummy leave/attendance/holiday/CPL dataset for the hotel demo, or remove it with --remove';

    protected const EMAIL_DOMAIN = '@dummy.fiorahotel.test';
    protected const NAME_PREFIX = '[TEST] ';
    protected const HOLIDAY_PREFIX = '[TEST] ';

    protected int $adminId;
    protected \App\Models\Shift $shift;
    protected array $employees = [];

    public function handle(): int
    {
        if ($this->option('remove')) {
            return $this->remove();
        }

        if (Employee::withTrashed()->where('email', 'like', '%' . self::EMAIL_DOMAIN)->exists()) {
            $this->error('Demo data already exists. Run with --remove first if you want to regenerate it.');

            return self::FAILURE;
        }

        $admin = User::where('email', 'admin@fiorahotel.com')->first() ?? User::first();

        if (! $admin) {
            $this->error('No admin user found to attribute the demo records to.');

            return self::FAILURE;
        }

        $this->adminId = $admin->id;

        $shift = \App\Models\Shift::where('is_active', 1)->orderBy('id')->first();

        if (! $shift) {
            $this->error('No active shift found — create one before seeding demo data.');

            return self::FAILURE;
        }

        $this->shift = $shift;

        DB::transaction(function () {
            $this->seedHolidays();
            $this->seedEmployees();
            $this->seedAugustBaseline();
            $this->seedScenarios();
        });

        $this->info('Demo data created.');
        $this->newLine();
        $this->printSummary();

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────
    // Holidays
    // ──────────────────────────────────────────────

    protected function seedHolidays(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'title' => 'New Year\'s Day'],
            ['date' => '2026-03-23', 'title' => 'Pakistan Day'],
            ['date' => '2026-05-01', 'title' => 'Labour Day'],
            ['date' => '2026-07-14', 'title' => 'Founders Day (Sample Holiday)'],
        ];

        foreach ($holidays as $holiday) {
            CompanyOffDay::create([
                'note' => self::HOLIDAY_PREFIX . $holiday['title'],
                'start_date' => $holiday['date'],
                'end_date' => $holiday['date'],
                'type' => 'Holiday',
            ]);
        }

        WorkingDayService::flush();
        $this->line('Created 4 holidays.');
    }

    // ──────────────────────────────────────────────
    // Employees
    // ──────────────────────────────────────────────

    protected function seedEmployees(): void
    {
        $definitions = [
            'ali' => ['name' => 'Ali Raza', 'position' => 'Waiter', 'gender' => 'male', 'joining_date' => '2024-01-10', 'salary' => 38000],
            'bushra' => ['name' => 'Bushra Yousuf', 'position' => 'Front Desk Officer', 'gender' => 'female', 'joining_date' => '2023-06-15', 'salary' => 42000],
            'usman' => ['name' => 'Usman Tariq', 'position' => 'Kitchen Staff', 'gender' => 'male', 'joining_date' => '2024-03-01', 'salary' => 36000],
            'farah' => ['name' => 'Farah Siddiqui', 'position' => 'Housekeeping Supervisor', 'gender' => 'female', 'joining_date' => '2022-11-20', 'salary' => 45000],
            'kamran' => ['name' => 'Kamran Sheikh', 'position' => 'Security Guard', 'gender' => 'male', 'joining_date' => '2023-09-05', 'salary' => 35000],
            'nida' => ['name' => 'Nida Aslam', 'position' => 'Housekeeping Staff', 'gender' => 'female', 'joining_date' => '2024-02-14', 'salary' => 33000],
            'hassan' => ['name' => 'Hassan Raza', 'position' => 'Bellboy', 'gender' => 'male', 'joining_date' => '2023-12-01', 'salary' => 32000],
            'sadia' => ['name' => 'Sadia Iqbal', 'position' => 'Front Desk Officer', 'gender' => 'female', 'joining_date' => '2026-05-15', 'salary' => 40000],
        ];

        $role = Role::where('name', 'employee')->first();
        $seq = 1;

        foreach ($definitions as $key => $def) {
            $email = strtolower($key) . self::EMAIL_DOMAIN;

            $user = User::create([
                'name' => self::NAME_PREFIX . $def['name'],
                'email' => $email,
                'password' => Hash::make('dummy-not-a-real-login'),
            ]);

            if ($role) {
                $user->roles()->sync([$role->id]);
            }

            $employee = Employee::create([
                'user_id' => $user->id,
                'name' => self::NAME_PREFIX . $def['name'],
                'email' => $email,
                'position' => $def['position'],
                'gender' => $def['gender'],
                'joining_date' => $def['joining_date'],
                'probation' => '3',
                'status' => 1,
                'contact_no' => '0300000' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'emergency_no' => '0301000' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'branch_id' => 1,
                'salary' => $def['salary'],
            ]);

            $employee->shifts()->attach($this->shift->id, ['assigned_at' => $def['joining_date']]);

            EmployeeSetting::create(['emp_id' => $employee->id, 'setting_name' => 'late_grace_minutes', 'setting_value' => 5, 'updated_by' => $this->adminId]);
            EmployeeSetting::create(['emp_id' => $employee->id, 'setting_name' => 'time_zone', 'setting_value' => 'Asia/Karachi', 'updated_by' => $this->adminId]);

            $this->employees[$key] = $employee->fresh();
            $seq++;
        }

        $this->line('Created ' . count($this->employees) . ' dummy employees, each allocated their leave balances automatically on creation.');
    }

    // ──────────────────────────────────────────────
    // August 1–11, 2026 baseline attendance
    // ──────────────────────────────────────────────

    /**
     * A normal working day for everyone, except the date each employee's
     * specific scenario needs the day for something else.
     */
    protected function seedAugustBaseline(): void
    {
        $reserved = [
            'ali' => ['2026-08-03'],   // uses a CPL day
            'farah' => ['2026-08-04'], // half day
            'kamran' => ['2026-08-06'], // unexplained absence
            'nida' => ['2026-08-05', '2026-08-10'], // late arrivals
            'hassan' => ['2026-08-07'], // late arrival
        ];

        foreach ($this->employees as $key => $employee) {
            if (Carbon::parse($employee->joining_date)->gt(Carbon::parse('2026-08-01'))) {
                // Joined mid-window — only mark days from their joining date.
                $start = Carbon::parse($employee->joining_date)->max(Carbon::parse('2026-08-01'));
            } else {
                $start = Carbon::parse('2026-08-01');
            }

            $skip = $reserved[$key] ?? [];

            for ($date = $start->copy(); $date->lte(Carbon::parse('2026-08-11')); $date->addDay()) {
                if (in_array($date->toDateString(), $skip, true)) {
                    continue;
                }

                $this->presentAttendance($employee, $date->toDateString(), '09:03', '18:05');
            }
        }

        $this->line('Created baseline August 1–11, 2026 attendance for all employees.');
    }

    // ──────────────────────────────────────────────
    // Scenarios
    // ──────────────────────────────────────────────

    protected function seedScenarios(): void
    {
        $this->scenarioHolidayWork();
        $this->scenarioPaidLeave();
        $this->scenarioUnpaidLeave();
        $this->scenarioHalfDay();
        $this->scenarioLateArrivals();
        $this->scenarioComboEmployee();
        $this->scenarioCplUsage();

        $this->line('Applied all leave / attendance / CPL scenarios.');
    }

    /**
     * Jul 14 is the shared demo holiday. Ali, Bushra and Hassan work it and
     * earn compensatory leave; everyone else simply has no record that day,
     * which the existing day-status rules already render as "Holiday" rather
     * than "Absent" — nothing extra needed to make that true.
     */
    protected function scenarioHolidayWork(): void
    {
        foreach (['ali', 'bushra', 'hassan'] as $key) {
            $employee = $this->employees[$key];
            $this->presentAttendance($employee, '2026-07-14', '09:00', '17:30');
        }

        // Ali and Bushra: HR has already reviewed and approved the credit.
        foreach (['ali', 'bushra'] as $key) {
            $credit = CompensatoryLeave::where('employee_id', $this->employees[$key]->id)
                ->whereDate('worked_date', '2026-07-14')
                ->firstOrFail();

            CompensatoryLeaveService::approve($credit, $this->adminId);
        }

        // Hassan's credit is deliberately left Pending, to show what an
        // unreviewed compensatory leave request looks like.
    }

    /**
     * Bushra takes 3 days of approved annual leave on ordinary working days.
     */
    protected function scenarioPaidLeave(): void
    {
        Leave::create([
            'employee_id' => $this->employees['bushra']->id,
            'shift_id' => $this->shift->id,
            'leave_type' => 'annual',
            'start_date' => '2026-02-09',
            'end_date' => '2026-02-11',
            'status' => 'Approved',
            'reason' => '[TEST] Family event — paid annual leave',
            'is_balance_deducted' => 1,
            'day_type' => 'full_day',
        ]);
    }

    /**
     * Usman is away one day without it touching his paid balance — the
     * "Absent (Unpaid)" path HR uses via the same trait method the manual
     * attendance form calls.
     */
    protected function scenarioUnpaidLeave(): void
    {
        $employee = $this->employees['usman'];

        $this->handleUnpaidAbsentRecord($employee->id, '2026-03-05', $this->shift, '[TEST] Personal emergency, unpaid');

        // handleUnpaidAbsentRecord tags the reason with its own prefix; keep it
        // identifiable as demo data on top of that.
        Leave::where('employee_id', $employee->id)
            ->whereDate('start_date', '2026-03-05')
            ->update(['reason' => '[TEST] Absent (Unpaid): Personal emergency']);
    }

    /**
     * Farah is checked in but flagged half day on Aug 4 — via the same trait
     * method HR uses, which both records the HalfDay and deducts 0.5 from her
     * annual balance through an Approved half-day Leave.
     */
    protected function scenarioHalfDay(): void
    {
        $employee = $this->employees['farah'];
        $date = '2026-08-04';

        $attendance = $this->presentAttendance($employee, $date, '09:00', '13:00');

        $this->createHalfDayRecord($attendance, $this->shift, '[TEST] Left after morning shift');
    }

    /**
     * Nida arrives late twice in August; Hassan once. Each goes through
     * createLateRecord — the same call a manually-entered "Late" status makes
     * — so the sheet shows the same "Late (Xh Ym)" badge HR would see.
     */
    protected function scenarioLateArrivals(): void
    {
        $late = [
            ['key' => 'nida', 'date' => '2026-08-05', 'check_in' => '09:35'],
            ['key' => 'nida', 'date' => '2026-08-10', 'check_in' => '09:55'],
            ['key' => 'hassan', 'date' => '2026-08-07', 'check_in' => '09:28'],
        ];

        foreach ($late as $entry) {
            $employee = $this->employees[$entry['key']];
            $attendance = $this->presentAttendance($employee, $entry['date'], $entry['check_in'], '18:05');

            if ($attendance->late_duration > 0) {
                $this->createLateRecord($attendance, $this->shift, '[TEST] Manual demo entry');
            }
        }
    }

    /**
     * Hassan also gets a one-day approved leave earlier in the year, on top
     * of his late arrival and pending CPL — the "a bit of everything" profile.
     */
    protected function scenarioComboEmployee(): void
    {
        Leave::create([
            'employee_id' => $this->employees['hassan']->id,
            'shift_id' => $this->shift->id,
            'leave_type' => 'annual',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'status' => 'Approved',
            'reason' => '[TEST] Personal errand — paid annual leave',
            'is_balance_deducted' => 1,
            'day_type' => 'full_day',
        ]);
    }

    /**
     * Ali spends the compensatory day he earned on Jul 14. This is an ordinary
     * Approved leave against the 'cpl' leave type — exactly how using earned
     * CPL works today — so LeaveObserver deducts it from his cpl balance.
     */
    protected function scenarioCplUsage(): void
    {
        Leave::create([
            'employee_id' => $this->employees['ali']->id,
            'shift_id' => $this->shift->id,
            'leave_type' => 'cpl',
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-03',
            'status' => 'Approved',
            'reason' => '[TEST] Using compensatory day earned on 14 Jul',
            'is_balance_deducted' => 1,
            'day_type' => 'full_day',
        ]);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * A checked-in attendance row, with late_duration computed the same way
     * the manual entry form computes it (grace period against shift start).
     * Does not create a LateArrival row by itself — callers that want the
     * "Late" badge to show call createLateRecord() afterwards, matching how
     * a manually-entered "Late" status behaves.
     */
    protected function presentAttendance(Employee $employee, string $date, string $checkIn, string $checkOut)
    {
        $tz = 'Asia/Karachi';
        $shiftStart = Carbon::parse($date . ' ' . $this->shift->start_time, $tz);
        $checkInAt = Carbon::parse($date . ' ' . $checkIn, $tz);
        $checkOutAt = Carbon::parse($date . ' ' . $checkOut, $tz);

        $graceMinutes = 5;
        $lateDuration = $checkInAt->gt($shiftStart->copy()->addMinutes($graceMinutes))
            ? $shiftStart->diffInMinutes($checkInAt)
            : 0;

        return \App\Models\Attendance::create([
            'emp_id' => $employee->id,
            'shift_id' => $this->shift->id,
            'shift_date' => $date,
            'check_in' => $checkInAt,
            'check_out' => $checkOutAt,
            'late_duration' => $lateDuration,
            'status' => 'Present',
            'is_manual' => true,
            'modified_by' => $this->adminId,
        ]);
    }

    protected function printSummary(): void
    {
        $year = 2026;

        $this->table(
            ['Employee', 'Sick', 'Casual', 'Annual', 'CPL', 'Present', 'Half Day', 'Late'],
            collect($this->employees)->map(function (Employee $employee) use ($year) {
                $employee = $employee->fresh();
                $balances = LeaveBalance::where('employee_id', $employee->id)->where('year', $year)->get()->keyBy('leave_type');
                $fmt = fn ($slug) => $balances->has($slug)
                    ? $balances[$slug]->remaining . '/' . $balances[$slug]->allocated
                    : '0/0';

                $attendances = \App\Models\Attendance::where('emp_id', $employee->id)->get();

                return [
                    str_replace(self::NAME_PREFIX, '', $employee->name),
                    $fmt('sick'),
                    $fmt('casual'),
                    $fmt('annual'),
                    $fmt('cpl'),
                    $attendances->where('status', 'Present')->count(),
                    HalfDay::where('emp_id', $employee->id)->count(),
                    LateArrival::where('emp_id', $employee->id)->count(),
                ];
            })->values()->all()
        );
    }

    // ──────────────────────────────────────────────
    // Removal
    // ──────────────────────────────────────────────

    protected function remove(): int
    {
        $employeeIds = Employee::withTrashed()->where('email', 'like', '%' . self::EMAIL_DOMAIN)->pluck('id');
        $userIds = User::withTrashed()->where('email', 'like', '%' . self::EMAIL_DOMAIN)->pluck('id');

        if ($employeeIds->isEmpty() && $userIds->isEmpty()) {
            $this->info('No demo data found.');
        } else {
            DB::transaction(function () use ($employeeIds, $userIds) {
                CompensatoryLeave::whereIn('employee_id', $employeeIds)->delete();
                DB::table('leave_cashouts')->whereIn('employee_id', $employeeIds)->delete();
                DB::table('employee_career_events')->whereIn('employee_id', $employeeIds)->delete();
                Leave::whereIn('employee_id', $employeeIds)->delete();
                LeaveBalance::whereIn('employee_id', $employeeIds)->delete();
                LateArrival::whereIn('emp_id', $employeeIds)->delete();
                HalfDay::whereIn('emp_id', $employeeIds)->delete();
                DB::table('attendances')->whereIn('emp_id', $employeeIds)->delete();
                DB::table('employee_shifts')->whereIn('emp_id', $employeeIds)->delete();
                EmployeeSetting::whereIn('emp_id', $employeeIds)->delete();
                DB::table('model_has_roles')->whereIn('model_id', $userIds)->where('model_type', User::class)->delete();
                // Both models soft-delete by default; a lingering soft-deleted
                // row would keep the unique email and block re-seeding, so this
                // dummy data is force-deleted rather than trashed.
                Employee::withTrashed()->whereIn('id', $employeeIds)->forceDelete();
                User::withTrashed()->whereIn('id', $userIds)->forceDelete();
            });

            $this->info("Removed {$employeeIds->count()} demo employee(s) and all their records.");
        }

        $holidays = CompanyOffDay::where('note', 'like', self::HOLIDAY_PREFIX . '%')->delete();
        $this->info("Removed {$holidays} demo holiday(s).");

        WorkingDayService::flush();

        return self::SUCCESS;
    }
}
