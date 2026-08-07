<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\LeaveType;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\LeaveStatusChanged;
use App\Events\LeaveApplied;

class LeaveController extends Controller
{
    public function index()
    {
        $this->authorize('view-leaves');
        $employees = Employee::accessible()->whereNull('resign_date')->orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'max_days']);
        $shifts = Shift::accessible()->orderBy('shift_name')->get(['id', 'shift_name']);
        $approvers = Employee::accessible()->whereIn('id', Leave::accessible()->select('approved_by')->distinct()->whereNotNull('approved_by')->pluck('approved_by'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $summary = [
            'total' => Leave::accessible()->count(),
            'approved' => Leave::accessible()->where('status', 'Approved')->count(),
            'pending' => Leave::accessible()->where('status', 'Pending')->count(),
            'rejected' => Leave::accessible()->where('status', 'Rejected')->count(),
        ];

        return view('admin.leaves.index', compact('employees', 'leaveTypes', 'shifts', 'approvers', 'summary'));
    }

    //    public function data(Request $request)
//     {
//         try {
//             $query = Leave::with(['employee:id,name', 'shift:id,shift_name', 'approvedBy:id,name', 'leaveType:id,name,slug,max_days'])->latest();

    //             // Apply filters
//             if ($request->employee_id) {
//                 $query->where('employee_id', $request->employee_id);
//             }
//             if ($request->leave_type) {
//                 $query->where('leave_type', $request->leave_type);
//             }
//             if ($request->status) {
//                 $query->where('status', $request->status);
//             }
//             if ($request->shift_id) {
//                 $query->where('shift_id', $request->shift_id);
//             }
//             if ($request->day_type) {
//                 $query->where('day_type', $request->day_type);
//             }
//             if ($request->approved_by) {
//                 $query->where('approved_by', $request->approved_by);
//             }
//             if (in_array($request->is_balance_deducted, ['0', '1'], true)) {
//                 $query->where('is_balance_deducted', $request->is_balance_deducted);
//             }
//             if ($request->date_range) {
//                 [$from, $to] = array_map('trim', explode(' - ', $request->date_range));
//                 $query->where(function ($q) use ($from, $to) {
//                     $q->whereBetween('start_date', [$from, $to])
//                       ->orWhereBetween('end_date', [$from, $to]);
//                 });
//             }

    //             // Calculate summary counts based on filtered query
//             $summary = [
//                 'total' => (clone $query)->count(),
//                 'approved' => (clone $query)->where('status', 'Approved')->count(),
//                 'pending' => (clone $query)->where('status', 'Pending')->count(),
//                 'rejected' => (clone $query)->where('status', 'Rejected')->count(),
//             ];

    //             return DataTables::eloquent($query)
//                 ->addColumn('date_range', fn($r) => $r->start_date && $r->end_date ? $r->start_date->format('d-M-Y') . ' to ' . $r->end_date->format('d-M-Y') : '-')
//                 ->addColumn('duration', fn($r) => $r->start_date && $r->end_date ? ($r->start_date->diffInDays($r->end_date) + 1) . ' days' : '-')
//                 ->addColumn('employee_name', fn($r) => $r->employee ? $r->employee->name : '-')
//                 ->addColumn('leave_type_name', fn($r) => $r->leaveType ? $r->leaveType->name : ucfirst($r->leave_type ?? '-'))
//                 ->addColumn('max_days', fn($r) => $r->leaveType ? $r->leaveType->max_days : '-')
//                 ->addColumn('day_type_label', function ($r) {
//                     return match ($r->day_type) {
//                         'full_day' => 'Full Day',
//                         'half_day' => 'Half Day',
//                         default => '-'
//                     };
//                 })
//                 ->addColumn('shift_name', fn($r) => $r->shift ? $r->shift->shift_name : '-')
//                 ->addColumn('status_badge', function ($r) {
//                     $badge = match ($r->status) {
//                         'Approved' => 'success',
//                         'Rejected' => 'danger',
//                         'Pending' => 'warning',
//                         default => 'secondary'
//                     };
//                     return '<span class="badge bg-' . $badge . '">' . ($r->status ?? '-') . '</span>';
//                 })
//                 ->addColumn('approved_by_name', fn($r) => $r->approvedBy ? $r->approvedBy->name : '-')
//                 ->addColumn('is_balance_deducted', fn($r) => $r->is_balance_deducted ? 'Deducted' : 'Not Deducted')
//                 ->addColumn('action', function ($r) {
//                     return '<a href="' . route('admin.leaves.show', $r->id) . '" class="btn btn-sm btn-info leave-show" data-id="' . $r->id . '"><i class="fa fa-eye"></i></a>';
//                 })
//                 ->rawColumns(['status_badge', 'action'])
//                 ->with('summary', $summary)
//                 ->make(true);
//         } catch (\Exception $e) {
//             \Log::error('DataTable Error: ' . $e->getMessage());
//             return response()->json(['error' => $e->getMessage()], 500);
//         }
//     } 

    public function data(Request $request)
    {
        $this->authorize('view-leaves');
        try {
            $query = Leave::accessible()->with(['shift:id,shift_name', 'approvedBy:id,name', 'leaveType:id,name,slug,max_days', 'approvals.approver'])
                ->leftJoin('employees', 'leaves.employee_id', '=', 'employees.id')
                ->select('leaves.*', 'employees.name as employee_name', 'employees.profile_pic', 'employees.id as emp_primary_id')
                ->latest('leaves.created_at');

            // Apply filters
            if ($request->employee_id) {
                $query->where('leaves.employee_id', $request->employee_id);
            }
            if ($request->leave_type) {
                $query->where('leaves.leave_type', $request->leave_type);
            }
            if ($request->status) {
                $query->where('leaves.status', $request->status);
            }
            if ($request->shift_id) {
                $query->where('leaves.shift_id', $request->shift_id);
            }
            if ($request->day_type) {
                $query->where('leaves.day_type', $request->day_type);
            }
            if ($request->approved_by) {
                $query->where('leaves.approved_by', $request->approved_by);
            }
            if (in_array($request->is_balance_deducted, ['0', '1'], true)) {
                $query->where('leaves.is_balance_deducted', $request->is_balance_deducted);
            }
            if ($request->date_range) {
                [$from, $to] = array_map('trim', explode(' - ', $request->date_range));
                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('leaves.start_date', [$from, $to])
                        ->orWhereBetween('leaves.end_date', [$from, $to]);
                });
            }

            // Calculate analytical stats based on filtered query
            $statsQuery = clone $query;
            $summary = [
                'total' => (clone $statsQuery)->count(),
                'approved' => (clone $statsQuery)->where('leaves.status', 'Approved')->count(),
                'pending' => (clone $statsQuery)->where('leaves.status', 'Pending')->count(),
                'rejected' => (clone $statsQuery)->where('leaves.status', 'Rejected')->count(),
                'active_today' => Leave::accessible()->where('status', 'Approved')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->count(),
            ];

            $canApprove = auth()->user()->can('approve-leave');
            $canReject = auth()->user()->can('reject-leave');

            return DataTables::eloquent($query)
                ->addColumn('profile_pic_url', function ($row) {
                    return $row->profile_pic && \Storage::disk('public')->exists($row->profile_pic) ? \Storage::disk('public')->url($row->profile_pic) : null;
                })
                ->addColumn('date_range', fn($r) => $r->start_date && $r->end_date ? \Carbon\Carbon::parse($r->start_date)->format('d-M-Y') . ' to ' . \Carbon\Carbon::parse($r->end_date)->format('d-M-Y') : '-')
                ->addColumn('duration', fn($r) => $r->start_date && $r->end_date ? (\Carbon\Carbon::parse($r->start_date)->diffInDays(\Carbon\Carbon::parse($r->end_date)) + 1) . ' days' : '-')
                ->addColumn('leave_type_name', fn($r) => $r->leaveType ? $r->leaveType->name : ucfirst($r->leave_type ?? '-'))
                ->addColumn('max_days', fn($r) => $r->leaveType ? $r->leaveType->max_days : '-')
                ->addColumn('day_type_label', function ($r) {
                    return match ($r->day_type) {
                        'full_day' => 'Full Day',
                        'first_half' => 'First Half',
                        'second_half' => 'Second Half',
                        default => '-'
                    };
                })
                ->addColumn('shift_name', fn($r) => $r->shift ? $r->shift->shift_name : '-')
                ->addColumn('status_badge', function ($r) {
                    $badge = match ($r->status) {
                        'Approved' => 'approved',
                        'Rejected' => 'rejected',
                        'Pending' => 'pending',
                        'Pending_Lead' => 'pending-lead',
                        default => 'secondary'
                    };
                    
                    if ($r->status === 'Pending') {
                        $leadApproval = $r->approvals->where('stage', 'Lead')->where('action', 'Approved')->first();
                        if ($leadApproval && $leadApproval->approver) {
                            return '<span class="saas-status-badge pending" style="background: rgba(59, 130, 246, 0.1); color: #2563eb; font-size: 0.75rem;"><i class="bi bi-check-circle-fill me-1 text-primary"></i>Approved: ' . explode(' ', trim($leadApproval->approver->name))[0] . ' (Lead)</span>';
                        }
                    }
                    
                    $label = $r->status === 'Pending_Lead' ? 'Pending' : $r->status;
                    return '<span class="saas-status-badge ' . $badge . '">' . ($label ?? '-') . '</span>';
                })
                ->addColumn('approved_by_name', fn($r) => $r->approvedBy ? $r->approvedBy->name : '-')
                ->addColumn('is_balance_deducted', fn($r) => $r->is_balance_deducted ? 'Deducted' : 'Not Deducted')
                ->addColumn('action', function ($r) use ($canApprove, $canReject) {
                    $buttons = '<div class="d-flex gap-2 justify-content-end">';
                    $buttons .= '<a href="' . route('admin.leaves.show', $r->id) . '" class="btn-saas-action leave-show" title="View Details" data-id="' . $r->id . '"><i class="fas fa-eye"></i></a>';
                    
                    $user = auth()->user();
                    $isOwnLeave = $user->employee && ($r->employee_id === $user->employee->id);
                    $isAdmin = $user->can('access-admin-panel');
                    
                    if (!$isOwnLeave) {
                        $showButtons = false;
                        // Show buttons if user has permission AND the leave is in an actionable status
                        if ($canApprove || $canReject) {
                            if ($isAdmin && in_array($r->status, ['Pending', 'Pending_Lead'])) {
                                $showButtons = true;
                            } else if ($r->status === 'Pending_Lead') {
                                // Leads/Managers can act on Pending_Lead
                                $showButtons = true;
                            }
                        }

                        if ($showButtons) {
                            if ($canApprove) {
                                $isSingleDay = $r->start_date && $r->end_date && \Carbon\Carbon::parse($r->start_date)->toDateString() === \Carbon\Carbon::parse($r->end_date)->toDateString() ? '1' : '0';
                                $buttons .= '<button class="btn-saas-action approve-btn leave-approve" title="Approve Leave" data-id="' . $r->id . '" data-single="' . $isSingleDay . '"><i class="fas fa-check"></i></button>';
                            }
                            if ($canReject) {
                                $buttons .= '<button class="btn-saas-action reject-btn leave-reject" title="Reject Leave" data-id="' . $r->id . '"><i class="fas fa-times"></i></button>';
                            }
                        } else if ($isAdmin && in_array($r->status, ['Approved', 'Rejected', 'Cancelled'])) {
                            $isSingleDay = $r->start_date && $r->end_date && \Carbon\Carbon::parse($r->start_date)->toDateString() === \Carbon\Carbon::parse($r->end_date)->toDateString() ? '1' : '0';
                            $lastApproval = $r->approvals->where('action', '!=', 'Updated')->last();
                            $reason = $lastApproval ? htmlspecialchars($lastApproval->reason, ENT_QUOTES) : '';
                            $attendanceStatus = '';
                            if ($r->status === 'Cancelled' && $isSingleDay) {
                                // Extract attendance status from reason if possible, or leave blank to let user re-select
                                preg_match('/\(Cancelled: Attendance updated to (.*?)\)$/', $r->reason, $matches);
                                if (!empty($matches[1])) {
                                    $attendanceStatus = $matches[1];
                                }
                            }
                            $isDeducted = $r->is_balance_deducted ? '1' : '0';
                            $buttons .= '<button class="btn-saas-action edit-btn leave-edit-decision text-indigo" title="Edit Decision" data-id="' . $r->id . '" data-single="' . $isSingleDay . '" data-reason="' . $reason . '" data-attendance="' . htmlspecialchars($attendanceStatus, ENT_QUOTES) . '" data-deduct="' . $isDeducted . '"><i class="fas fa-edit"></i></button>';
                        }
                    }
                    
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->with('summary', $summary)
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTable Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateDays(Request $request)
    {
        $this->authorize('create-leave');
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $from = \Carbon\Carbon::parse($request->start_date);
            $to = \Carbon\Carbon::parse($request->end_date);
            
            $days = 0;
            $holidays = 0;
            $employee = $request->employee_id ? Employee::accessible()->find($request->employee_id) : Auth::user()->employee;
            $team_id = $employee ? $employee->team_id : null;
            
            while ($from->lte($to)) {
                $isOff = \App\Models\CompanyOffDay::isOffDay($from, null, $team_id);
                if ($isOff) {
                    $holidays++;
                } else {
                    $days++;
                }
                $from->addDay();
            }

            return response()->json(['days' => $days, 'holidays' => $holidays]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function approve(Request $request, $id)
    {
        $this->authorize('approve-leave');
        try {
            $request->validate(['reason' => 'required|string']);
            
            return DB::transaction(function () use ($request, $id) {
                $leave = Leave::accessible()->with('employee')->lockForUpdate()->findOrFail($id);
                
                if ($leave->employee && $leave->employee->user_id === Auth::id()) {
                    return response()->json(['message' => 'Security Error: You cannot approve your own leave requests.'], 403);
                }

                if (!in_array($leave->status, ['Pending', 'Pending_Lead'])) {
                    return response()->json(['message' => 'Leave is already processed. Current status: ' . $leave->status], 400);
                }

                $isAdmin = Auth::user()->can('access-admin-panel');
                if (!$isAdmin && $leave->status !== 'Pending_Lead') {
                    return response()->json(['message' => 'Security Error: You are not authorized to authorize this stage. Request is with HR/Admin.'], 403);
                }

                $attendanceStatus = $request->input('attendance_status');
                $isSingleDay = $leave->start_date->toDateString() === $leave->end_date->toDateString();
                
                if ($attendanceStatus && $isSingleDay) {
                    $this->updateAttendanceForLeave($leave, $attendanceStatus);
                    
                    $stage = $leave->status === 'Pending_Lead' ? 'HR (Direct Override)' : 'HR';
                    $leave->status = 'Cancelled';
                    $leave->approved_by = Auth::id();
                    $leave->reason = trim($leave->reason . ' (Cancelled: Attendance updated to ' . $attendanceStatus . ')');
                } else {
                    if ($isAdmin) {
                        $stage = $leave->status === 'Pending_Lead' ? 'HR (Direct Override)' : 'HR';
                        $leave->status = 'Approved';
                        $leave->approved_by = Auth::id();
                        $leave->is_balance_deducted = $request->input('deduct_balance', 0);
                    } else {
                        $stage = 'Lead';
                        $leave->status = 'Pending'; // Handover to HR queue
                    }
                }
                
                $leave->save();

                // Record Audit Trail
                \App\Models\LeaveApproval::create([
                    'leave_id' => $leave->id,
                    'approver_id' => Auth::id(),
                    'stage' => $stage,
                    'action' => 'Approved',
                    'reason' => $request->input('reason')
                ]);

                // Dispatch Pusher Event for Employee Notification
                event(new LeaveStatusChanged($leave->employee_id, 'Approved', $request->input('reason')));

                clear_employee_app_data_cache((int) $leave->employee_id);

                return response()->json(['message' => 'Leave authorized successfully']);
            });
        } catch (\Exception $e) {
            \Log::error('Approve Leave Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to approve leave: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('reject-leave');
        try {
            $request->validate(['reason' => 'required|string']);
            
            return DB::transaction(function () use ($request, $id) {
                $leave = Leave::accessible()->with('employee')->lockForUpdate()->findOrFail($id);
                
                if ($leave->employee && $leave->employee->user_id === Auth::id()) {
                    return response()->json(['message' => 'Security Error: You cannot reject your own leave requests.'], 403);
                }

                if (!in_array($leave->status, ['Pending', 'Pending_Lead'])) {
                    return response()->json(['message' => 'Leave is already processed. Current status: ' . $leave->status], 400);
                }

                $isAdmin = Auth::user()->can('access-admin-panel');
                if (!$isAdmin && $leave->status !== 'Pending_Lead') {
                    return response()->json(['message' => 'Security Error: You are not authorized to deny this stage. Request is with HR/Admin.'], 403);
                }

                if ($isAdmin) {
                    $stage = $leave->status === 'Pending_Lead' ? 'HR (Direct Override)' : 'HR';
                } else {
                    $stage = 'Lead';
                }

                $leave->status = 'Rejected';
                $leave->approved_by = Auth::id();
                $leave->save();

                // Record Audit Trail
                \App\Models\LeaveApproval::create([
                    'leave_id' => $leave->id,
                    'approver_id' => Auth::id(),
                    'stage' => $stage,
                    'action' => 'Rejected',
                    'reason' => $request->input('reason')
                ]);

                // Dispatch Pusher Event for Employee Notification
                event(new LeaveStatusChanged($leave->employee_id, 'Rejected', $request->input('reason')));

                clear_employee_app_data_cache((int) $leave->employee_id);

                return response()->json(['message' => 'Leave rejected successfully']);
            });
        } catch (\Exception $e) {
            \Log::error('Reject Leave Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to reject leave: ' . $e->getMessage()], 500);
        }
    }

    public function updateDecision(Request $request, $id)
    {
        $this->authorize('approve-leave'); // Requires admin-level leave management
        try {
            $request->validate(['reason' => 'required|string']);
            
            return DB::transaction(function () use ($request, $id) {
                $leave = Leave::accessible()->with('employee')->lockForUpdate()->findOrFail($id);
                
                $isAdmin = Auth::user()->can('access-admin-panel');
                if (!$isAdmin) {
                    return response()->json(['message' => 'Security Error: Only administrators can edit processed decisions.'], 403);
                }

                if (in_array($leave->status, ['Pending', 'Pending_Lead'])) {
                    return response()->json(['message' => 'Leave has not been processed yet. Cannot edit decision.'], 400);
                }

                // If balance was deducted previously, refund it temporarily via Observer
                if ($leave->is_balance_deducted) {
                    $leave->is_balance_deducted = 0;
                    $leave->save(); // This triggers the observer to refund
                }

                $attendanceStatus = $request->input('attendance_status');
                $isSingleDay = $leave->start_date->toDateString() === $leave->end_date->toDateString();
                
                if ($attendanceStatus && $isSingleDay) {
                    $this->updateAttendanceForLeave($leave, $attendanceStatus);
                    
                    $leave->status = 'Cancelled';
                    $leave->approved_by = Auth::id();
                    // Clean up existing cancelled suffix if present
                    $baseReason = preg_replace('/ \(Cancelled: Attendance updated to .*\)$/', '', $leave->reason);
                    $leave->reason = trim($request->input('reason') . ' (Cancelled: Attendance updated to ' . $attendanceStatus . ')');
                } else {
                    // If no attendance status is selected, and it was Cancelled before, remove the attendance override
                    if ($leave->status === 'Cancelled' && $isSingleDay) {
                        $date = $leave->start_date->toDateString();
                        $attendance = \App\Models\Attendance::where('emp_id', $leave->employee_id)->where('shift_date', $date)->first();
                        if ($attendance && $attendance->is_manual) {
                            \App\Models\LateArrival::where('attendance_id', $attendance->id)->delete();
                            $attendance->delete();
                        }
                    }

                    // Restore to Approved 
                    $leave->status = 'Approved';
                    $leave->approved_by = Auth::id();
                    $leave->reason = preg_replace('/ \(Cancelled: Attendance updated to .*\)$/', '', $request->input('reason'));
                    $leave->is_balance_deducted = $request->input('deduct_balance', 0);
                }
                
                $leave->save();

                // Record Audit Trail
                \App\Models\LeaveApproval::create([
                    'leave_id' => $leave->id,
                    'approver_id' => Auth::id(),
                    'stage' => 'HR (Decision Edited)',
                    'action' => 'Approved',
                    'reason' => $request->input('reason')
                ]);

                clear_employee_app_data_cache((int) $leave->employee_id);

                return response()->json(['message' => 'Leave decision updated successfully']);
            });
        } catch (\Exception $e) {
            \Log::error('Update Leave Decision Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update decision: ' . $e->getMessage()], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('approve-leave');
        try {
            $request->validate([
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'attendance_status' => 'required|string|in:Present,Late,Half Day (First Half),Half Day (Second Half)'
            ]);

            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
            $attendanceStatus = $request->attendance_status;
            
            $leaves = Leave::whereIn('employee_id', $request->employee_ids)
                ->whereIn('status', ['Pending', 'Pending_Lead'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                })->get();

            $processed = 0;
            $skipped = 0;

            DB::transaction(function () use ($leaves, $attendanceStatus, &$processed, &$skipped) {
                foreach ($leaves as $leave) {
                    $employee = $leave->employee;
                    if (!$employee) {
                        $skipped++;
                        continue;
                    }

                    $dateCursor = $leave->start_date->copy();
                    $workedDays = 0;

                    while ($dateCursor->lte($leave->end_date)) {
                        $isOff = \App\Models\CompanyOffDay::isOffDay($dateCursor, null, $employee->team_id);
                        if (!$isOff) {
                            $this->updateAttendanceForLeave($leave, $attendanceStatus, $dateCursor->toDateString());
                            $workedDays++;
                        }
                        $dateCursor->addDay();
                    }

                    if ($workedDays > 0) {
                        $leave->status = 'Cancelled';
                        $leave->approved_by = Auth::id();
                        $leave->reason = trim($leave->reason . ' (Bulk Cancelled: Attendance updated to ' . $attendanceStatus . ')');
                        $leave->save();

                        \App\Models\LeaveApproval::create([
                            'leave_id' => $leave->id,
                            'approver_id' => Auth::id(),
                            'stage' => 'HR (Bulk Update)',
                            'action' => 'Approved',
                            'reason' => 'Bulk updated to ' . $attendanceStatus
                        ]);
                        
                        clear_employee_app_data_cache((int) $leave->employee_id);
                        $processed++;
                    } else {
                        $skipped++;
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Bulk update complete.",
                'summary' => [
                    'found' => $leaves->count(),
                    'processed' => $processed,
                    'skipped' => $skipped
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Bulk Leave Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Bulk update failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $this->authorize('view-leaves');
        $leave = Leave::accessible()->with(['employee:id,name', 'shift:id,shift_name', 'approvedBy:id,name', 'leaveType:id,name,slug,max_days', 'approvals.approver:id,name'])->findOrFail($id);

        if ($request->ajax()) {
            return response()->json([
                'id' => $leave->id,
                'employee_name' => $leave->employee ? $leave->employee->name : '-',
                'leave_type_name' => $leave->leaveType ? $leave->leaveType->name : ucfirst($leave->leave_type ?? '-'),
                'max_days' => $leave->leaveType ? $leave->leaveType->max_days : '-',
                'date_range' => $leave->start_date && $leave->end_date ? $leave->start_date->format('d-M-Y') . ' to ' . $leave->end_date->format('d-M-Y') : '-',
                'duration' => $leave->start_date && $leave->end_date ? ($leave->start_date->diffInDays($leave->end_date) + 1) . ' days' : '-',
                'day_type' => match ($leave->day_type) {
                    'full_day' => 'Full Day',
                    'first_half' => 'First Half',
                    'second_half' => 'Second Half',
                    default => '-'
                },
                'shift_name' => $leave->shift ? $leave->shift->shift_name : '-',
                'status' => $leave->status === 'Pending_Lead' ? 'Pending' : ($leave->status ?? '-'),
                'reason' => $leave->reason ?? '-',
                'approved_by_name' => $leave->approvedBy ? $leave->approvedBy->name : '-',
                'is_balance_deducted' => $leave->is_balance_deducted ? 'Deducted' : 'Not Deducted',
                'created_at' => $leave->created_at ? $leave->created_at->format('d-M-Y H:i:s') : '-',
                'approvals' => $leave->approvals->map(function ($a) {
                    return [
                        'stage' => $a->stage,
                        'action' => $a->action,
                        'reason' => $a->reason,
                        'approver_name' => $a->approver ? $a->approver->name : 'Unknown',
                        'date' => $a->created_at->format('d-M-Y h:i A')
                    ];
                })
            ]);
        }

        return view('admin.leaves.show', compact('leave'));
    }

    public function store(Request $request)
    {
        $this->authorize('create-leave');
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_type' => 'required|exists:leave_types,slug',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'day_type' => 'required|in:full_day,first_half,second_half',
                'reason' => 'required|string',
            ]);

            $leave = new Leave();
            $leave->employee_id = $request->employee_id;
            
            // Branch Assignment
            $employee = Employee::accessible()->find($request->employee_id);
            if ($employee) {
                $leave->branch_id = $employee->branch_id;
            }
            $leave->leave_type = $request->leave_type;
            $leave->start_date = $request->start_date;
            $leave->end_date = $request->end_date;
            $leave->day_type = $request->day_type;
            $leave->reason = $request->reason;
            
            // Workflow Logic
            $employee = Employee::accessible()->with('team')->find($request->employee_id);
            if ($employee && $employee->user_id === Auth::id()) {
                // User applying for themselves
                $hasLead = $employee->team && $employee->team->leader_id;
                // If they are the leader themselves, they don't have a lead to approve them
                if ($hasLead && $employee->team->leader_id == $employee->id) {
                    $hasLead = false;
                }
                $leave->status = $hasLead ? 'Pending_Lead' : 'Pending';
                $leave->approved_by = null;
            } else {
                // Admin logging a manual record for someone else
                $leave->status = 'Approved';
                $leave->approved_by = Auth::id();
            }
            
            $leave->save();

            return response()->json(['success' => true, 'message' => 'Record logged successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function updateAttendanceForLeave($leave, $statusStr, $overrideDate = null)
    {
        $employeeId = $leave->employee_id;
        $date = $overrideDate ?: $leave->start_date->toDateString();
        
        $shift = $leave->shift;
        if (!$shift) {
            $currentAssignment = \App\Models\EmployeeShift::where('emp_id', $employeeId)->orderBy('assigned_at', 'desc')->first();
            $shift = $currentAssignment ? $currentAssignment->shift : \App\Models\Shift::where('is_active', 1)->first();
        }
        if (!$shift) return;

        $tz = get_employee_settings($employeeId, 'time_zone') ?? 'Asia/Karachi';
        $shiftStart = \Carbon\Carbon::parse($date . ' ' . $shift->start_time, $tz);
        $shiftEnd = \Carbon\Carbon::parse($date . ' ' . $shift->end_time, $tz);
        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $checkIn = null;
        $checkOut = null;
        $lateDuration = 0;
        $status = $statusStr;

        if ($statusStr === 'Present') {
            $checkIn = $shiftStart->copy();
            $checkOut = $shiftEnd->copy();
        } elseif ($statusStr === 'Late') {
            $status = 'Late';
            $gracePeriod = (int) (get_employee_settings($employeeId, 'late_grace_minutes') ?? 5);
            $checkIn = $shiftStart->copy()->addMinutes($gracePeriod + 1);
            $checkOut = $shiftEnd->copy();
            $lateDuration = $gracePeriod + 1;
        } elseif (str_starts_with($statusStr, 'Half Day')) {
            $status = 'Half Day';
            $halfDuration = $shiftStart->diffInMinutes($shiftEnd) / 2;
            if (str_contains($statusStr, 'First Half')) {
                // Worked first half
                $checkIn = $shiftStart->copy();
                $checkOut = $shiftStart->copy()->addMinutes($halfDuration);
            } else {
                // Worked second half
                $checkIn = $shiftStart->copy()->addMinutes($halfDuration);
                $checkOut = $shiftEnd->copy();
            }
        }

        $attendance = \App\Models\Attendance::updateOrCreate(
            ['emp_id' => $employeeId, 'shift_date' => $date],
            [
                'shift_id' => $shift->id,
                'status' => $status,
                'is_manual' => true,
                'modified_by' => \Auth::id(),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'late_duration' => $lateDuration,
            ]
        );

        if ($status === 'Late' && $lateDuration > 0) {
            \App\Models\LateArrival::updateOrCreate(
                ['attendance_id' => $attendance->id],
                [
                    'emp_id' => $employeeId,
                    'shift_id' => $shift->id,
                    'date' => $date,
                    'scheduled_start' => $shiftStart,
                    'actual_check_in' => $checkIn,
                    'late_minutes' => $lateDuration,
                    'late_reason' => 'Leave Approved - Late Mark',
                ]
            );
        }
    }
}