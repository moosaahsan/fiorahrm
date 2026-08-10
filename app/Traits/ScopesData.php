<?php

namespace App\Traits;

use App\Models\Department;
use App\Models\Team;
use App\Models\Employee;

trait ScopesData
{
    /**
     * Scope a query to only include records accessible by the current user.
     */
    public function scopeAccessible($query)
    {
        $user = auth()->user();
        $table = $this->getTable();
        
        if (!$user) return $query->whereRaw('1=0');

        // Tables that actually have a branch_id column
        $branchAwareTables = [
            'employees', 'departments', 'teams', 'shifts',
            'attendances', 'leaves', 'leave_balances',
            'company_off_days', 'late_arrivals', 'employee_breaks',
            'salary_structures', 'payrolls', 'compensatory_leaves',
        ];
        $hasBranchId = in_array($table, $branchAwareTables);

        // Define scoping variables early for cross-role use
        $personnelTables = [
            'employees', 'attendances', 'leaves', 'leave_balances',
            'employee_shifts', 'employee_breaks', 'late_arrivals', 'screenshots', 'half_days',
            'payroll_items', 'compensatory_leaves',
        ];

        $employeeIdColumn = 'id';
        if ($table !== 'employees') {
            $employeeIdColumn = 'emp_id';
        }
        $specialColumns = [
            'leaves' => 'employee_id',
            'leave_balances' => 'employee_id',
            'payroll_items' => 'employee_id',
            'compensatory_leaves' => 'employee_id',
        ];
        if (isset($specialColumns[$table])) {
            $employeeIdColumn = $specialColumns[$table];
        }

        // 1. Full Access / Monitoring Cluster Logic
        $privilegedRoles = ['admin', 'hr', 'administrator', 'Floor Manager', 'manager', 'supervisor', 'team-lead', 'coo'];
        
        if ($user->hasRole($privilegedRoles)) {
            // EXPERT RECOMMENDATION: Priority Monitoring Cluster Scoping
            $assignedTeamIds = \DB::table('user_teams')->where('user_id', $user->id)->pluck('team_id')->toArray();
            
            if (!empty($assignedTeamIds)) {
                // If specific teams are assigned, restrict visibility strictly to those clusters
                if (in_array($table, $personnelTables)) {
                    $query->whereIn($table . '.' . $employeeIdColumn, function($sq) use ($assignedTeamIds) {
                        $sq->select('id')->from('employees')->whereIn('team_id', $assignedTeamIds);
                    });
                } elseif ($table === 'teams') {
                    $query->whereIn($table . '.id', $assignedTeamIds);
                } elseif ($table === 'departments') {
                    $query->whereIn($table . '.id', function($sq) use ($assignedTeamIds) {
                        $sq->select('department_id')->from('teams')->whereIn('id', $assignedTeamIds);
                    });
                } elseif ($table === 'branches') {
                    $query->whereIn($table . '.id', function($sq) use ($assignedTeamIds) {
                        $sq->select('branch_id')->from('teams')->whereIn('id', $assignedTeamIds);
                    });
                } elseif ($table === 'shifts') {
                    // Narrow shifts to those used by the assigned teams OR belonging to their branches
                    $query->where(function($q) use ($table, $assignedTeamIds) {
                        $q->whereIn($table . '.id', function($sq) use ($assignedTeamIds) {
                            $sq->select('shift_id')->from('employee_shifts')
                               ->whereIn('emp_id', function($ssq) use ($assignedTeamIds) {
                                   $ssq->select('id')->from('employees')->whereIn('team_id', $assignedTeamIds);
                               });
                        })
                        ->orWhereIn($table . '.branch_id', function($sq) use ($assignedTeamIds) {
                            $sq->select('branch_id')->from('teams')->whereIn('id', $assignedTeamIds);
                        })
                        ->orWhereNull($table . '.branch_id');
                    });
                } elseif ($hasBranchId) {
                    // Fallback for other branch-aware tables
                    $query->whereIn($table . '.branch_id', function($sq) use ($assignedTeamIds) {
                        $sq->select('branch_id')->from('teams')->whereIn('id', $assignedTeamIds);
                    });
                }
                return $query; // Terminate further scoping for monitoring cluster
            } else {
                // Standard Admin/HR Full Access (Branch Isolated if active_branch_id is set)
                $activeBranchId = session('active_branch_id');
                if ($activeBranchId && !request()->is('admin/branches*') && $hasBranchId) {
                    $query->where($table . '.branch_id', $activeBranchId);
                }
                return $query;
            }
        }

        // --- Non-admin flow / Multi-Team fallback ---
        $employee = $user->employee;
        
        // If not an admin and no employee record, check if they at least have monitoring teams
        $monitoringTeamIds = \DB::table('user_teams')->where('user_id', $user->id)->pluck('team_id')->toArray();

        if (!$employee && empty($monitoringTeamIds)) {
            return $query->whereRaw('1=0');
        }

        $selfId = $employee->id ?? 0;
        $branchId = $employee->branch_id ?? null;

        // 2. Branch Isolation
        if ($branchId && $hasBranchId) {
            $query->where($table . '.branch_id', $branchId);
        }

        // 3. Multi-Tiered Scoping
        if (!in_array($table, $personnelTables)) {
            return $query;
        }

        return $query->where(function($q) use ($user, $table, $employeeIdColumn, $selfId, $monitoringTeamIds) {
            // A. Access to self
            if ($selfId) {
                $q->where($table . '.' . $employeeIdColumn, $selfId);
            }

            // B. Access via Managed Departments
            $q->orWhereIn($table . '.' . $employeeIdColumn, function($sq) use ($user) {
                $sq->select('employees.id')
                   ->from('employees')
                   ->join('teams', 'employees.team_id', '=', 'teams.id')
                   ->join('departments', 'teams.department_id', '=', 'departments.id')
                   ->where('departments.manager_id', $user->id);
            });

            // C. Access via Led Teams
            $q->orWhereIn($table . '.' . $employeeIdColumn, function($sq) use ($user) {
                $sq->select('employees.id')
                   ->from('employees')
                   ->join('teams', 'employees.team_id', '=', 'teams.id')
                   ->where('teams.leader_id', $user->id);
            });

            // D. Access via Monitoring Cluster (NEW Multi-Team Assignment)
            if (!empty($monitoringTeamIds)) {
                $q->orWhereIn($table . '.' . $employeeIdColumn, function($sq) use ($monitoringTeamIds) {
                    $sq->select('id')
                       ->from('employees')
                       ->whereIn('team_id', $monitoringTeamIds);
                });
            }
        });
    }
}

