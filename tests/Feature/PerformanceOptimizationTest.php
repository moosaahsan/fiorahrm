<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AppDataController;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    public function test_legacy_agent_heartbeat_returns_success_without_auth(): void
    {
        $response = $this->postJson('/api/agent/heartbeat', [
            'employee_id' => 1,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'legacy' => true,
            ]);
    }

    public function test_legacy_agent_sync_activities_returns_success_without_auth(): void
    {
        $response = $this->postJson('/api/agent/sync-activities', [
            'activities' => [],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'legacy' => true,
                'synced' => 0,
            ]);
    }

    public function test_clear_employee_settings_cache_forgets_cached_settings(): void
    {
        Cache::put('employee_settings:99', ['idle_time_allowed' => '10'], 300);
        Cache::put('app_data_refresh:99', ['ok' => true], 45);

        clear_employee_settings_cache(99);

        $this->assertFalse(Cache::has('employee_settings:99'));
        $this->assertFalse(Cache::has('employee_active_shift:99'));
        $this->assertFalse(Cache::has('app_data_refresh:99'));
    }

    public function test_get_employee_settings_uses_cache_when_table_exists(): void
    {
        if (! Schema::hasTable('employee_settings')) {
            $this->markTestSkipped('employee_settings table not available in test DB');
        }

        Cache::forget('employee_settings:777');

        DB::table('employee_settings')->updateOrInsert(
            ['emp_id' => 777, 'setting_name' => 'idle_time_allowed'],
            ['setting_value' => '12', 'updated_by' => 1]
        );

        $first = get_employee_settings(777, 'idle_time_allowed');
        $this->assertSame('12', (string) $first);

        DB::table('employee_settings')
            ->where('emp_id', 777)
            ->where('setting_name', 'idle_time_allowed')
            ->update(['setting_value' => '99']);

        $cached = get_employee_settings(777, 'idle_time_allowed');
        $this->assertSame('12', (string) $cached);

        clear_employee_settings_cache(777);
        $fresh = get_employee_settings(777, 'idle_time_allowed');
        $this->assertSame('99', (string) $fresh);

        DB::table('employee_settings')->where('emp_id', 777)->delete();
    }

    public function test_clear_employee_app_data_cache_forgets_refresh_key(): void
    {
        Cache::put('app_data_refresh:42', ['attendance_status' => 'Checked In'], 45);
        Cache::put('attendance_stats_month:v2:42:' . date('Y-m'), ['present_days' => 1], 60);
        Cache::put('attendance_stats_month:42:' . date('Y-m'), ['present_days' => 1], 60);
        Cache::put('leave_stats:42:' . date('Y'), ['approved_days' => 0], 60);

        clear_employee_app_data_cache(42);

        $this->assertFalse(Cache::has('app_data_refresh:42'));
        $this->assertFalse(Cache::has('attendance_stats_month:v2:42:' . date('Y-m')));
        $this->assertFalse(Cache::has('attendance_stats_month:42:' . date('Y-m')));
        $this->assertFalse(Cache::has('leave_stats:42:' . date('Y')));
    }

    public function test_refresh_data_can_be_cached_without_force(): void
    {
        Cache::forget('app_data_refresh:55');

        $service = $this->createMock(EmployeeService::class);
        $service->expects($this->once())
            ->method('getAppData')
            ->with(55)
            ->willReturn(['attendance_status' => 'Not Checked In', 'employee_id' => 55]);

        $this->app->instance(EmployeeService::class, $service);

        $controller = app(AppDataController::class);

        $first = $controller->refresh(new Request(['employee_id' => 55]));
        $second = $controller->refresh(new Request(['employee_id' => 55]));

        $this->assertSame('Not Checked In', $first->getData(true)['attendance_status']);
        $this->assertSame('Not Checked In', $second->getData(true)['attendance_status']);
    }

    public function test_refresh_data_force_bypasses_cache(): void
    {
        Cache::put('app_data_refresh:56', ['attendance_status' => 'Stale'], 45);

        $service = $this->createMock(EmployeeService::class);
        $service->expects($this->once())
            ->method('getAppData')
            ->with(56)
            ->willReturn(['attendance_status' => 'Checked In', 'employee_id' => 56]);

        $this->app->instance(EmployeeService::class, $service);

        $controller = app(AppDataController::class);
        $response = $controller->refresh(new Request(['employee_id' => 56, 'force' => true]));

        $this->assertSame('Checked In', $response->getData(true)['attendance_status']);
    }
}
