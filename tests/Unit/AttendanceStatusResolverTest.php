<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceStatusResolver;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceStatusResolverTest extends TestCase
{
    public function test_break_exceeded_overrides_present_label(): void
    {
        $employee = new Employee(['id' => 1, 'team_id' => null]);
        $attendance = new Attendance([
            'emp_id' => 1,
            'check_in' => now()->subHours(4),
            'check_out' => null,
            'shift_date' => now()->toDateString(),
        ]);

        $resolver = new AttendanceStatusResolver();
        $result = $resolver->resolve(
            $employee,
            $attendance,
            now()->toDateString(),
            ['exceeded' => 30, 'isOnBreak' => false, 'allowed' => 45, 'spent' => 75],
            null,
            'Asia/Karachi',
            Carbon::now('Asia/Karachi')
        );

        $this->assertSame(AttendanceStatusResolver::BREAK_EXCEEDED, $result['code']);
        $this->assertSame('break_exceeded', $result['alert']);
        $this->assertSame(30, $result['break_exceeded_minutes']);
    }

    public function test_checked_out_status_includes_checkout_time(): void
    {
        $employee = new Employee(['id' => 2, 'team_id' => null]);
        $attendance = new Attendance([
            'emp_id' => 2,
            'check_in' => now()->subHours(8),
            'check_out' => Carbon::parse('2026-07-08 18:30:00', 'Asia/Karachi'),
            'shift_date' => '2026-07-08',
        ]);

        $resolver = new AttendanceStatusResolver();
        $result = $resolver->resolve(
            $employee,
            $attendance,
            '2026-07-08',
            ['exceeded' => 0, 'isOnBreak' => false],
            null,
            'Asia/Karachi'
        );

        $this->assertSame(AttendanceStatusResolver::CHECKED_OUT, $result['code']);
        $this->assertTrue($result['show_checkout_time']);
        $this->assertNotEmpty($result['checkout_time']);
    }

    public function test_net_minutes_caps_orphan_session_at_sixteen_hours(): void
    {
        $service = new DashboardMetricsService();
        $attendance = new Attendance([
            'check_in' => Carbon::parse('2026-01-01 09:00:00'),
            'check_out' => null,
            'shift_date' => '2026-01-01',
            'emp_id' => 99,
        ]);
        $attendance->setRelation('breaks', collect());

        $minutes = $service->netMinutes($attendance, 'Asia/Karachi', Carbon::parse('2026-01-05 12:00:00', 'Asia/Karachi'));

        $this->assertLessThanOrEqual(DashboardMetricsService::MAX_SESSION_MINUTES, $minutes);
    }

    public function test_test_employee_detection_by_email(): void
    {
        $employee = new Employee(['name' => 'John', 'email' => 'checkout-test-on-break@test.local']);
        $this->assertTrue(AttendanceStatusResolver::isTestEmployee($employee));
    }

    public function test_stale_heartbeat_returns_connection_lost_for_active_checkin(): void
    {
        $employee = new Employee(['id' => 3, 'team_id' => null]);
        $attendance = new Attendance([
            'emp_id' => 3,
            'check_in' => now()->subHours(2),
            'check_out' => null,
            'shift_date' => now()->toDateString(),
        ]);

        $resolver = new AttendanceStatusResolver();
        $now = Carbon::now('Asia/Karachi');
        $staleResponse = new \App\Models\AppResponse([
            'response_update' => $now->copy()->subMinutes(20)->utc(),
        ]);

        $result = $resolver->resolve(
            $employee,
            $attendance,
            $now->toDateString(),
            ['exceeded' => 0, 'isOnBreak' => false],
            $staleResponse,
            'Asia/Karachi',
            $now
        );

        $this->assertSame(AttendanceStatusResolver::CONNECTION_LOST, $result['code']);
        $this->assertSame('connection_lost', $result['alert']);
    }
}
