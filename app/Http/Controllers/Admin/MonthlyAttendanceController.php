<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MonthlyAttendanceController extends Controller
{
    public function monthlyView(Request $request)
    {
        $employees = Employee::accessible()
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'resign_date']);

        $employees->transform(function($emp) {
            if ($emp->resign_date) {
                $emp->name = $emp->name . ' (Resigned)';
            }
            return $emp;
        });

        $activeEmployees = $employees->filter(function($emp) {
            return is_null($emp->resign_date);
        });

        $leftEmployees = $employees->filter(function($emp) {
            return !is_null($emp->resign_date);
        });

        $shifts = Shift::accessible()->orderBy('shift_name')->get(['id', 'shift_name']);

        $period = \App\Services\PayrollPeriodService::getPeriodForDate(now());

        $start = $request->input('start')
            ? Carbon::parse($request->input('start'))
            : $period['start']->copy();

        $end = $request->input('end')
            ? Carbon::parse($request->input('end'))
            : $period['end']->copy();

        return view('admin.attendance.monthly', compact('activeEmployees', 'leftEmployees', 'shifts', 'start', 'end'));
    }

    public function monthlyData(Request $request)
    {
        // 1) Parse & normalize range (accept date_start/date_end to avoid colliding with DataTables pagination 'start' offset)
        $rawStart = $request->input('date_start') ?? $request->input('start_date');
        $rawEnd = $request->input('date_end') ?? $request->input('end_date');

        if (!$rawStart || is_numeric($rawStart)) {
            $rawStart = $request->input('start');
            if (is_numeric($rawStart)) {
                $rawStart = null;
            }
        }
        if (!$rawEnd || is_numeric($rawEnd)) {
            $rawEnd = $request->input('end');
            if (is_numeric($rawEnd)) {
                $rawEnd = null;
            }
        }

        $period = \App\Services\PayrollPeriodService::getPeriodForDate(now());
        $startDate = $rawStart ? Carbon::parse($rawStart)->startOfDay() : $period['start']->copy()->startOfDay();
        $endDate = $rawEnd ? Carbon::parse($rawEnd)->endOfDay() : $period['end']->copy()->endOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->copy(), $startDate->copy()];
        }

        // 2) Build dates & keys (Y_m_d)
        $dates = [];
        $keys = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $dates[] = $cursor->copy();
            $keys[] = $cursor->format('Y_m_d');
            $cursor->addDay();
        }

        // 3) Batch data fetching for optimization (eliminate N+1)
        $allOffDays = \App\Models\CompanyOffDay::with('teams')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $query = Employee::accessible()
            ->select('employees.id', 'employees.name', 'employees.position', 'employees.team_id', 'employees.resign_date', 'employees.joining_date')
            ->with([
                'leaves' => function ($q) use ($startDate, $endDate) {
                    $q->select('id', 'employee_id', 'start_date', 'end_date', 'status')
                        ->whereIn('status', ['Approved', 'Pending'])
                        ->where(function ($q2) use ($startDate, $endDate) {
                            $q2->whereBetween('start_date', [$startDate, $endDate])
                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                ->orWhere(function ($q3) use ($startDate, $endDate) {
                                    $q3->where('start_date', '<=', $endDate)
                                        ->where('end_date', '>=', $startDate);
                                });
                        });
                },
                'attendances' => function ($q) use ($startDate, $endDate) {
                    $q->select('id', 'emp_id', 'shift_id', 'shift_date', 'check_in', 'check_out')
                        ->with(['lateArrival', 'halfDay']) // Eager load these for the helper
                        ->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()]);
                },
                'leaveBalances' => function ($q) {
                    $q->where('year', now()->year)->with('leaveType');
                },
                'currentShift'
            ]);

        // 4) Dropdown filters
        if ($request->filled('employee_id')) {
            $query->where('employees.id', $request->employee_id);
        }
        if ($request->filled('shift')) {
            $query->whereHas('attendances', function ($q) use ($request) {
                $q->where('shift_id', $request->shift);
            });
        }

        // 5) DataTables base columns
        $dataTable = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', fn($e) => $e->name)
            ->addColumn('position', fn($e) => $e->position ?? '-')
            ->addColumn('emp_status', function ($e) {
                // Optimized: try to avoid full helper call if possible, or accept that this is O(1 per row) now
                return strip_tags(employee_status($e->id)); 
            });

        // 6) Server-side global search (unchanged)
        $dataTable->filter(function ($q) use ($request) {
            $search = trim((string) $request->input('search.value', ''));
            if ($search === '') return;
            $escapeLike = function (string $value): string {
                return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
            };
            $term = $escapeLike($search);
            $q->where(function ($inner) use ($term, $search) {
                $inner->where('employees.name', 'LIKE', "%{$term}%")
                    ->orWhere('employees.position', 'LIKE', "%{$term}%");
                if (ctype_digit($search)) $inner->orWhere('employees.id', (int) $search);
            });
        }, true);

        $tz = app_settings('app_timezone') ?? 'Asia/Karachi';

        // 7) Pre-cache grouped data per employee for the loop
        $employeeDataCache = [];

        foreach ($dates as $i => $date) {
            $key = $keys[$i]; // Y_m_d
            $dataTable->addColumn($key, function ($employee) use ($date, $key, $allOffDays, &$employeeDataCache) {
                // Initialize cache for this employee if not exists
                if (!isset($employeeDataCache[$employee->id])) {
                    $employeeDataCache[$employee->id] = [
                        'atts' => $employee->attendances->keyBy(fn($a) => Carbon::parse($a->shift_date)->format('Y_m_d')),
                        'leaves' => collect(),
                    ];
                    // Map leaves to dates
                    foreach($employee->leaves as $leave) {
                        $ls = Carbon::parse($leave->start_date)->startOfDay();
                        $le = Carbon::parse($leave->end_date)->endOfday();
                        $curr = $ls->copy();
                        while($curr <= $le) {
                            $employeeDataCache[$employee->id]['leaves']->put($curr->format('Y_m_d'), $leave->status);
                            $curr->addDay();
                        }
                    }
                }

                $cache = $employeeDataCache[$employee->id];
                $attRecord = $cache['atts']->get($key);

                // Present?
                if ($attRecord && $attRecord->check_in) {
                    // Optimized: Manually build badge if relations are loaded to avoid DB hits in helper
                    if ($attRecord->halfDay) return '<span class="z-badge z-badge-half">Half Day</span>';
                    if ($attRecord->status === 'Early Leave') return '<span class="z-badge" style="background-color: rgba(245, 158, 11, 0.1); color: #d97706; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 11px;">Early Leave</span>';
                    if ($attRecord->lateArrival) {
                        $mins = (int) ($attRecord->lateArrival->late_minutes ?? 0);
                        $label = $mins > 0 ? "Late (".format_minutes_to_hm($mins).")" : 'Late';
                        return '<span class="z-badge z-badge-late">' . e($label) . '</span>';
                    }
                    return '<span class="z-badge z-badge-present">Present</span>';
                }

                // Leave?
                $lStatus = $cache['leaves']->get($key);
                if ($lStatus) return $lStatus === 'Approved' ? 'AL' : 'PL';

                // Off Day / Holiday? (Internal check against $allOffDays collection)
                $offDay = $allOffDays->first(function($o) use ($date, $employee) {
                    $applies = $date->between($o->start_date, $o->end_date);
                    if (!$applies) return false;
                    // Check team restriction
                    if ($o->teams->isEmpty()) return true; // Global
                    return $o->teams->contains('id', $employee->team_id);
                });

                if ($offDay) return $offDay->type === 'Holiday' ? 'H' : 'O';

                // Recurring weekly off? (none by default — a hotel works every day)
                if (\App\Services\WorkingDayService::isWeeklyOffDay($date)) return 'O';

                // Exceptions / Future / Absent
                if ($employee->joining_date && $date->lt(Carbon::parse($employee->joining_date)->startOfDay())) return 'EX';
                if ($date->isFuture()) return 'U';
                if ($employee->resign_date && $date->gt(Carbon::parse($employee->resign_date))) return '-';
                
                return 'A';
            });
        }

        // 8) Summary columns from eager-loaded balances
        $dataTable->addColumn('total_paid_leaves', function ($employee) {
            return $employee->leaveBalances->where('leaveType.is_paid', true)->sum('used');
        })
        ->addColumn('total_lop', function ($employee) {
            return $employee->leaveBalances->where('leaveType.is_paid', false)->sum('used');
        })
        ->addColumn('total_allocated_leaves', function($employee) {
            return $employee->leaveBalances->where('leaveType.is_paid', true)->sum('allocated');
        })
        ->addColumn('remaining_leaves', function($employee) {
            return $employee->leaveBalances->where('leaveType.is_paid', true)->sum('remaining');
        })
        ->addColumn('assigned_check_in', function($employee) {
            $shift = $employee->currentShift->shift ?? null;
            return $shift ? date('h:i A', strtotime($shift->start_time)) : '<span class="text-muted">Unassigned</span>';
        });

        // Make all day columns raw so the helper's HTML renders in the client
        $dataTable->rawColumns(array_merge($keys, ['assigned_check_in', 'emp_status']));

        return $dataTable->make(true);

    }

}
