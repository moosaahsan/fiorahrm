<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\EmployeeShift;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\EmployeeBreak;

class AttendanceController extends Controller
{
    public function logs()
    {
        $this->authorize('view-attendance');

        // 1. Fetch Accessible Departments
        $departments = \App\Models\Department::accessible()->orderBy('name')->get(['id', 'name']);

        // 2. Fetch Accessible Employees
        $employees = Employee::accessible()->whereNull('resign_date')->orderBy('name')->get(['id', 'name']);

        // 3. Fetch Accessible Shifts
        $shifts = Shift::accessible()->orderBy('shift_name')->get(['id', 'shift_name']);

        return view('admin.attendance.logs', compact('employees', 'shifts', 'departments'));
    }
    public function logsDataOld(Request $request)
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = now($timezone);

        $query = Attendance::with([
            'employee:id,name',
            'shift:id,shift_name',
            'lateArrival',
            'halfDay',
            'breaks' // Ensure breaks are eager loaded
        ])
            ->whereHas('employee', fn($q) => $q->whereNull('resign_date'))
            ->orderByDesc('shift_date');

        // Filters
        if ($request->employee_id) {
            $query->where('emp_id', $request->employee_id);
        }
        if ($request->shift_id) {
            $query->where('shift_id', $request->shift_id);
        }
        if ($request->date_range) {
            [$from, $to] = array_map('trim', explode(' - ', $request->date_range));
            $query->whereBetween('shift_date', [$from, $to]);
        }

        // Totals
        $totWorked = (clone $query)->get()->sum(function ($r) {
            $workedString = calculateWorkedHours($r);
            if ($workedString === '-' || !preg_match('/(\d+)h (\d+)m/', $workedString, $matches)) {
                return 0;
            }

            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            return $hours + ($minutes / 60);
        });

        $totLate = (clone $query)->get()->sum(fn($r) => $r->late_duration ?? 0);

        return DataTables::eloquent($query)
            ->addColumn('shift_date', fn($r) =>
                optional($r->shift_date)->format('d-M-Y') ?? '-')
            ->addColumn('employee_id', fn($r) => 'AST‑' . $r->employee->id)
            ->addColumn('employee_name', fn($r) => $r->employee->name)
            ->addColumn('shift_name', fn($r) => $r->shift->shift_name ?? '-')
            ->addColumn('check_in', fn($r) =>
                optional($r->check_in)->format('H:i:s') ?: '-')
            ->addColumn('check_out', function ($r) use ($timezone, $now) {
                // Check if on an active "General" break
                $onBreak = $r->breaks
                    ->where('type', 'General')
                    ->contains(function ($break) {
                    return $break->start_time && is_null($break->end_time);
                });

                if ($onBreak) {
                    return '<span class="badge bg-warning text-dark">On Break</span>';
                }

                return $r->check_out
                    ? $r->check_out->format('H:i:s')
                    : working_badge();
            })
            ->addColumn('worked_hours', function ($r) {
                return calculateWorkedHours($r);
            })
            ->addColumn('status_badge', function ($r) {
                if ($r->status !== 'Present') {
                    return '<span class="badge badge-secondary">Absent</span>';
                }

                if ($r->halfDay) {
                    $reason = $r->halfDay->reason ?? 'No reason given';
                    return '<span class="badge badge-warning" data-bs-toggle="tooltip" title="' . e($reason) . '">Half Day</span>';
                }

                if ($r->lateArrival) {
                    $reason = $r->lateArrival->late_reason ?? 'No reason given';
                    $duration = formatMinutesToHours($r->lateArrival->late_minutes);
                    return '<span class="badge badge-danger" data-bs-toggle="tooltip" title="' . e($reason) . '">Late</span> ' .
                        '<small class="text-muted ms-1">(' . $duration . ')</small>';
                }

                return '<span class="badge badge-success">On Time</span>';
            })
            ->addColumn('action', fn($r) =>
                '<button class="btn btn-sm btn-primary view-details" data-id="' . $r->id . '">
                <i class="fa fa-eye"></i> View
            </button>')
            ->rawColumns(['check_out', 'status_badge', 'action'])
            ->with([
                'total_worked_hours' => round($totWorked, 2), // for summary
                'total_late_minutes' => $totLate,
            ])
            ->make(true);
    }

    private function logsDataRange(Request $request, $fromDate, $toDate, $timezone, $now, $allOffDaysForRows, $allLeavesForRows)
    {
        $startDate = \Carbon\Carbon::parse($fromDate);
        $endDate = \Carbon\Carbon::parse($toDate);

        // Build base list of employees matching other filters (dept, employee, shift)
        $activeEmpQuery = Employee::accessible();
        if ($request->filled('department_id')) {
            $activeEmpQuery->whereIn('employees.team_id', \App\Models\Team::where('department_id', $request->department_id)->pluck('id'));
        }
        if ($request->filled('employee_id')) {
            $activeEmpQuery->where('employees.id', $request->employee_id);
        }
        if ($request->filled('multi_shift_ids') || $request->filled('shift_id')) {
            $ids = $request->multi_shift_ids ?: [$request->shift_id];
            $activeEmpQuery->whereHas('currentShiftAssignment', function($q) use ($ids) {
                $q->whereIn('shift_id', $ids);
            });
        }
        
        $activeEmployees = $activeEmpQuery->with(['team', 'shiftHistory.shift', 'currentShiftAssignment.shift'])->get();
        $employeeIds = $activeEmployees->pluck('id')->toArray();

        // Fetch existing attendances
        $attendances = \App\Models\Attendance::accessible()
            ->whereIn('emp_id', $employeeIds)
            ->with(['shift', 'lateArrival', 'halfDay', 'breaks'])
            ->whereBetween('shift_date', [$fromDate, $toDate])
            ->get()
            ->groupBy(function($att) {
                return $att->emp_id . '_' . \Carbon\Carbon::parse($att->shift_date)->toDateString();
            });

        $collection = collect();
        
        foreach ($activeEmployees as $emp) {
            $empId = $emp->id;
            // Get this employee's leaves (Leave model uses 'employee_id' column)
            $empLeaves = $allLeavesForRows->get($empId, collect());
            
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $curDateStr = $date->toDateString();
                $isWeekend = $date->isWeekend();
                
                if ($emp->joining_date && $date->lt(\Carbon\Carbon::parse($emp->joining_date)->startOfDay())) {
                    continue;
                }
                if ($emp->resign_date && $date->gt(\Carbon\Carbon::parse($emp->resign_date)->endOfDay())) {
                    continue;
                }

                $historicalShift = $emp->shiftHistory
                    ->filter(function($s) use ($curDateStr) {
                        return \Carbon\Carbon::parse($s->assigned_at)->format('Y-m-d') <= $curDateStr;
                    })
                    ->sortByDesc('assigned_at')
                    ->first();
                $correctShift = $historicalShift ? $historicalShift->shift : ($emp->currentShiftAssignment->shift ?? null);

                $key = $empId . '_' . $curDateStr;
                
                // Check if this day has an approved leave
                $leaveForDay = $empLeaves->first(function($l) use ($curDateStr) {
                    return $curDateStr >= $l->start_date->format('Y-m-d') && $curDateStr <= $l->end_date->format('Y-m-d');
                });
                
                $hasValidAttendance = false;
                
                if ($attendances->has($key)) {
                    foreach ($attendances->get($key) as $att) {
                        $att->setRelation('employee', $emp);
                        
                        // If this day has an approved leave, override the attendance to show as leave
                        if ($leaveForDay && $leaveForDay->status === 'Approved') {
                            // Don't push the real attendance - we'll create a leave dummy instead
                            continue;
                        }
                        
                        $collection->push($att);
                        
                        // Valid check-in or Present status
                        if ($att->status === 'Present' || $att->check_in) {
                            $hasValidAttendance = true;
                        }
                    }
                }
                
                // If approved leave exists for this day, create a leave-specific dummy
                if ($leaveForDay && $leaveForDay->status === 'Approved') {
                    $dummy = new \stdClass();
                    $dummy->id = null;
                    $dummy->emp_id = $empId;
                    $dummy->employee = $emp;
                    $dummy->shift_date = $curDateStr;
                    $dummy->status = 'Absent'; // Absent with leave = Paid
                    $dummy->check_in = null;
                    $dummy->check_out = null;
                    $dummy->shift = $correctShift;
                    $dummy->halfDay = null;
                    $dummy->lateArrival = null;
                    $dummy->breaks = collect();
                    $dummy->attendances = collect();
                    $dummy->is_weekend = $isWeekend;
                    $dummy->approved_leave = $leaveForDay; // Tag the leave for badge rendering
                    $collection->push($dummy);
                } elseif (!$hasValidAttendance) {
                    // No attendance and no leave - create absent dummy
                    $explicitAbsent = $attendances->has($key) ? collect($attendances->get($key))->firstWhere('status', 'Absent') : null;
                    if (!$explicitAbsent) {
                        $dummy = new \stdClass();
                        $dummy->id = null;
                        $dummy->emp_id = $empId;
                        $dummy->employee = $emp;
                        $dummy->shift_date = $curDateStr;
                        $dummy->status = 'Absent';
                        $dummy->check_in = null;
                        $dummy->check_out = null;
                        $dummy->shift = $correctShift;
                        $dummy->halfDay = null;
                        $dummy->lateArrival = null;
                        $dummy->breaks = collect();
                        $dummy->attendances = collect();
                        $dummy->is_weekend = $isWeekend;
                        $dummy->approved_leave = null;
                        $collection->push($dummy);
                    }
                }
            }
        }
        
