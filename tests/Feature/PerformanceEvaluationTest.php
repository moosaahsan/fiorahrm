<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Employee;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\EmployeeBreak;
use App\Models\LateArrival;
use App\Models\Shift;
use App\Models\Performance\PerformanceSetting;
use App\Services\PerformanceScoreCalculator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PerformanceEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $employee;
    protected $shift;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed standard settings for testing
        PerformanceSetting::create(['key' => 'attendance_weight', 'value' => 15.00]);
        PerformanceSetting::create(['key' => 'leave_weight', 'value' => 15.00]);
        PerformanceSetting::create(['key' => 'break_weight', 'value' => 10.00]);
        PerformanceSetting::create(['key' => 'late_weight', 'value' => 10.00]);
        
        $this->shift = Shift::create([
            'shift_name' => 'General',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00'
        ]);

        // Setup base employee manually (no factory)
        $this->user = User::factory()->create();
        $this->employee = Employee::create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'employee_code' => 'EMP-' . strtoupper(Str::random(5)),
            'personal_email' => 'john.doe@test.local',
            'mobile' => '1234567890',
            'status' => 'Active',
            'join_date' => now()->subYear()->toDateString(),
        ]);
    }

    /**
     * Test perfect automatic score path
     */
    public function test_perfect_automatic_score()
    {
        $calculator = new PerformanceScoreCalculator();
        
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        
        // Generate perfect attendance for all working days
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0);
        $endDate = $startDate->copy()->endOfMonth();
        $limitDate = $endDate->lt(now()) ? $endDate : now();
        
        $current = $startDate->copy();
        while ($current <= $limitDate) {
            if (!$current->isWeekend()) {
                Attendance::create([
                    'emp_id' => $this->employee->id,
                    'shift_date' => $current->toDateString(),
                    'status' => 'Present',
                    'check_in' => $current->copy()->setHour(9)->setMinute(0),
                    'check_out' => $current->copy()->setHour(17)->setMinute(0),
                    'worked_hours' => 8
                ]);
            }
            $current->addDay();
        }

        $metrics = $calculator->calculate($this->employee, $year, $month);

        $this->assertGreaterThan(14.00, $metrics['attendance_score']);
        $this->assertEquals(15.00, $metrics['leave_score']);
        $this->assertEquals(10.00, $metrics['break_score']);
        $this->assertEquals(10.00, $metrics['late_score']);
    }

    /**
     * Test score deduction for leaves, breaks, and late arrivals
     */
    public function test_deductions_in_automatic_score()
    {
        $calculator = new PerformanceScoreCalculator();
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $targetDate = Carbon::create($year, $month, 5, 0, 0, 0);
        
        if ($targetDate->isWeekend()) {
            $targetDate->subDays(2);
        }

        // Create associated attendance for constraints
        $attendance = Attendance::create([
            'emp_id' => $this->employee->id,
            'shift_date' => $targetDate->toDateString(),
            'status' => 'Present',
            'check_in' => $targetDate->copy()->setHour(9)->setMinute(30),
            'worked_hours' => 7.5
        ]);

        // 1. One Approved Leave (Deducts 3.0 points)
        Leave::create([
            'employee_id' => $this->employee->id,
            'start_date' => $targetDate->copy()->addDay()->toDateString(),
            'end_date' => $targetDate->copy()->addDay()->toDateString(),
            'status' => 'Approved',
            'type_id' => 1,
            'reason' => 'Test',
            'manager_approval' => 'Approved',
            'hr_approval' => 'Approved'
        ]);

        // 2. One Exceeded Break (Deducts 2.0 points)
        EmployeeBreak::create([
            'emp_id' => $this->employee->id,
            'shift_date' => $targetDate->toDateString(),
            'exceeded_minutes' => 15,
            'type' => 'General',
            'start_time' => $targetDate->copy()->setHour(12)->setMinute(0),
            'end_time' => $targetDate->copy()->setHour(13)->setMinute(0),
            'spent_minutes' => 60
        ]);

        // 3. One Late Arrival (Deducts 2.0 points)
        LateArrival::create([
            'emp_id' => $this->employee->id,
            'attendance_id' => $attendance->id,
            'shift_id' => $this->shift->id,
            'scheduled_start' => '09:00:00',
            'actual_check_in' => '09:30:00',
            'date' => $targetDate->toDateString(),
            'late_minutes' => 30,
            'reason' => 'Traffic',
            'is_approved' => 0
        ]);

        $metrics = $calculator->calculate($this->employee, $year, $month);

        // Max was 15 for leave, 1 deducted -> 15 - 3 = 12
        $this->assertEquals(12.00, $metrics['leave_score']);
        
        // Max was 10 for break, 1 deducted -> 10 - 2 = 8
        $this->assertEquals(8.00, $metrics['break_score']);
        
        // Max was 10 for late, 1 deducted -> 10 - 2 = 8
        $this->assertEquals(8.00, $metrics['late_score']);
    }
}
