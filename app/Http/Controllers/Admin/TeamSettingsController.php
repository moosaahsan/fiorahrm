<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Employee;
use App\Models\EmployeeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;

class TeamSettingsController extends Controller
{
    public function index()
    {
        $this->authorize('manage-employee-settings');
        
        $teams = Team::accessible()
            ->withCount('employees')
            ->get();

        return view('admin.settings.employee-settings', compact('teams'));
    }

    public function getTeamSettings($teamId)
    {
        $this->authorize('manage-employee-settings');

        $team = Team::accessible()->with('employees')->findOrFail($teamId);
        $employees = $team->employees;

        // Default values
        $defaults = [
            'break_duration' => 60,
            'late_grace_minutes' => 5,
            'leaves_allowed_in_year' => 16,
            'idle_time_allowed' => 5,
            'full_day_allowed_in_month' => 0,
            'half_day_allowed_in_month' => 0,
        ];

        if ($employees->isEmpty()) {
            return response()->json([
                'settings' => [
                    'break_duration' => $defaults['break_duration'],
                    'late_minutes_margin' => $defaults['late_grace_minutes'],
                    'leaves_allowed_in_year' => $defaults['leaves_allowed_in_year'],
                    'idle_time_allowed' => $defaults['idle_time_allowed'],
                    'number_full_days_allowed_in_month' => $defaults['full_day_allowed_in_month'],
                    'number_half_days_allowed_in_month' => $defaults['half_day_allowed_in_month'],
                ],
                'outliers' => []
            ]);
        }

        // Fetch ALL settings for ALL employees in the team in one go
        $empIds = $employees->pluck('id');
        $allSettings = DB::table('employee_settings')
            ->whereIn('emp_id', $empIds)
            ->get()
            ->groupBy('setting_name');

        // Calculate Consenus (Mode) for each setting
        $teamPolicy = [];
        $settingKeys = [
            'break_duration',
            'late_grace_minutes',
            'leaves_allowed_in_year',
            'idle_time_allowed',
            'full_day_allowed_in_month',
            'half_day_allowed_in_month'
        ];

        foreach ($settingKeys as $key) {
            $values = $allSettings->get($key, collect())->pluck('setting_value');
            if ($values->isEmpty()) {
                // Fallback to app defaults if no settings found for any employee
                $teamPolicy[$key] = (int) ($defaults[$key] ?? 0);
            } else {
                // Get the most frequent value (Mode)
                $counts = array_count_values($values->toArray());
                arsort($counts);
                $teamPolicy[$key] = (int) key($counts);
            }
        }

        // Map keys for frontend (using UI-friendly names for late grace)
        $mappedPolicy = [
            'break_duration' => $teamPolicy['break_duration'],
            'late_minutes_margin' => $teamPolicy['late_grace_minutes'],
            'leaves_allowed_in_year' => $teamPolicy['leaves_allowed_in_year'],
            'idle_time_allowed' => $teamPolicy['idle_time_allowed'],
            'number_full_days_allowed_in_month' => $teamPolicy['full_day_allowed_in_month'],
            'number_half_days_allowed_in_month' => $teamPolicy['half_day_allowed_in_month'],
        ];

        // Group settings by employee for outlier detection
        $settingsByEmp = DB::table('employee_settings')
            ->whereIn('emp_id', $empIds)
            ->get()
            ->groupBy('emp_id');

        // Outlier detection
        $outliers = [];
        foreach ($employees as $emp) {
            $empSettings = $settingsByEmp->get($emp->id, collect())->pluck('setting_value', 'setting_name');
            $isOutlier = false;
            $diffs = [];
            
            $empMapped = [
                'break_duration' => (int) ($empSettings->get('break_duration') ?? $defaults['break_duration']),
                'late_grace_minutes' => (int) ($empSettings->get('late_grace_minutes') ?? $defaults['late_grace_minutes']),
                'leaves_allowed_in_year' => (int) ($empSettings->get('leaves_allowed_in_year') ?? $defaults['leaves_allowed_in_year']),
                'idle_time_allowed' => (int) ($empSettings->get('idle_time_allowed') ?? $defaults['idle_time_allowed']),
                'full_day_allowed_in_month' => (int) ($empSettings->get('full_day_allowed_in_month') ?? $defaults['full_day_allowed_in_month']),
                'half_day_allowed_in_month' => (int) ($empSettings->get('half_day_allowed_in_month') ?? $defaults['half_day_allowed_in_month']),
            ];

            foreach ($teamPolicy as $key => $val) {
                if ($empMapped[$key] !== $val) {
                    $isOutlier = true;
                    $label = ucwords(str_replace('_', ' ', $key));
                    if ($key === 'late_grace_minutes') $label = 'Late Grace';
                    
                    $diffs[] = [
                        'label' => $label,
                        'value' => $empMapped[$key]
                    ];
                }
            }

            if ($isOutlier) {
                $outliers[] = [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'diffs' => $diffs
                ];
            }
        }

        return response()->json([
            'settings' => $mappedPolicy,
            'outliers' => $outliers
        ]);
    }

    public function applyToTeam(Request $request)
    {
        $this->authorize('manage-employee-settings');

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'break_duration' => 'required|integer|min:0',
            'late_minutes_margin' => 'required|integer|min:0',
            'leaves_allowed_in_year' => 'required|integer|min:0',
            'idle_time_allowed' => 'required|integer|min:0',
            'number_full_days_allowed_in_month' => 'required|integer|min:0',
            'number_half_days_allowed_in_month' => 'required|integer|min:0',
        ]);

        $team = Team::accessible()->findOrFail($validated['team_id']);
        $employees = $team->employees()->pluck('id');

        if ($employees->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'This team has no employees.'], 422);
        }

        $settingsToUpdate = [
            'break_duration' => $validated['break_duration'],
            'late_grace_minutes' => $validated['late_minutes_margin'],
            'leaves_allowed_in_year' => $validated['leaves_allowed_in_year'],
            'idle_time_allowed' => $validated['idle_time_allowed'],
            'full_day_allowed_in_month' => $validated['number_full_days_allowed_in_month'],
            'half_day_allowed_in_month' => $validated['number_half_days_allowed_in_month'],
        ];

        DB::beginTransaction();
        try {
            foreach ($employees as $empId) {
                foreach ($settingsToUpdate as $name => $value) {
                    EmployeeSetting::updateOrCreate(
                        ['emp_id' => $empId, 'setting_name' => $name],
                        ['setting_value' => $value, 'updated_by' => auth()->id() ?? 1]
                    );
                }

                clear_employee_settings_cache($empId);

                // Also update the main employee table for redundancy if needed (as seen in update method)
                Employee::where('id', $empId)->update([
                    'break_duration' => $validated['break_duration'],
                    'late_minutes_margin' => $validated['late_minutes_margin'],
                    'leaves_allowed_in_year' => $validated['leaves_allowed_in_year'],
                    'idle_time_allowed' => $validated['idle_time_allowed'],
                    'number_full_days_allowed_in_month' => $validated['number_full_days_allowed_in_month'],
                    'number_half_days_allowed_in_month' => $validated['number_half_days_allowed_in_month'],
                ]);
            }

            DB::commit();

            ActivityLogger::log('update', 'TeamSettings', "Applied policy overrides to Team: {$team->name}");

            return response()->json(['success' => true, 'message' => "Settings applied to {$employees->count()} employees in {$team->name} successfully!"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to apply settings: ' . $e->getMessage()], 500);
        }
    }
}