        // Save unfiltered total staff (total unique active employees queried)
        $totalAccessibleEmployees = $activeEmployees->count();

        // Apply Status Filters manually to the collection
        if ($request->filled('status')) {
            $status = $request->status;
            
            $collection = $collection->filter(function($r) use ($status, $allOffDaysForRows) {
                $isMissing = $r instanceof \stdClass || (!$r->check_in && $r->status !== 'Present');
                $shiftDateStr = is_string($r->shift_date) ? $r->shift_date : $r->shift_date->format('Y-m-d');
                $teamId = $r->employee->team_id ?? (is_object($r) && isset($r->team_id) ? $r->team_id : null);
                
                $isWeekend = isset($r->is_weekend) ? $r->is_weekend : \Carbon\Carbon::parse($shiftDateStr)->isWeekend();
                $offDay = $allOffDaysForRows->first(function($o) use ($shiftDateStr, $teamId) {
                    $applies = $shiftDateStr >= $o->start_date->format('Y-m-d') && $shiftDateStr <= $o->end_date->format('Y-m-d');
                    if (!$applies) return false;
                    return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                });
                
                // Determine if this record represents a half-day (either checked-in half day or half-day leave day)
                $isHalfDay = (!$isMissing && $r->halfDay) || (isset($r->approved_leave) && $r->approved_leave && in_array($r->approved_leave->day_type, ['first_half', 'second_half', 'half_day']));
                
                // Determine if checked in late
                $isLate = !$isMissing && (bool)$r->lateArrival;
                
                if ($status === 'Present') {
                    return !$isMissing && !$r->halfDay && !$r->lateArrival;
                } elseif ($status === 'Late') {
                    return $isLate;
                } elseif ($status === 'Half Day') {
                    return $isHalfDay;
                } elseif ($status === 'Absent (Paid)') {
                    // Must be missing AND must be a full-day leave
                    $isFullDayLeave = isset($r->approved_leave) && $r->approved_leave && $r->approved_leave->day_type === 'full_day';
                    return $isMissing && $isFullDayLeave;
                } elseif ($status === 'Absent (Unpaid)') {
                    // Must be missing, NOT a weekend/holiday, AND NO approved leave of any kind
                    if (!$isMissing) return false;
                    if ($isWeekend || $offDay) return false;
                    return !(isset($r->approved_leave) && $r->approved_leave);
                } elseif ($status === 'Break Exceeded') {
                    if ($isMissing) return false;
                    
                    $allowed = get_effective_break_minutes((int) $r->emp_id, is_string($r->shift_date) ? $r->shift_date : $r->shift_date->format('Y-m-d'));
                    $completedSumSeconds = $r->breaks->where('type', 'General')->whereNotNull('end_time')->sum(function($b) {
                        $mins = $b->spent_minutes ?? (int) ceil(\Carbon\Carbon::parse($b->created_at)->diffInMinutes(\Carbon\Carbon::parse($b->end_time)));
                        return min($mins, 120);
                    }) * 60;
                    
                    $activeSeconds = 0;
                    $activeBreak = $r->breaks->where('type', 'General')->whereNull('end_time')->first();
                    if ($activeBreak) {
                        $activeSeconds = $activeBreak->calculateBreakTimeInSeconds();
                        if ($activeSeconds > (120 * 60)) {
                            $activeSeconds = 120 * 60;
                        }
                    }
                    
                    $totalSpentSeconds = $completedSumSeconds + $activeSeconds;
                    return $totalSpentSeconds > ($allowed * 60);
                }
                
                if (isset($r->status) && $r->status === $status) return true;
                return false;
            });
        }
        
        // Dynamically compute precise stats based on the finalized collection
        $totWorkedMinutes = 0;
        $totLate = 0;
        $countPresent = 0;
        $countAbsent = 0;
        $countHalfDay = 0;
        $countLate = 0;
        $totalExceededSeconds = 0;
        $totalExceededDays = 0;
        $exceededDays = [];
        $totalBreakSeconds = 0;

        foreach ($collection as $r) {
            $shiftDateStr = is_string($r->shift_date) ? $r->shift_date : $r->shift_date->format('Y-m-d');
            $isMissing = $r instanceof \stdClass || (!$r->check_in && $r->status !== 'Present');
            
            if (!$isMissing) {
                $allowed = get_effective_break_minutes((int) $r->emp_id, $shiftDateStr);
                $completedSumSeconds = $r->breaks->where('type', 'General')->whereNotNull('end_time')->sum(function($b) {
                    $mins = $b->spent_minutes ?? (int) ceil(\Carbon\Carbon::parse($b->created_at)->diffInMinutes(\Carbon\Carbon::parse($b->end_time)));
                    return min($mins, 120);
                }) * 60;
                
                $activeSeconds = 0;
                $activeBreak = $r->breaks->where('type', 'General')->whereNull('end_time')->first();
                if ($activeBreak) {
                    $activeSeconds = $activeBreak->calculateBreakTimeInSeconds();
                    if ($activeSeconds > (120 * 60)) {
                        $activeSeconds = 120 * 60;
                    }
                }
                
                $totalSpentSeconds = $completedSumSeconds + $activeSeconds;
                $totalBreakSeconds += $totalSpentSeconds;
                $allowedSeconds = $allowed * 60;
                $exceededSeconds = max($totalSpentSeconds - $allowedSeconds, 0);
                
                if ($exceededSeconds > 0) {
                    $totalExceededSeconds += $exceededSeconds;
                    $totalExceededDays++;

                    $h = floor($exceededSeconds / 3600);
                    $m = floor(($exceededSeconds % 3600) / 60);
                    $s = $exceededSeconds % 60;
                    $exceededFormatted = sprintf('%02d hours %02d minutes %02d seconds', $h, $m, $s);
                    $exceededDays[] = [
                        'date' => \Carbon\Carbon::parse($shiftDateStr)->format('d-M-Y'),
                        'time' => $exceededFormatted,
                    ];
                }
            }

            // Check if it is a half-day (either checked-in half day or half-day leave)
            $isHalfDay = (!$isMissing && $r->halfDay) || (isset($r->approved_leave) && $r->approved_leave && in_array($r->approved_leave->day_type, ['first_half', 'second_half', 'half_day']));
            
            // Check if checked in late
            $isLate = !$isMissing && (bool)$r->lateArrival;
            
            if ($isHalfDay) {
                $countHalfDay++;
                if (!$isMissing) {
                    $totWorkedMinutes += calculate_net_minutes($r, $timezone, $now);
                }
            } elseif ($isLate) {
                $countLate++;
                if (!$isMissing) {
                    $totLate += $r->lateArrival->late_minutes;
                    $totWorkedMinutes += calculate_net_minutes($r, $timezone, $now);
                }
            } elseif (!$isMissing && ($r->status === 'Present' || $r->check_in)) {
                $countPresent++;
                $totWorkedMinutes += calculate_net_minutes($r, $timezone, $now);
            } else {
                $teamId = $r->employee->team_id ?? (is_object($r) && isset($r->team_id) ? $r->team_id : null);
                $isWeekend = isset($r->is_weekend) ? $r->is_weekend : \Carbon\Carbon::parse($shiftDateStr)->isWeekend();
                
                $offDay = $allOffDaysForRows->first(function($o) use ($shiftDateStr, $teamId) {
                    $applies = $shiftDateStr >= $o->start_date->format('Y-m-d') && $shiftDateStr <= $o->end_date->format('Y-m-d');
                    if (!$applies) return false;
                    return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                });
                
                $hasApprovedLeave = isset($r->approved_leave) && $r->approved_leave;
                $isFullDayLeave = $hasApprovedLeave && $r->approved_leave->day_type === 'full_day';
                
                // Count as absent only if: has approved FULL-DAY leave (paid absent), OR is a working day without any leave (unpaid absent)
                if ($isFullDayLeave || (!$isWeekend && !$offDay && !$hasApprovedLeave)) {
                    $countAbsent++;
                }
            }
        }

        $h = floor($totalExceededSeconds / 3600);
        $m = floor(($totalExceededSeconds % 3600) / 60);
        $s = $totalExceededSeconds % 60;
        $totalExceededFormatted = sprintf('%02d hours %02d minutes %02d seconds', $h, $m, $s);

        $hBreak = floor($totalBreakSeconds / 3600);
        $mBreak = floor(($totalBreakSeconds % 3600) / 60);
        $totalBreakFormatted = sprintf('%02dh %02dm', $hBreak, $mBreak);

        if ($request->filled('export_pdf')) {
            $pdfLogs = [];
            foreach ($collection as $r) {
                $dateStr = isset($r->shift_date) ? (is_string($r->shift_date) ? $r->shift_date : $r->shift_date->format('Y-m-d')) : null;
                $dateFormatted = $dateStr ? \Carbon\Carbon::parse($dateStr)->format('d-M-Y') : '-';
                
                $empId = $r->emp_id;
                $empName = $r->emp_name ?? ($r->employee ? $r->employee->name : '-');
                $shiftName = $r->shift ? $r->shift->shift_name : '-';
                $checkIn = $r->check_in ? \Carbon\Carbon::parse($r->check_in)->format('h:i A') : '-';
                
                $checkOut = '-';
                if ($r->check_in) {
                    $isOnBreak = optional($r)->breaks ? $r->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time) : false;
                    $checkOut = $isOnBreak ? 'On Break' : ($r->check_out ? \Carbon\Carbon::parse($r->check_out)->format('h:i A') : 'Active');
                }
                
                $workedHoursString = '-';
                if ($r->check_in) {
                    $stats = calculate_break_stats($r->employee, null, $r->shift_date, $r->id);
                    $finalWorkHours = formatMinutesToHours($stats['working_minutes']);
                    $totalAllocated = $stats['allowed_break_minutes'];
                    $spentBreak = $stats['total_spent_minutes'];
                    $exceeded = $stats['exceeded_minutes'];
                    $statusLine = ($exceeded > 0) ? "Exceeded by {$exceeded}m" : "Remaining: " . $stats['remaining_minutes'] . "m";
                    $workedHoursString = "Worked: {$finalWorkHours} (Break Spent: {$spentBreak}m / Allowed: {$totalAllocated}m) [{$statusLine}]";
                }
                
                // Get status label using generateStatusBadge
                $statusBadgeHtml = $this->generateStatusBadge($r, $allOffDaysForRows, $allLeavesForRows);
                $statusText = trim(preg_replace('/\s+/', ' ', strip_tags($statusBadgeHtml)));

                $pdfLogs[] = [
                    'shift_date' => $dateFormatted,
                    'employee_id' => 'AST-' . $empId,
                    'employee_name' => $empName,
                    'shift_name' => $shiftName,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'worked_hours' => $workedHoursString,
                    'status' => $statusText
                ];
            }
            return response()->json([
                'logs' => $pdfLogs,
                'stats' => [
                    'total_worked_hours' => round($totWorkedMinutes / 60, 2),
                    'total_late_minutes' => $totLate,
                    'count_present' => $countPresent,
                    'count_absent' => $countAbsent,
                    'count_halfday' => $countHalfDay,
                    'count_late' => $countLate,
                    'total_staff' => $totalAccessibleEmployees,
                    'total_exceeded_seconds' => $totalExceededSeconds ?? 0,
                    'total_exceeded_days' => $totalExceededDays ?? 0,
                    'total_exceeded_formatted' => $totalExceededFormatted ?? '00 hours 00 minutes 00 seconds',
                    'exceeded_days' => $exceededDays ?? [],
                    'total_break_formatted' => $totalBreakFormatted ?? '0h 0m',
                ]
            ]);
        }

        return \Yajra\DataTables\Facades\DataTables::of($collection)
            ->addColumn('shift_date', function($r) {
                $date = isset($r->shift_date) ? (is_string($r->shift_date) ? $r->shift_date : $r->shift_date->format('Y-m-d')) : '-';
                return $date ? \Carbon\Carbon::parse($date)->format('d-M-Y') : '-';
            })
            ->addColumn('employee_id', fn($r) => 'AST‑' . $r->emp_id)
            ->addColumn('employee_name', fn($r) => $r->emp_name ?? $r->employee->name ?? '-')
            ->addColumn('profile_pic_url', function($r) {
                $pic = $r->profile_pic ?? optional($r->employee)->profile_pic;
                return $pic && \Illuminate\Support\Facades\Storage::disk('public')->exists($pic) ? \Illuminate\Support\Facades\Storage::disk('public')->url($pic) : null;
            })
            ->addColumn('shift_name', function($r) {
                return $r->shift->shift_name ?? '-';
            })
            ->addColumn('check_in', fn($r) => $r->check_in ? \Carbon\Carbon::parse($r->check_in)->format('h:i A') : '-')
            ->addColumn('check_out', function ($r) {
                if (!$r->check_in) return '-';
                $isOnBreak = optional($r)->breaks ? $r->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time) : false;
                if ($isOnBreak) {
                    return '<span class="saas-status-badge break animate-pulse"><i class="fas fa-mug-hot mr-1"></i> On Break</span>';
                }
                return $r->check_out ? \Carbon\Carbon::parse($r->check_out)->format('h:i A') : working_badge();
            })
            ->addColumn('worked_hours', function($r) use ($timezone, $now) {
                if (!$r->check_in) return '-';
                
                $netMinutes = calculate_net_minutes($r, $timezone, $now);
                $hours = floor($netMinutes / 60);
                $mins = $netMinutes % 60;
                $netDuration = "{$hours}h {$mins}m";
                
                $stats = calculate_break_stats($r->employee, null, $r->shift_date, $r->id);
                $finalWorkHours = formatMinutesToHours($stats['working_minutes']);
                $totalAllocated = $stats['allowed_break_minutes'];
                $spentBreak = $stats['total_spent_minutes'];
                $exceeded = $stats['exceeded_minutes'];
                $remaining = $stats['remaining_minutes'];

                $statusHtml = ($exceeded > 0) 
                    ? "<span class='text-danger fw-bold'>Exc: {$exceeded}m</span>" 
                    : "<span class='text-success fw-bold'>Rem: {$remaining}m</span>";

                return "
                    <div class='duration-hub'>
                        <div class='net-time'>{$finalWorkHours}</div>
                        <div class='break-intelligence-v2'>
                            <div class='stat-line'>Total: <span class='val'>{$totalAllocated}m</span></div>
                            <div class='stat-line'>Spent: <span class='val'>{$spentBreak}m</span></div>
                            <div class='stat-line'>{$statusHtml}</div>
                        </div>
                    </div>
                ";
            })
            ->addColumn('status_badge', function ($r) use ($allOffDaysForRows, $allLeavesForRows) {
                // If the check_in is missing, either it's a dummy row or just absent
                if (!$r->check_in && (!isset($r->status) || $r->status !== 'Present')) {
                    $shiftDateStr = is_string($r->shift_date) ? $r->shift_date : $r->shift_date->format('Y-m-d');
                    
                    // Check for approved_leave tag (set during collection build) first
                    $leave = isset($r->approved_leave) && $r->approved_leave ? $r->approved_leave : null;
                    
                    // Fallback: check from the pre-fetched leaves collection
                    if (!$leave) {
                        $leave = $allLeavesForRows->get($r->emp_id, collect())->first(function($l) use ($shiftDateStr) {
                            return $shiftDateStr >= $l->start_date->format('Y-m-d') && $shiftDateStr <= $l->end_date->format('Y-m-d');
                        });
                    }
                    
                    if ($leave) {
                        $typeLabel = ($leave->day_type === 'full_day') ? 'Full Day' : 'Half Day';
                        return $leave->status === 'Approved' 
                            ? '<span class="saas-status-badge leave"><i class="fas fa-plane-departure mr-1"></i>Approved (' . $typeLabel . ')</span>'
                            : '<span class="saas-status-badge leave-pending"><i class="fas fa-hourglass-half mr-1"></i>Pending (' . $typeLabel . ')</span>';
                    }

                    // Check for upcoming joining date
                    if (isset($r->employee) && $r->employee->joining_date && \Carbon\Carbon::parse($shiftDateStr)->lt(\Carbon\Carbon::parse($r->employee->joining_date)->startOfDay())) {
                        return '<span class="saas-status-badge upcoming"><i class="fas fa-clock mr-1"></i>Upcoming</span>';
                    }

                    // Check for holidays / off days
                    $teamId = $r->employee->team_id ?? (is_object($r) && isset($r->team_id) ? $r->team_id : null);
                    $offDay = $allOffDaysForRows->first(function($o) use ($shiftDateStr, $teamId) {
                        $applies = $shiftDateStr >= $o->start_date->format('Y-m-d') && $shiftDateStr <= $o->end_date->format('Y-m-d');
                        if (!$applies) return false;
                        return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                    });

                    if ($offDay) {
                        return $offDay->type === 'Holiday'
                            ? '<span class="saas-status-badge holiday"><i class="fas fa-star mr-1"></i>Holiday</span>'
                            : '<span class="saas-status-badge offday"><i class="fas fa-calendar-week mr-1"></i>Off Day</span>';
                    }
                    
                    // Check for weekend
                    $isWeekend = isset($r->is_weekend) ? $r->is_weekend : \Carbon\Carbon::parse($shiftDateStr)->isWeekend();
                    if ($isWeekend) {
                        return '<span class="saas-status-badge offday"><i class="fas fa-calendar-week mr-1"></i>Weekend</span>';
                    }
                    
                    return '<span class="saas-status-badge absent"><i class="fas fa-exclamation-triangle mr-1"></i>Absent (Unpaid)</span>';
                }

                if ($r->status === 'Present' || $r->check_in) {
                    if ($r->halfDay) {
                        return '<span class="saas-status-badge half-day"><i class="fas fa-adjust mr-1"></i>Half Day</span>';
                    }
                    if ($r->lateArrival) {
                        $duration = formatMinutesToHours($r->lateArrival->late_minutes);
                        return '<span class="badge badge-danger">Late</span> <small class="text-muted ms-1">(' . $duration . ')</small>';
                    }
                    return '<span class="badge badge-success">On Time</span>';
                }

                return '<span class="badge badge-secondary">Unknown</span>';
            })
            ->addColumn('action', function($r) {
                if (!$r->id) return '-'; // Do not return action button for dummy rows
                
                $viewBtn = ($r->status === 'Present') ? 
                    '<button class="btn-saas-action view-details" data-id="' . $r->id . '" title="View Intelligence">
                        <i class="fas fa-eye"></i>
                    </button>' : '';

                $editBtn = '';
                if (auth()->user()->can('manage-attendance')) {
                    $editBtn = '<button class="btn-saas-action edit-attendance" data-id="' . $r->id . '" title="Edit Log">
                                    <i class="fas fa-edit"></i>
                                </button>';
                }

                return '<div class="d-flex gap-2 justify-content-end">' . 
                            $viewBtn . 
                            $editBtn .
                        '</div>';
            })
            ->rawColumns(['check_out', 'status_badge', 'action', 'worked_hours'])
            ->with([
                'total_worked_hours' => round($totWorkedMinutes / 60, 2),
                'total_late_minutes' => $totLate,
                'count_present' => $countPresent,
                'count_absent' => $countAbsent,
                'count_halfday' => $countHalfDay,
                'count_late' => $countLate,
                'total_staff' => $totalAccessibleEmployees,
                'total_exceeded_seconds' => $totalExceededSeconds,
                'total_exceeded_days' => $totalExceededDays,
                'total_exceeded_formatted' => $totalExceededFormatted,
                'exceeded_days' => $exceededDays ?? [],
                'total_break_formatted' => $totalBreakFormatted ?? '0h 0m',
            ])
            ->make(true);
    }

    public function logsData(Request $request)
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $now = now($timezone);

        // 1. Determine the target date range
        $targetDate = null;
        $fromDate = null;
        $toDate = null;

        if ($request->filled('date_range')) {
            $range = explode(' - ', $request->date_range);
            if (count($range) == 2) {
                $fromDate = trim($range[0]);
                $toDate = trim($range[1]);
                if ($fromDate === $toDate) {
                    $targetDate = $fromDate;
                }
            }
        } else {
            // Default to today's shift date
            $targetDate = Carbon::today($timezone)->toDateString();
            $fromDate = $targetDate;
            $toDate = $targetDate;
        }

        // ⚙️ Performance Optimization: High-precision pre-fetching for DataTable rows & stats
        // Fetching leaves and off-days for the active range to prevent N+1 hits
        $allOffDaysForRows = \App\Models\CompanyOffDay::with('teams')
            ->where(function($q) use ($fromDate, $toDate) {
                $q->where(function($q2) use ($fromDate, $toDate) {
                    $q2->whereBetween('start_date', [$fromDate, $toDate])
                       ->orWhereBetween('end_date', [$fromDate, $toDate]);
                })->orWhere(function($q3) use ($fromDate, $toDate) {
                    $q3->whereDate('start_date', '<=', $fromDate)
                       ->whereDate('end_date', '>=', $toDate);
                });
            })->get();

        $allLeavesForRows = \App\Models\Leave::whereIn('status', ['Approved', 'Pending'])
            ->where(function($q) use ($fromDate, $toDate) {
                $q->whereDate('start_date', '<=', $toDate)
                  ->whereDate('end_date', '>=', $fromDate);
            })->get()->groupBy('employee_id');
        // Note: Leave model uses 'employee_id', Attendance uses 'emp_id' - both contain the same Employee ID values

        if (!$targetDate) {
            return $this->logsDataRange($request, $fromDate, $toDate, $timezone, $now, $allOffDaysForRows, $allLeavesForRows);
        }

        // 0. Authorization Scoping (Smart Filter) - Include Resigned for selection but label them
        $employees = Employee::accessible()->orderBy('name')->get(['id', 'name', 'resign_date']);
        $employees->transform(function($emp) {
            if ($emp->resign_date) {
                $emp->name = $emp->name . ' (Resigned)';
            }
            return $emp;
        });

        // 2. Base Query
        if ($targetDate) {
            // Defaults to only active employees on target date UNLESS specific employee filtered
            $query = Employee::accessible();
            $query->whereDate('employees.joining_date', '<=', $targetDate);
            $query->where(function($q) use ($targetDate) {
                $q->whereNull('employees.resign_date')
                  ->orWhereDate('employees.resign_date', '>', $targetDate);
            });

            $query->leftJoin('attendances', function($join) use ($targetDate) {
                    $join->on('employees.id', '=', 'attendances.emp_id')
                         ->whereDate('attendances.shift_date', '=', $targetDate);
                })
                ->with(['attendances' => function($q) use ($targetDate) {
                    $q->whereDate('shift_date', $targetDate)
                      ->with(['shift', 'lateArrival', 'halfDay', 'breaks', 'checkedOutBy']);
                }])
                ->select([
                    'employees.id', 
                    'employees.name as emp_name',
                    'employees.profile_pic',
                    'employees.team_id',
                    'employees.joining_date',
                    'employees.resign_date',
                    'attendances.id as attendance_id', 
                    'attendances.check_in',
                    'attendances.check_out',
                    'attendances.status',
                    'attendances.shift_id',
                    'attendances.shift_date'
                ]);
        } else {
            $query = Attendance::accessible()
                ->join('employees', 'attendances.emp_id', '=', 'employees.id')
                ->select('attendances.*')
                ->with(['employee:id,name,profile_pic,team_id', 'shift:id,shift_name', 'lateArrival', 'halfDay', 'breaks', 'checkedOutBy'])
                ->whereBetween('attendances.shift_date', [$fromDate, $toDate]);
        }

        // Apply filters to $query
        if ($request->filled('department_id')) {
            $teamIds = \App\Models\Team::where('department_id', $request->department_id)->pluck('id');
            if ($targetDate) {
                $query->whereIn('employees.team_id', $teamIds);
            } else {
                $query->whereHas('employee', function($q) use ($teamIds) {
                    $q->whereIn('team_id', $teamIds);
                });
            }
        }

        if ($request->filled('employee_id')) {
            if ($targetDate) {
                $query->where('employees.id', $request->employee_id);
            } else {
                $query->where('attendances.emp_id', $request->employee_id);
            }
        }

        if ($request->filled('multi_shift_ids') || $request->filled('shift_id')) {
            $ids = $request->multi_shift_ids ?: [$request->shift_id];
            if ($targetDate) {
                $query->where(function($q) use ($ids) {
                    $q->whereIn('attendances.shift_id', $ids)
                      ->orWhere(function($q2) use ($ids) {
                          $q2->whereNull('attendances.shift_id')
                             ->whereHas('currentShiftAssignment', function($q3) use ($ids) {
                                 $q3->whereIn('shift_id', $ids);
                             });
                      });
                });
            } else {
                $query->whereIn('attendances.shift_id', $ids);
            }
        }

        // Clone BEFORE status filter for Total Staff count (should always reflect full team size)
        $totalStaffQuery = clone $query;

        // Apply Status Filter to $query
        if ($request->filled('status')) {
            $status = $request->status;

            if ($targetDate) {
                if ($status === 'Half Day') {
                    $query->whereHas('attendances', function($q) use ($targetDate) {
                        $q->whereDate('shift_date', $targetDate)->whereHas('halfDay');
                    });
                } elseif ($status === 'Late') {
                    $query->whereHas('attendances', function($q) use ($targetDate) {
                        $q->whereDate('shift_date', $targetDate)->whereHas('lateArrival');
                    });
                } elseif ($status === 'Present') {
                    $query->where(function($q) {
                        $q->where('attendances.status', 'Present')
                          ->orWhereNotNull('attendances.check_in');
                    })
                    ->whereDoesntHave('attendances', function($q) use ($targetDate) {
                        $q->whereDate('shift_date', $targetDate)
                          ->where(function($qq) {
                              $qq->whereHas('halfDay')->orWhereHas('lateArrival');
                          });
                    });
                } elseif ($status === 'Absent (Paid)') {
                    $query->whereHas('leaves', function($l) use ($targetDate) {
                        $l->where('status', 'Approved')
                          ->whereDate('start_date', '<=', $targetDate)
                          ->whereDate('end_date', '>=', $targetDate);
                    })
                    ->where(function($q) {
                        $q->whereNull('attendances.status')
                          ->orWhere(function($qq) {
                              $qq->where('attendances.status', '!=', 'Present')
                                 ->whereNull('attendances.check_in');
                          });
                    });
                } elseif ($status === 'Absent (Unpaid)') {
                    $query->where(function($q) {
                        $q->whereNull('attendances.status')
                          ->orWhere(function($qq) {
                              $qq->where('attendances.status', '!=', 'Present')
                                 ->whereNull('attendances.check_in');
                          });
                    })
                    ->whereDoesntHave('leaves', function($l) use ($targetDate) {
                        $l->where('status', 'Approved')
                          ->whereDate('start_date', '<=', $targetDate)
                          ->whereDate('end_date', '>=', $targetDate);
                    })
                    ->where(function($q) use ($targetDate) {
                        $q->whereNotExists(function($subquery) use ($targetDate) {
                            $subquery->select(DB::raw(1))
                                ->from('company_off_days')
                                ->leftJoin('holiday_team', 'company_off_days.id', '=', 'holiday_team.holiday_id')
                                ->whereDate('company_off_days.start_date', '<=', $targetDate)
                                ->whereDate('company_off_days.end_date', '>=', $targetDate)
                                ->where(function($nested) {
                                    $nested->whereNull('holiday_team.team_id')
                                           ->orWhereColumn('holiday_team.team_id', 'employees.team_id');
                                });
                        });
                    });
                } elseif ($status === 'Break Exceeded') {
                    $query->whereHas('attendances', function($q) use ($targetDate) {
                        $q->whereDate('shift_date', $targetDate)->whereNotNull('check_in');
                    });
                } else {
                    $query->where('attendances.status', $status);
                }
            } else {
                if ($status === 'Half Day') {
                    $query->whereHas('halfDay');
                } elseif ($status === 'Late') {
                    $query->whereHas('lateArrival');
                } elseif ($status === 'Break Exceeded') {
                    $query->whereNotNull('attendances.check_in');
                } elseif ($status === 'Present') {
                    $query->where(function($q) {
                        $q->where('attendances.status', 'Present')
                          ->orWhereNotNull('attendances.check_in');
                    })
                    ->whereDoesntHave('halfDay')
                    ->whereDoesntHave('lateArrival');
                } elseif ($status === 'Absent (Paid)') {
                    $query->where(function($q) {
                        $q->where('attendances.status', 'Absent')
                          ->orWhere(function($qq) {
                              $qq->where('attendances.status', '!=', 'Present')
                                 ->whereNull('attendances.check_in');
                          });
                    })
                    ->whereHas('employee.leaves', function($l) {
                        $l->where('status', 'Approved')
                          ->whereColumn('leaves.start_date', '<=', 'attendances.shift_date')
                          ->whereColumn('leaves.end_date', '>=', 'attendances.shift_date');
                    });
                } elseif ($status === 'Absent (Unpaid)') {
                    $query->where(function($q) {
                        $q->where('attendances.status', 'Absent')
                          ->orWhere(function($qq) {
                              $qq->where('attendances.status', '!=', 'Present')
                                 ->whereNull('attendances.check_in');
                          });
                    })
                    ->whereDoesntHave('employee.leaves', function($l) {
                        $l->where('status', 'Approved')
                          ->whereColumn('leaves.start_date', '<=', 'attendances.shift_date')
                          ->whereColumn('leaves.end_date', '>=', 'attendances.shift_date');
                    });
                } else {
                    $query->where('attendances.status', $status);
                }
            }
        }

        // 3. Clone for Stats Calculation (including status filter so stats match filtered rows perfectly)
        $statsQuery = clone $query;

        // 4. Calculate Stats Tally
        $totWorkedMinutes = 0;
        $totLate = 0;
        $countPresent = 0;
        $countAbsent = 0;
        $countHalfDay = 0;
        $countLate = 0;
        // Total Staff = full team size (computed from pre-status-filter query)
        $totalAccessibleEmployees = $targetDate ? $totalStaffQuery->count() : Employee::accessible()->whereNull('resign_date')->count();

        if ($targetDate) {
            $statsEmployees = $statsQuery->get();
            $totalExceededSeconds = 0;
            $totalExceededDays = 0;
            $exceededDays = [];

            foreach ($statsEmployees as $emp) {
                $att = $emp->attendances->first();
                $empId = $emp->id;
                $teamId = $emp->team_id;

                if ($att && ($att->status === 'Present' || $att->check_in)) {
                    if ($att->halfDay) {
                        $countHalfDay++;
                    } elseif ($att->lateArrival) {
                        $countLate++;
                        $totLate += $att->lateArrival->late_minutes;
                    } else {
                        $countPresent++;
                    }
                    $totWorkedMinutes += calculate_net_minutes($att, $timezone, $now);

                    // Break exceeded logic (half-day uses effective allowance)
                    $allowed = get_effective_break_minutes((int) $empId, is_string($att->shift_date) ? $att->shift_date : $att->shift_date->format('Y-m-d'));
                    $completedSumSeconds = $att->breaks->where('type', 'General')->whereNotNull('end_time')->sum(function($b) {
                        $mins = $b->spent_minutes ?? (int) ceil(\Carbon\Carbon::parse($b->created_at)->diffInMinutes(\Carbon\Carbon::parse($b->end_time)));
                        return min($mins, 120);
                    }) * 60;
                    
                    $activeSeconds = 0;
                    $activeBreak = $att->breaks->where('type', 'General')->whereNull('end_time')->first();
                    if ($activeBreak) {
                        $activeSeconds = $activeBreak->calculateBreakTimeInSeconds();
                        if ($activeSeconds > (120 * 60)) {
                            $activeSeconds = 120 * 60;
                        }
                    }
                    
                    $totalSpentSeconds = $completedSumSeconds + $activeSeconds;
                    $allowedSeconds = $allowed * 60;
                    $exceededSeconds = max($totalSpentSeconds - $allowedSeconds, 0);
                    
                    if ($exceededSeconds > 0) {
                        $totalExceededSeconds += $exceededSeconds;
                        $totalExceededDays++;

                        $h = floor($exceededSeconds / 3600);
                        $m = floor(($exceededSeconds % 3600) / 60);
                        $s = $exceededSeconds % 60;
                        $exceededFormatted = sprintf('%02d hours %02d minutes %02d seconds', $h, $m, $s);
                        $exceededDays[] = [
                            'date' => \Carbon\Carbon::parse($targetDate)->format('d-M-Y'),
                            'time' => $exceededFormatted,
                        ];
                    }
                } else {
                    $offDay = $allOffDaysForRows->first(function($o) use ($targetDate, $teamId) {
                        $applies = $targetDate >= $o->start_date->format('Y-m-d') && $targetDate <= $o->end_date->format('Y-m-d');
                        if (!$applies) return false;
                        return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                    });

                    if (!$offDay) {
                        $countAbsent++;
                    }
                }
            }

            $h = floor($totalExceededSeconds / 3600);
            $m = floor(($totalExceededSeconds % 3600) / 60);
            $s = $totalExceededSeconds % 60;
            $totalExceededFormatted = sprintf('%02d hours %02d minutes %02d seconds', $h, $m, $s);
        } else {
            // Range View
            $statsAttendances = $statsQuery->get();
            $totalAccessibleEmployees = Employee::accessible()->whereNull('resign_date')->count();

            foreach ($statsAttendances as $att) {
                if ($att->status === 'Present' || $att->check_in) {
                    if ($att->halfDay) {
                        $countHalfDay++;
                    } elseif ($att->lateArrival) {
                        $countLate++;
                        $totLate += $att->lateArrival->late_minutes;
                    } else {
                        $countPresent++;
                    }
                    $totWorkedMinutes += calculate_net_minutes($att, $timezone, $now);
                }
            }

            // Calculate range absents daily
            $startDate = Carbon::parse($fromDate);
            $endDate = Carbon::parse($toDate);
            
            // Build base list of employees matching other filters (dept, employee, shift)
            // But exclude status filter for global absent logic unless range status filter is active
            $activeEmpQuery = Employee::accessible();
            if ($request->filled('department_id')) {
                $activeEmpQuery->whereIn('employees.team_id', \App\Models\Team::where('department_id', $request->department_id)->pluck('id'));
            }
            if ($request->filled('employee_id')) {
                $activeEmpQuery->where('employees.id', $request->employee_id);
            }
            if ($request->filled('multi_shift_ids') || $request->filled('shift_id')) {
                $ids = $request->multi_shift_ids ?: [$request->shift_id];
                $activeEmpQuery->whereHas('currentShiftAssignment', function($q) use ($ids) {
                    $q->whereIn('shift_id', $ids);
                });
            }
            $activeEmployees = $activeEmpQuery->get(['id', 'team_id', 'joining_date', 'resign_date']);

            // Group by employee and shift_date from the loaded attendances
            $attendanceMap = $statsAttendances->groupBy('emp_id')->map(function($group) {
                return $group->pluck('shift_date')->map(function($date) {
                    return $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
                })->toArray();
            })->toArray();

            // Run absent checks daily
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $curDateStr = $date->toDateString();
                foreach ($activeEmployees as $emp) {
                    if ($emp->joining_date && Carbon::parse($curDateStr)->lt(Carbon::parse($emp->joining_date)->startOfDay())) {
                        continue;
                    }
                    if ($emp->resign_date && Carbon::parse($curDateStr)->gt(Carbon::parse($emp->resign_date)->endOfDay())) {
                        continue;
                    }
                    
                    $checkedIn = isset($attendanceMap[$emp->id]) && in_array($curDateStr, $attendanceMap[$emp->id]);
                    if (!$checkedIn) {
                        $empId = $emp->id;
                        $teamId = $emp->team_id;

                        $offDay = $allOffDaysForRows->first(function($o) use ($curDateStr, $teamId) {
                            $applies = $curDateStr >= $o->start_date->format('Y-m-d') && $curDateStr <= $o->end_date->format('Y-m-d');
                            if (!$applies) return false;
                            return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                        });

                        if (!$offDay) {
                            $countAbsent++;
                        }
                    }
                }
            }

            // Adjust stats counts if a status filter is set for range
            if ($request->filled('status')) {
                $status = $request->status;
                if ($status !== 'Present') $countPresent = 0;
                if ($status !== 'Late') $countLate = 0;
                if ($status !== 'Half Day') $countHalfDay = 0;
                if ($status !== 'Absent (Unpaid)' && $status !== 'Absent (Paid)') $countAbsent = 0;
            }
        }

        // 5. Return DataTables
        if ($request->status === 'Break Exceeded') {
            $collection = $query->get();
            $collection = $collection->filter(function($r) use ($targetDate) {
                $att = $targetDate ? $r->attendances->first() : $r;
                if (!$att || !$att->check_in) return false;
                
                $empId = $targetDate ? $r->id : $r->emp_id;
                $shiftDateStr = is_string($att->shift_date) ? $att->shift_date : $att->shift_date->format('Y-m-d');
                $allowed = get_effective_break_minutes((int) $empId, $shiftDateStr);
                
                $completedSumSeconds = $att->breaks->where('type', 'General')->whereNotNull('end_time')->sum(function($b) {
                    $mins = $b->spent_minutes ?? (int) ceil(\Carbon\Carbon::parse($b->created_at)->diffInMinutes(\Carbon\Carbon::parse($b->end_time)));
                    return min($mins, 120);
                }) * 60;
                
                $activeSeconds = 0;
                $activeBreak = $att->breaks->where('type', 'General')->whereNull('end_time')->first();
                if ($activeBreak) {
                    $activeSeconds = $activeBreak->calculateBreakTimeInSeconds();
                    if ($activeSeconds > (120 * 60)) {
                        $activeSeconds = 120 * 60;
                    }
                }
                
                $totalSpentSeconds = $completedSumSeconds + $activeSeconds;
                return $totalSpentSeconds > ($allowed * 60);
            });

            // Adjust stats counts specifically for filter
            $totWorkedMinutes = 0;
            $totLate = 0;
            $countPresent = 0;
            $countAbsent = 0;
            $countHalfDay = 0;
            $countLate = 0;
            $totalExceededSeconds = 0;
            $totalExceededDays = 0;

            foreach ($collection as $r) {
                $att = $targetDate ? $r->attendances->first() : $r;
                if ($att && $att->check_in) {
                    $totWorkedMinutes += calculate_net_minutes($att, $timezone, $now);
                    
                    $empId = $targetDate ? $r->id : $r->emp_id;
                    $shiftDateStr = is_string($att->shift_date) ? $att->shift_date : $att->shift_date->format('Y-m-d');
                    $allowed = get_effective_break_minutes((int) $empId, $shiftDateStr);
                    $completedSumSeconds = $att->breaks->where('type', 'General')->whereNotNull('end_time')->sum(function($b) {
                        $mins = $b->spent_minutes ?? (int) ceil(\Carbon\Carbon::parse($b->created_at)->diffInMinutes(\Carbon\Carbon::parse($b->end_time)));
                        return min($mins, 120);
                    }) * 60;
                    
                    $activeSeconds = 0;
                    $activeBreak = $att->breaks->where('type', 'General')->whereNull('end_time')->first();
                    if ($activeBreak) {
                        $activeSeconds = $activeBreak->calculateBreakTimeInSeconds();
                        if ($activeSeconds > (120 * 60)) {
                            $activeSeconds = 120 * 60;
                        }
                    }
                    
                    $totalSpentSeconds = $completedSumSeconds + $activeSeconds;
                    $allowedSeconds = $allowed * 60;
                    $exceededSeconds = max($totalSpentSeconds - $allowedSeconds, 0);
                    
                    if ($exceededSeconds > 0) {
                        $totalExceededSeconds += $exceededSeconds;
                        $totalExceededDays++;
                    }
                }
            }

            $h = floor($totalExceededSeconds / 3600);
            $m = floor(($totalExceededSeconds % 3600) / 60);
            $s = $totalExceededSeconds % 60;
            $totalExceededFormatted = sprintf('%02d hours %02d minutes %02d seconds', $h, $m, $s);

            return DataTables::of($collection)
                ->addColumn('shift_date', function($r) use ($targetDate) {
                    $date = $targetDate ?: (isset($r->shift_date) ? $r->shift_date->format('Y-m-d') : '-');
                    return $date ? Carbon::parse($date)->format('d-M-Y') : '-';
                })
                ->addColumn('employee_id', fn($r) => 'AST‑' . ($targetDate ? $r->id : $r->emp_id))
                ->addColumn('employee_name', fn($r) => $targetDate ? ($r->emp_name ?? $r->name) : ($r->employee->name ?? '-'))
                ->addColumn('profile_pic_url', function($r) use ($targetDate) {
                    $pic = $targetDate ? $r->profile_pic : optional($r->employee)->profile_pic;
                    return $pic && Storage::disk('public')->exists($pic) ? Storage::disk('public')->url($pic) : null;
                })
                ->addColumn('shift_name', function($r) use ($targetDate) {
                    $att = $targetDate ? $r->attendances->first() : $r;
                    return $att->shift->shift_name ?? '-';
                })
                ->addColumn('check_in', function($r) use ($targetDate) {
                    $att = $targetDate ? $r->attendances->first() : $r;
                    return $att && $att->check_in ? Carbon::parse($att->check_in)->format('h:i A') : '-';
                })
                ->addColumn('check_out', function ($r) use ($targetDate) {
                    $attModel = $targetDate ? $r->attendances->first() : $r;
                    if (!$attModel || !$attModel->check_in) return '-';
                    $isOnBreak = optional($attModel)->breaks ? $attModel->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time) : false;
                    if ($isOnBreak) {
                        return '<span class="saas-status-badge break animate-pulse"><i class="fas fa-mug-hot mr-1"></i> On Break</span>';
                    }
                    return $attModel->check_out ? Carbon::parse($attModel->check_out)->format('h:i A') : working_badge();
                })
                ->addColumn('worked_hours', function($r) use ($targetDate) {
                    $attModel = $targetDate ? $r->attendances->first() : $r;
                    if (!$attModel || !$attModel->check_in) return '-';
                    $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
                    $netMinutes = calculate_net_minutes($attModel, $timezone, now($timezone));
                    $hours = floor($netMinutes / 60);
                    $mins = $netMinutes % 60;
                    $stats = calculate_break_stats($attModel->employee, null, $attModel->shift_date, $attModel->id);
                    $finalWorkHours = formatMinutesToHours($stats['working_minutes']);
                    $totalAllocated = $stats['allowed_break_minutes'];
                    $spentBreak = $stats['total_spent_minutes'];
                    $exceeded = $stats['exceeded_minutes'];
                    $remaining = $stats['remaining_minutes'];
                    $statusHtml = ($exceeded > 0) 
                        ? "<span class='text-danger fw-bold'>Exc: {$exceeded}m</span>" 
                        : "<span class='text-success fw-bold'>Rem: {$remaining}m</span>";
                    return "
                        <div class='duration-hub'>
                            <div class='net-time'>{$finalWorkHours}</div>
                            <div class='break-intelligence-v2'>
                                <div class='stat-line'>Total: <span class='val'>{$totalAllocated}m</span></div>
                                <div class='stat-line'>Spent: <span class='val'>{$spentBreak}m</span></div>
                                <div class='stat-line'>{$statusHtml}</div>
                            </div>
                        </div>
                    ";
                })
                ->addColumn('status_badge', function ($r) use ($targetDate, $allOffDaysForRows, $allLeavesForRows) {
                    $attModel = $targetDate ? $r->attendances->first() : $r;
                    return $this->generateStatusBadge($attModel, $allOffDaysForRows, $allLeavesForRows);
                })
                ->addColumn('action', function($r) use ($targetDate) {
                    $id = $targetDate ? $r->attendance_id : $r->id;
                    if (!$id) return '-';
                    $attModel = $targetDate ? $r->attendances->first() : $r;
                    $viewBtn = ($attModel && $attModel->status === 'Present') ? 
                        '<button class="btn-saas-action view-details" data-id="' . $id . '" title="View Intelligence">
                            <i class="fas fa-eye"></i>
                        </button>' : '';
                    $editBtn = '';
                    if (auth()->user()->can('manage-attendance')) {
                        $editBtn = '<button class="btn-saas-action edit-attendance" data-id="' . $id . '" title="Edit Log">
                                        <i class="fas fa-edit"></i>
                                    </button>';
                    }
                    return '<div class="d-flex gap-2 justify-content-end">' . $viewBtn . $editBtn . '</div>';
                })
                ->rawColumns(['check_out', 'status_badge', 'action', 'worked_hours'])
                ->with([
                    'total_worked_hours' => round($totWorkedMinutes / 60, 2),
                    'total_late_minutes' => $totLate,
                    'count_present' => $countPresent,
                    'count_absent' => $countAbsent,
                    'count_halfday' => $countHalfDay,
                    'count_late' => $countLate,
                    'total_staff' => $totalAccessibleEmployees,
                    'total_exceeded_seconds' => $totalExceededSeconds,
                    'total_exceeded_days' => $totalExceededDays,
                    'total_exceeded_formatted' => $totalExceededFormatted,
                ])
                ->make(true);
        }

        if ($request->filled('export_pdf')) {
            $collection = $query->get();
            $pdfLogs = [];
            foreach ($collection as $r) {
                $dateStr = $targetDate;
                $dateFormatted = $dateStr ? \Carbon\Carbon::parse($dateStr)->format('d-M-Y') : '-';
                
                $empId = $r->id;
                $empName = $r->emp_name ?? $r->name;
                
                $att = $r->attendances->first();
                $shiftName = ($att && $att->shift) ? $att->shift->shift_name : '-';
                $checkIn = ($att && $att->check_in) ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '-';
                
                $checkOut = '-';
                if ($att && $att->check_in) {
                    $isOnBreak = optional($att)->breaks ? $att->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time) : false;
                    $checkOut = $isOnBreak ? 'On Break' : ($att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : 'Active');
                }
                
                $workedHoursString = '-';
                if ($att && $att->check_in) {
                    $stats = calculate_break_stats($r, null, $att->shift_date, $att->id);
                    $finalWorkHours = formatMinutesToHours($stats['working_minutes']);
                    $totalAllocated = $stats['allowed_break_minutes'];
                    $spentBreak = $stats['total_spent_minutes'];
                    $exceeded = $stats['exceeded_minutes'];
                    $statusLine = ($exceeded > 0) ? "Exceeded by {$exceeded}m" : "Remaining: " . $stats['remaining_minutes'] . "m";
                    $workedHoursString = "Worked: {$finalWorkHours} (Break Spent: {$spentBreak}m / Allowed: {$totalAllocated}m) [{$statusLine}]";
                }
                
                // Get status label using generateStatusBadge / custom check
                if (!$att) {
                    $teamId = $r->team_id;
                    $leave = $allLeavesForRows->get($empId, collect())->first(function($l) use ($dateStr) {
                        return $dateStr >= $l->start_date->format('Y-m-d') && $dateStr <= $l->end_date->format('Y-m-d');
                    });
                    if ($leave) {
                        $typeLabel = ($leave->day_type === 'full_day') ? 'Full Day' : 'Half Day';
                        $statusText = $leave->status === 'Approved' ? 'Approved Leave (' . $typeLabel . ')' : 'Pending Leave (' . $typeLabel . ')';
                    } elseif ($r->joining_date && Carbon::parse($dateStr)->lt(Carbon::parse($r->joining_date)->startOfDay())) {
                        $statusText = 'Upcoming Employee';
                    } else {
                        $offDay = $allOffDaysForRows->first(function($o) use ($dateStr, $teamId) {
                            $applies = $dateStr >= $o->start_date && $dateStr <= $o->end_date;
                            if (!$applies) return false;
                            return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                        });

                        if ($offDay) {
                            $statusText = $offDay->type === 'Holiday' ? 'Holiday' : 'Off Day';
                        } else {
                            $statusText = 'Absent (Unpaid)';
                        }
                    }
                } else {
                    $statusBadgeHtml = $this->generateStatusBadge($att, $allOffDaysForRows, $allLeavesForRows);
                    $statusText = trim(preg_replace('/\s+/', ' ', strip_tags($statusBadgeHtml)));
                }

                $pdfLogs[] = [
                    'shift_date' => $dateFormatted,
                    'employee_id' => 'AST-' . $empId,
                    'employee_name' => $empName,
                    'shift_name' => $shiftName,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'worked_hours' => $workedHoursString,
                    'status' => $statusText
                ];
            }
            return response()->json([
                'logs' => $pdfLogs,
                'stats' => [
                    'total_worked_hours' => round($totWorkedMinutes / 60, 2),
                    'total_late_minutes' => $totLate,
                    'count_present' => $countPresent,
                    'count_absent' => $countAbsent,
                    'count_halfday' => $countHalfDay,
                    'count_late' => $countLate,
                    'total_staff' => $totalAccessibleEmployees,
                    'total_exceeded_seconds' => $totalExceededSeconds ?? 0,
                    'total_exceeded_days' => $totalExceededDays ?? 0,
                    'total_exceeded_formatted' => $totalExceededFormatted ?? '00 hours 00 minutes 00 seconds',
                    'exceeded_days' => $exceededDays ?? [],
                ]
            ]);
        }

        return DataTables::of($query)
            ->addColumn('shift_date', function($r) use ($targetDate) {
                $date = $targetDate ?: (isset($r->shift_date) ? $r->shift_date->format('Y-m-d') : '-');
                return $date ? Carbon::parse($date)->format('d-M-Y') : '-';
            })
            ->addColumn('employee_id', fn($r) => 'AST‑' . $r->id)
            ->addColumn('employee_name', fn($r) => $r->emp_name ?? $r->employee->name)
            ->addColumn('profile_pic_url', function($r) use ($targetDate) {
                $pic = $targetDate ? $r->profile_pic : optional($r->employee)->profile_pic;
                return $pic && Storage::disk('public')->exists($pic) ? Storage::disk('public')->url($pic) : null;
            })
            ->addColumn('shift_name', function($r) use ($targetDate) {
                if ($targetDate) {
                   $att = $r->attendances->first();
                   return $att->shift->shift_name ?? '-';
                }
                return $r->shift->shift_name ?? '-';
            })
            ->addColumn('check_in', fn($r) => $r->check_in ? Carbon::parse($r->check_in)->format('h:i A') : '-')
            ->addColumn('check_out', function ($r) use ($targetDate) {
                if (!$r->check_in) return '-';
                
                $attModel = $targetDate ? $r->attendances->first() : $r;
                $isOnBreak = optional($attModel)->breaks ? $attModel->breaks->contains(fn($b) => $b->type === 'General' && $b->start_time && !$b->end_time) : false;
                
                if ($isOnBreak) {
                    return '<span class="saas-status-badge break animate-pulse"><i class="fas fa-mug-hot mr-1"></i> On Break</span>';
                }

                if (!$r->check_out) {
                    return working_badge();
                }

                $checkoutHtml = Carbon::parse($r->check_out)->format('h:i A');
                if ($attModel && $attModel->checked_out_by) {
                    $adminName = optional($attModel->checkedOutBy)->name ?? 'Admin';
                    $checkoutHtml .= '<br><small class="text-muted" style="font-size: 0.7rem;">By: ' . e($adminName) . '</small>';
                }

                return $checkoutHtml;
            })
            ->addColumn('worked_hours', function($r) use ($targetDate) {
                $attModel = $targetDate ? $r->attendances->first() : $r;
                if (!$attModel || !$attModel->check_in) return '-';

                // Calculate Work & Break Intelligence
                $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
                // Note: calculate_net_minutes is optimized to use eager loaded relations
                $netMinutes = calculate_net_minutes($attModel, $timezone, now($timezone));
                $hours = floor($netMinutes / 60);
                $mins = $netMinutes % 60;
                $netDuration = "{$hours}h {$mins}m";

                // We keep the detailed stats calculation but it's now faster due to eager loads
                $stats = calculate_break_stats($attModel->employee, null, $attModel->shift_date, $attModel->id);
                
                $finalWorkHours = formatMinutesToHours($stats['working_minutes']);
                $totalAllocated = $stats['allowed_break_minutes'];
                $spentBreak = $stats['total_spent_minutes'];
                $exceeded = $stats['exceeded_minutes'];
                $remaining = $stats['remaining_minutes'];

                $statusHtml = ($exceeded > 0) 
                    ? "<span class='text-danger fw-bold'>Exc: {$exceeded}m</span>" 
                    : "<span class='text-success fw-bold'>Rem: {$remaining}m</span>";

                return "
                    <div class='duration-hub'>
                        <div class='net-time'>{$finalWorkHours}</div>
                        <div class='break-intelligence-v2'>
                            <div class='stat-line'>Total: <span class='val'>{$totalAllocated}m</span></div>
                            <div class='stat-line'>Spent: <span class='val'>{$spentBreak}m</span></div>
                            <div class='stat-line'>{$statusHtml}</div>
                        </div>
                    </div>
                ";
            })
            ->addColumn('status_badge', function ($r) use ($targetDate, $allOffDaysForRows, $allLeavesForRows) {
                $attModel = $targetDate ? $r->attendances->first() : $r;
                $shiftDate = $targetDate ?: (isset($r->shift_date) ? $r->shift_date->format('Y-m-d') : null);
                
                if (!$attModel) {
                    if ($shiftDate) {
                        $empId = $r->id;
                        $teamId = $r->team_id;

                        // Check memory-cache for leaves
                        $leave = $allLeavesForRows->get($empId, collect())->first(function($l) use ($shiftDate) {
                            return $shiftDate >= $l->start_date->format('Y-m-d') && $shiftDate <= $l->end_date->format('Y-m-d');
                        });

                        if ($leave) {
                            $typeLabel = ($leave->day_type === 'full_day') ? 'Full Day' : 'Half Day';
                            return $leave->status === 'Approved' 
                                ? '<span class="saas-status-badge leave"><i class="fas fa-plane-departure mr-1"></i>Approved (' . $typeLabel . ')</span>'
                                : '<span class="saas-status-badge leave-pending"><i class="fas fa-hourglass-half mr-1"></i>Pending (' . $typeLabel . ')</span>';
                        }

                        if ($r->joining_date && Carbon::parse($shiftDate)->lt(Carbon::parse($r->joining_date)->startOfDay())) {
                            return '<span class="saas-status-badge upcoming"><i class="fas fa-clock mr-1"></i>Upcoming</span>';
                        }

                        // Check memory-cache for off days
                        $offDay = $allOffDaysForRows->first(function($o) use ($shiftDate, $teamId) {
                            $applies = $shiftDate >= $o->start_date && $shiftDate <= $o->end_date;
                            if (!$applies) return false;
                            return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
                        });

                        if ($offDay) {
                            return $offDay->type === 'Holiday'
                                ? '<span class="saas-status-badge holiday"><i class="fas fa-star mr-1"></i>Holiday</span>'
                                : '<span class="saas-status-badge offday"><i class="fas fa-calendar-week mr-1"></i>Off Day</span>';
                        }
                    }
                    return '<span class="saas-status-badge absent"><i class="fas fa-exclamation-triangle mr-1"></i>Absent (Unpaid)</span>';
                }

                // Optimized: Pass local collections to status generator
                return $this->generateStatusBadge($attModel, $allOffDaysForRows, $allLeavesForRows);
            })
            ->addColumn('action', function($r) use ($targetDate) {
                $id = $targetDate ? $r->attendance_id : $r->id;
                if (!$id) return '-';
                
                $viewBtn = ($r->status === 'Present') ? 
                    '<button class="btn-saas-action view-details" data-id="' . $id . '" title="View Intelligence">
                        <i class="fas fa-eye"></i>
                    </button>' : '';

                $editBtn = '';
                if (auth()->user()->can('manage-attendance')) {
                    $editBtn = '<button class="btn-saas-action edit-attendance" data-id="' . $id . '" title="Edit Log">
                                    <i class="fas fa-edit"></i>
                                </button>';
                }

                return '<div class="d-flex gap-2 justify-content-end">' . 
                            $viewBtn . 
                            $editBtn .
                        '</div>';
            })
            ->rawColumns(['check_out', 'status_badge', 'action', 'worked_hours'])
            ->with([
                'total_worked_hours' => round($totWorkedMinutes / 60, 2),
                'total_late_minutes' => $totLate,
                'count_present' => $countPresent,
                'count_absent' => $countAbsent,
                'count_halfday' => $countHalfDay,
                'count_late' => $countLate,
                'total_staff' => $totalAccessibleEmployees,
                'total_exceeded_seconds' => $totalExceededSeconds ?? 0,
                'total_exceeded_days' => $totalExceededDays ?? 0,
                'total_exceeded_formatted' => $totalExceededFormatted ?? '00 hours 00 minutes 00 seconds',
                'exceeded_days' => $exceededDays ?? [],
            ])
            ->make(true);
    }

/**
 * Helper to keep the main controller clean with premium SAAS badges
 */
private function generateStatusBadge($r, $allOffDays = null, $allLeaves = null)
{
    $shiftDate = $r->shift_date instanceof \Carbon\Carbon ? $r->shift_date->toDateString() : \Carbon\Carbon::parse($r->shift_date)->toDateString();
    $teamId = $r->employee->team_id ?? null;

    // 🛑 High Priority: Holiday / Off Day Check (Optimized lookup)
    $offDay = null;
    if ($allOffDays) {
        $offDay = $allOffDays->first(function($o) use ($shiftDate, $teamId) {
            $applies = $shiftDate >= $o->start_date && $shiftDate <= $o->end_date;
            if (!$applies) return false;
            return $o->teams->isEmpty() || $o->teams->contains('id', $teamId);
        });
    } else {
        // Fallback for isolated calls
        $offDay = \App\Models\CompanyOffDay::where(function($q) use ($shiftDate) {
            $q->whereDate('start_date', '<=', $shiftDate)->whereDate('end_date', '>=', $shiftDate);
        })->first();
    }

    if ($offDay) {
        return $offDay->type === 'Holiday'
            ? '<span class="saas-status-badge holiday"><i class="fas fa-star mr-1"></i>Holiday</span>'
            : '<span class="saas-status-badge offday"><i class="fas fa-calendar-week mr-1"></i>Off Day</span>';
    }

    // 🛑 High Priority: Leave Check (Optimized lookup)
    $leave = null;
    if ($allLeaves) {
        $leave = $allLeaves->get($r->emp_id, collect())->first(function($l) use ($shiftDate) {
            return $shiftDate >= $l->start_date->format('Y-m-d') && $shiftDate <= $l->end_date->format('Y-m-d');
        });
    } else {
        // Fallback for isolated calls
        $leave = \App\Models\Leave::where('employee_id', $r->emp_id)
            ->whereIn('status', ['Approved', 'Pending'])
            ->whereDate('start_date', '<=', $shiftDate)
            ->whereDate('end_date', '>=', $shiftDate)
            ->orderByRaw("FIELD(status, 'Approved', 'Pending')")
            ->first();
    }

    if ($leave) {
        $typeLabel = ($leave->day_type === 'full_day') ? 'Full Day' : 'Half Day';
        return $leave->status === 'Approved'
            ? '<span class="saas-status-badge leave"><i class="fas fa-plane-departure mr-1"></i>Approved (' . $typeLabel . ')</span>'
            : '<span class="saas-status-badge leave-pending"><i class="fas fa-hourglass-half mr-1"></i>Pending (' . $typeLabel . ')</span>';
    }

    // Standard Status Logic
    if ($r->status === 'Holiday') {
        return '<span class="saas-status-badge holiday"><i class="fas fa-star mr-1"></i>Holiday</span>';
    }
    if ($r->status === 'Event' || $r->status === 'Off Day') {
        return '<span class="saas-status-badge offday"><i class="fas fa-calendar-week mr-1"></i>Off Day</span>';
    }

    if ($r->status === 'Approved' || $r->status === 'Leave') {
        return '<span class="saas-status-badge leave"><i class="fas fa-plane-departure mr-1"></i>Approved (On Leave)</span>';
    }

    if ($r->status !== 'Present' && !$r->check_in) {
        return '<span class="saas-status-badge absent"><i class="fas fa-exclamation-triangle mr-1"></i>Absent (Unpaid)</span>';
    }

    if ($r->halfDay) {
        return '<span class="saas-status-badge halfday" data-bs-toggle="tooltip" title="' . e($r->halfDay->reason) . '"><i class="fas fa-calendar-minus mr-1"></i>Half Day</span>';
    }

    if ($r->status === 'Early Leave') {
        return '<span class="saas-status-badge" style="background-color: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2);"><i class="fas fa-sign-out-alt mr-1"></i>Early Leave</span>';
    }

    if ($r->lateArrival) {
        $duration = formatMinutesToHours($r->lateArrival->late_minutes);
        return '<div>
                    <span class="saas-status-badge late" data-bs-toggle="tooltip" title="' . e($r->lateArrival->late_reason) . '">
                        <i class="fas fa-clock mr-1"></i>Late
                    </span>
                    <div class="mt-1">
                        <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.65rem; font-weight: 800; border-radius: 4px; padding: 2px 6px; letter-spacing: 0.05em;">' . $duration . '</span>
                    </div>
                </div>';
    }

    return '<span class="saas-status-badge present"><i class="fas fa-check-circle mr-1"></i>On Time</span>';
}


    public function viewDetails($id)
    {
        $attendance = Attendance::accessible()->with('employee', 'breaks')
            ->findOrFail($id);

        return view('admin.attendance.partials.details', compact('attendance'));
    }
    public function show($id)
    {
        $attendance = Attendance::accessible()->with(['employee', 'breaks'])->findOrFail($id);

        return view('admin.attendance.partials.details', compact('attendance'));
    }


    /**
     * Get attendance for all employees for a specific year, considering midnight shifts.
     *
     * @param  int|null  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttendanceForAllEmployeesWithMidnightShiftHandling($year = null)
    {
        $year = $year ?? Carbon::now()->year; // Default to current year if not provided

        // Get all employee IDs
        $employeeIds = Employee::accessible()->pluck('id');

        // Get all attendance records for the given year and employee(s)
        $attendances = Attendance::whereIn('emp_id', $employeeIds)
            ->whereYear('check_in', $year)
            ->get();

        // Process attendance for each employee
        foreach ($attendances as $attendance) {
            $shiftRecord = EmployeeShift::where('emp_id', $attendance->emp_id)
                ->whereDate('assigned_at', '<=', Carbon::now())
                ->latest('assigned_at')
                ->first();

            if (!$shiftRecord) {
                continue; // Skip if no shift assigned
            }

            $shift = Shift::find($shiftRecord->shift_id);
            if (!$shift || !$shift->crosses_midnight) {
                // If no shift or the shift does not cross midnight, use check-out date as attendance date
                $attendance->attendance_date = Carbon::parse($attendance->check_out)->format('Y-m-d');
            } else {
                // If shift crosses midnight, set attendance date to check-in date
                $attendance->attendance_date = Carbon::parse($attendance->check_in)->format('Y-m-d');
            }
        }

        return response()->json($attendances);
    }

    /**
     * Get attendance for a specific employee for a specific year, considering midnight shifts.
     *
     * @param  int  $empId
     * @param  int|null  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttendanceForSpecificEmployeeWithMidnightShiftHandling($empId, $year = null)
    {
        $year = $year ?? Carbon::now()->year; // Default to current year if not provided

        // Get attendance records for the specific employee
        $attendanceExists = Employee::accessible()->where('id', $empId)->exists();
        if (!$attendanceExists) abort(403);

        $attendances = Attendance::where('emp_id', $empId)
            ->whereYear('check_in', $year)
            ->get();

        // Process attendance for the employee
        foreach ($attendances as $attendance) {
            $shiftRecord = EmployeeShift::where('emp_id', $attendance->emp_id)
                ->whereDate('assigned_at', '<=', Carbon::now())
                ->latest('assigned_at')
                ->first();

            if (!$shiftRecord) {
                continue; // Skip if no shift assigned
            }

            $shift = Shift::find($shiftRecord->shift_id);
            if (!$shift || !$shift->crosses_midnight) {
                // If no shift or the shift does not cross midnight, use check-out date as attendance date
                $attendance->attendance_date = Carbon::parse($attendance->check_out)->format('Y-m-d');
            } else {
                // If shift crosses midnight, set attendance date to check-in date
                $attendance->attendance_date = Carbon::parse($attendance->check_in)->format('Y-m-d');
            }
        }

        return response()->json($attendances);
    }

    

}
