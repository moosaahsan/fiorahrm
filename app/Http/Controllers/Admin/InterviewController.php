<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\Employee;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

class InterviewController extends Controller
{
    public function index()
    {
        $this->authorize('view-interview');

        $query = Interview::query();

        //addmin and reception and with hr can see all interviews
        if (!auth()->user()->hasRole(['admin', 'receptionist'])) {
            $categories = [];
            if (auth()->user()->can('view-bpo-interviews'))
                $categories[] = 'BPO';
            if (auth()->user()->can('view-billing-interviews'))
                $categories[] = 'Billing';

            if (!empty($categories)) {
                $query->whereIn('category', $categories);
            }
        }

        $appliedCount = (clone $query)->where('status', 'Applied')->count();
        $scheduledCount = (clone $query)->where('status', 'Scheduled')->count();
        $inProgressCount = (clone $query)->where('status', 'In Progress')->count();
        $trainingCount = (clone $query)->where('status', 'Training')->count();
        $onHoldCount = (clone $query)->where('status', 'On Hold')->count();
        $finalizedCount = (clone $query)->whereIn('status', ['Selected', 'Rejected', 'Onboarded', 'No Show', 'Hired'])->count();

        $creators = User::query()
            ->whereIn('id', Interview::query()->whereNotNull('created_by')->distinct()->pluck('created_by'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $sources = Interview::query()
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $categories = [];
        if (auth()->user()->hasRole(['admin', 'receptionist'])) {
            $categories = ['BPO', 'Billing'];
        } else {
            if (auth()->user()->can('view-bpo-interviews')) {
                $categories[] = 'BPO';
            }
            if (auth()->user()->can('view-billing-interviews')) {
                $categories[] = 'Billing';
            }
        }

        $interviewTypes = Interview::query()
            ->whereNotNull('interview_type')
            ->where('interview_type', '!=', '')
            ->distinct()
            ->orderBy('interview_type')
            ->pluck('interview_type');

        if ($interviewTypes->isEmpty()) {
            $interviewTypes = collect(['Walk-in', 'Referral', 'Social Media']);
        }

        $teamLeads = User::query()
            ->whereIn('id', Interview::query()->whereNotNull('team_lead_id')->distinct()->pluck('team_lead_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $allUsers = User::orderBy('name')->get(['id', 'name']);

        return view('admin.interviews.index', compact(
            'appliedCount',
            'scheduledCount',
            'inProgressCount',
            'trainingCount',
            'onHoldCount',
            'finalizedCount',
            'creators',
            'sources',
            'categories',
            'interviewTypes',
            'teamLeads',
            'allUsers'
        ));
    }

    /**
     * Get interviews data for DataTable.
     */
    public function data(Request $request)
    {
        $this->authorize('view-interview');

        $query = Interview::with(['creator', 'assignedTo', 'jobPosting', 'teamLead'])->latest();

        // Permission-based category filtering (Exempt Admin and Receptionist)
        if (!auth()->user()->hasRole(['admin', 'receptionist'])) {
            $categories = [];
            if (auth()->user()->can('view-bpo-interviews'))
                $categories[] = 'BPO';
            if (auth()->user()->can('view-billing-interviews'))
                $categories[] = 'Billing';

            if (!empty($categories)) {
                $query->whereIn('category', $categories);
            } else {
                $query->whereRaw('1=0'); // Security: no permission = no data
            }
        }



        if ($request->has('status_filter') && $request->status_filter) {
            $tab = $request->status_filter;
            if ($tab === 'applied') {
                $query->where('status', 'Applied');
            } elseif ($tab === 'scheduled') {
                $query->where('status', 'Scheduled');
            } elseif ($tab === 'in_progress') {
                $query->where('status', 'In Progress');
            } elseif ($tab === 'training') {
                $query->where('status', 'Training');
                if ($request->has('training_team_lead') && $request->training_team_lead !== 'all') {
                    $query->where('team_lead_id', $request->training_team_lead);
                }
            } elseif ($tab === 'on_hold') {
                $query->where('status', 'On Hold');
            } elseif ($tab === 'finalized') {
                $subFilter = $request->finalized_sub_filter;
                if ($subFilter === 'Onboarded') {
                    $query->where('status', 'Onboarded');
                } elseif ($subFilter === 'Rejected') {
                    $query->where('status', 'Rejected');
                } else {
                    $query->whereIn('status', ['Selected', 'Rejected', 'Onboarded', 'No Show', 'Hired']);
                }
            }
        }

        // Date range is required for directory browsing (default last 30 days if omitted)
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        if (!$dateFrom || !$dateTo) {
            $dateTo = now()->toDateString();
            $dateFrom = now()->subDays(29)->toDateString();
        }

        $dateField = $request->input('date_field', 'created_at');
        if (!in_array($dateField, ['created_at', 'interview_date'], true)) {
            $dateField = 'created_at';
        }

        if ($dateField === 'interview_date') {
            $query->whereDate('interview_date', '>=', $dateFrom)
                ->whereDate('interview_date', '<=', $dateTo);
        } else {
            $query->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo);
        }

        if ($request->filled('interview_type')) {
            $query->where('interview_type', $request->interview_type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('created_by')) {
            $creatorId = $request->created_by;
            $query->where(function ($q) use ($creatorId) {
                $q->where('assigned_to', $creatorId)
                  ->orWhere('created_by', $creatorId);
            });
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('cv', function ($row) {
                $html = '';
                if ($row->cv_path) {
                    $html .= '<a href="' . route('admin.interviews.view-cv', basename($row->cv_path)) . '" target="_blank" class="btn btn-sm btn-outline-info mr-1 mb-1" title="View CV"><i class="fas fa-file-pdf"></i> CV</a>';
                }
                if ($row->cnic_front_path) {
                    $html .= '<a href="' . route('admin.interviews.view-cnic-doc', ['id' => $row->id, 'side' => 'front']) . '" target="_blank" class="btn btn-sm btn-outline-primary mr-1 mb-1" title="CNIC Front"><i class="far fa-id-card"></i> Front</a>';
                }
                if ($row->cnic_back_path) {
                    $html .= '<a href="' . route('admin.interviews.view-cnic-doc', ['id' => $row->id, 'side' => 'back']) . '" target="_blank" class="btn btn-sm btn-outline-secondary mb-1" title="CNIC Back"><i class="far fa-id-card"></i> Back</a>';
                }
                return $html ?: '<span class="text-muted">No Docs</span>';
            })
            ->addColumn('created_by_name', function ($row) {
                $handler = $row->assignedTo ? $row->assignedTo->name : ($row->creator ? $row->creator->name : 'System');
                $creator = $row->creator ? $row->creator->name : null;

                $html = '<div class="d-flex flex-column">';
                $html .= '<span class="font-weight-bold text-dark">' . e($handler) . '</span>';

                if ($creator && $row->assigned_to && (int)$row->assigned_to !== (int)$row->created_by) {
                    $html .= '<small class="text-muted" title="Originally recorded by ' . e($creator) . '"><i class="fas fa-history text-xs mr-1"></i>Rec by: ' . e($creator) . '</small>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('type', function ($row) {
                $type = '<span class="badge badge-light border px-2 py-1">' . $row->interview_type . '</span>';
                if ($row->category) {
                    $catClass = $row->category === 'Billing' ? 'badge-info' : 'badge-primary';
                    $type .= ' <span class="badge ' . $catClass . ' ml-1">' . $row->category . '</span>';
                }
                return $type;
            })
            ->addColumn('job_position', function ($row) {
                if ($row->jobPosting)
                    return $row->jobPosting->title;
                return $row->position_applied ?? 'N/A';
            })
            ->addColumn('assigned_team_lead', function ($row) {
                if ($row->status === 'Training' || $row->team_lead_id) {
                    return $row->teamLead ? $row->teamLead->name : 'Not Assigned';
                }
                return '-';
            })
            ->addColumn('action', function ($row) use ($request) {
                $user = auth()->user();
                $btn = '<div class="d-flex gap-2">';

                // Show/Timeline is always visible for anyone who can view interviews
                $tabParam = $request->status_filter ? '?tab=' . $request->status_filter : '';
                $btn .= '<a href="' . route('admin.interviews.show', $row->id) . $tabParam . '" class="btn-saas-action border-primary text-primary" title="Candidate Profile & Timeline"><i class="fas fa-address-card"></i></a>';

                if ($user->can('edit-interview')) {
                    $currentAssigned = $row->assigned_to ?? $row->created_by;
                    $btn .= '<button type="button" class="btn-saas-action reassign-candidate text-info border-info" data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-assigned="' . $currentAssigned . '" title="Move Candidate / Reassign Owner"><i class="fas fa-exchange-alt"></i></button>';
                    $btn .= '<a href="' . route('admin.interviews.edit', $row->id) . $tabParam . '" class="btn-saas-action" title="Edit Basic Info"><i class="fas fa-pen-nib"></i></a>';
                }

                if ($user->can('delete-interview')) {
                    $btn .= '<button class="btn-saas-action delete-interview btn-delete" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash-alt text-danger"></i></button>';
                }

                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['cv', 'type', 'created_by_name', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new interview.
     */
    public function create()
    {
        $this->authorize('create-interview');
        $teams = \App\Models\Team::orderBy('name')->get(['id', 'name']);
        $jobs = JobPosting::where('status', 'Open')->orderBy('title')->get(['id', 'title', 'category']);
        return view('admin.interviews.create', compact('teams', 'jobs'));
    }

    /**
     * Store a newly created interview.
     */
    public function store(Request $request)
    {
        $this->authorize('create-interview');

        $request->validate([
            'name' => 'required|string|max:255',
            'cnic' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'experience' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'communication_skills' => 'nullable|string|max:255',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // max 5MB
            'cnic_front' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'cnic_back' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'interview_date' => 'nullable|date',
            'category' => 'nullable|in:BPO,Billing',
            'job_id' => 'nullable|exists:job_postings,id',
            'position_applied' => 'nullable|string|max:255',
        ]);

        $cvPath = null;
        if ($request->hasFile('cv')) {
            $cvPath = $request->file('cv')->store('cvs', 'public');
        }

        $cnicFrontPath = null;
        if ($request->hasFile('cnic_front')) {
            $cnicFrontPath = $request->file('cnic_front')->store('cnic_docs', 'public');
        }

        $cnicBackPath = null;
        if ($request->hasFile('cnic_back')) {
            $cnicBackPath = $request->file('cnic_back')->store('cnic_docs', 'public');
        }

        Interview::create([
            'interview_type' => $request->interview_type ?? 'Walk-in',
            'name' => $request->name,
            'cnic' => $request->cnic,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'experience' => $request->experience,
            'qualification' => $request->qualification,
            'reference' => $request->reference,
            'source' => $request->source,
            'communication_skills' => $request->communication_skills,
            'cv_path' => $cvPath,
            'cnic_front_path' => $cnicFrontPath,
            'cnic_back_path' => $cnicBackPath,
            'interviewers' => [],
            'remarks' => [],
            'status' => 'Scheduled',
            'interview_date' => $request->interview_date,
            'category' => $request->category ?? 'BPO',
            'job_posting_id' => $request->job_id,
            'position_applied' => $request->position_applied,
            'created_by' => auth()->id(),
            'assigned_to' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Interview scheduled successfully.']);
    }

    /**
     * Reassign/Move candidate handler to another user while preserving original creator record.
     */
    public function reassign(Request $request, $id)
    {
        $this->authorize('edit-interview');
        $interview = Interview::findOrFail($id);

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $oldHandler = $interview->assignedTo ? $interview->assignedTo->name : ($interview->creator ? $interview->creator->name : 'System');
        $newUser = User::findOrFail($request->assigned_to);

        if ((int)$interview->assigned_to === (int)$newUser->id) {
            return response()->json(['success' => true, 'message' => "Candidate is already assigned to {$newUser->name}."]);
        }

        $interview->assigned_to = $newUser->id;

        $currentInterviewers = is_array($interview->interviewers) ? $interview->interviewers : [];
        $currentRemarks = is_array($interview->remarks) ? $interview->remarks : [];
        $currentRoundDates = is_array($interview->round_dates) ? $interview->round_dates : [];

        $user = auth()->user()->name ?? 'System';
        $manualRemark = "Candidate moved/reassigned from '{$oldHandler}' to '{$newUser->name}' by {$user}.";

        $currentInterviewers[] = [$user];
        $currentRemarks[] = [$manualRemark];
        $currentRoundDates[] = now()->toDateTimeString();

        $interview->interviewers = $currentInterviewers;
        $interview->remarks = $currentRemarks;
        $interview->round_dates = $currentRoundDates;

        $interview->save();

        return response()->json([
            'success' => true,
            'message' => "Candidate handler moved to {$newUser->name}. Original creator record remained intact."
        ]);
    }

    /**
     * Show the Candidate Profile and Timeline.
     */
    public function updateTeamLead(Request $request, $id)
    {
        $this->authorize('edit-interview');
        $interview = Interview::findOrFail($id);

        $request->validate([
            'team_lead_id' => 'required|exists:users,id',
        ]);

        $interview->team_lead_id = $request->team_lead_id;
        $interview->save();

        return back()->with('success', 'Team Lead has been successfully updated.');
    }

    /**
     * Update training status from the training stage.
     */
    public function updateTrainingStatus(Request $request, $id)
    {
        $this->authorize('edit-interview');
        $interview = Interview::findOrFail($id);

        $request->validate([
            'training_status' => 'required|in:On Floor,Left',
            'reason' => 'required_if:training_status,Left|string|nullable'
        ]);

        $interview->training_status = $request->training_status;

        if ($request->training_status === 'Left') {
            $interview->status = 'Rejected';
            $interview->training_end_date = now();

            $currentInterviewers = is_array($interview->interviewers) ? $interview->interviewers : [];
            $currentRemarks = is_array($interview->remarks) ? $interview->remarks : [];
            $currentRoundDates = is_array($interview->round_dates) ? $interview->round_dates : [];

            $user = auth()->user()->name ?? 'System';
            $manualRemark = "Candidate left during training. Reason: " . $request->reason;

            $currentInterviewers[] = [$user];
            $currentRemarks[] = [$manualRemark];
            $currentRoundDates[] = now()->toDateTimeString();

            $interview->interviewers = $currentInterviewers;
            $interview->remarks = $currentRemarks;
            $interview->round_dates = $currentRoundDates;
        }

        $interview->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Training Status has been successfully updated.']);
        }

        return back()->with('success', 'Training Status has been successfully updated.');
    }

    public function show($id)
    {
        $this->authorize('view-interview');
        $interview = Interview::with('creator')->findOrFail($id);
        $teams = \App\Models\Team::orderBy('name')->get(['id', 'name']);
        $branches = \App\Models\Branch::orderBy('name')->get(['id', 'name']);
        return view('admin.interviews.show', compact('interview', 'teams', 'branches'));
    }

    /**
     * Conduct a round for an interview and process decision.
     */
    public function conductRound(Request $request, $id)
    {
        $this->authorize('edit-interview');
        $interview = Interview::findOrFail($id);

        $request->validate([
            'interviewers' => 'required|array',
            'interviewers.*' => 'required|string|max:255',
            'remarks' => 'required|array',
            'remarks.*' => 'nullable|string',
            'decision' => 'required|string|in:advance,hire,hire_only,reject,training,noshow,hold',
            'training_start_date' => 'nullable|required_if:decision,training|date',
            'team_lead_id' => 'nullable|required_if:decision,training|exists:users,id',
        ]);

        if ($request->decision === 'hire') {
            $request->validate([
                'team_id' => 'required|exists:teams,id',
                'branch_id' => 'required|exists:branches,id',
                'position' => 'required|string|max:255',
                'joining_date' => 'required|date',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);
        }

        // Current arrays
        $currentInterviewers = is_array($interview->interviewers) ? $interview->interviewers : [];
        $currentRemarks = is_array($interview->remarks) ? $interview->remarks : [];

        // Append new round
        $currentInterviewers[] = array_values(array_filter(array_map('trim', $request->interviewers)));
        $currentRemarks[] = array_values(array_map(function ($r) {
            return trim($r ?? '');
        }, $request->remarks));

        $currentRoundDates = is_array($interview->round_dates) ? $interview->round_dates : [];
        $currentRoundDates[] = now()->toDateTimeString();

        // Update arrays
        $interview->interviewers = $currentInterviewers;
        $interview->remarks = $currentRemarks;
        $interview->round_dates = $currentRoundDates;

        // Process Decision
        if ($request->decision === 'reject') {
            $interview->status = 'Rejected';
            $interview->training_end_date = now();
            $interview->save();
            return response()->json(['success' => true, 'message' => 'Interview round completed and candidate marked as Rejected.']);
        }

        if ($request->decision === 'noshow') {
            $interview->status = 'No Show';
            $interview->save();
            return response()->json(['success' => true, 'message' => 'Candidate marked as No Show. Pipeline finalized.']);
        }

        if ($request->decision === 'advance') {
            $interview->status = 'In Progress';
            $interview->save();
            return response()->json(['success' => true, 'message' => 'Interview round completed. Candidate advanced to the next stage.']);
        }

        if ($request->decision === 'hold') {
            $interview->status = 'On Hold';
            $interview->on_hold_date = now();
            $interview->save();
            return response()->json(['success' => true, 'message' => 'Interview round completed. Candidate placed on Hold.']);
        }

        if ($request->decision === 'training') {
            $interview->status = 'Training';
            $interview->training_start_date = $request->training_start_date;
            $interview->team_lead_id = $request->team_lead_id;
            $interview->save();
            return response()->json(['success' => true, 'message' => 'Interview round completed. Candidate moved to Training stage.']);
        }

        if ($request->decision === 'hire_only') {
            $interview->status = 'Hired';
            $interview->training_end_date = now();
            $interview->save();
            return response()->json(['success' => true, 'message' => 'Interview round completed. Candidate marked as Hired without onboarding.']);
        }

        if ($request->decision === 'hire') {
            // Auto onboard logic
            try {
                \DB::beginTransaction();

                // Create system user first
                $user = User::create([
                    'name' => $interview->name,
                    'email' => $request->email,
                    'password' => \Hash::make($request->password),
                ]);
                $user->assignRole('employee');

                // Create employee profile
                Employee::create([
                    'user_id' => $user->id,
                    'name' => $interview->name,
                    'cnic' => $interview->cnic,
                    'position' => $request->position,
                    'joining_date' => $request->joining_date,
                    'email' => $request->email,
                    'contact_no' => $interview->phone,
                    'team_id' => $request->team_id,
                    'branch_id' => $request->branch_id,
                    'status' => 1,
                ]);

                $interview->status = 'Onboarded';
                $interview->training_end_date = now();
                $interview->save();

                \DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Interview round completed. Candidate has been successfully hired and onboarded as an employee!'
                ]);

            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Round saved but failed to onboard candidate: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    /**
     * Change candidate status manually (override) with an audit trail note.
     */
    public function changeStatus(Request $request, $id)
    {
        $this->authorize('edit-interview');
        $interview = Interview::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:Pending,In Progress,On Hold,Training,Rejected,No Show',
            'training_start_date' => 'nullable|required_if:status,Training|date',
            'team_lead_id' => 'nullable|required_if:status,Training|exists:users,id',
            'remarks' => 'nullable|string'
        ]);

        $oldStatus = $interview->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return response()->json(['success' => true, 'message' => 'Candidate is already in ' . $newStatus . ' status.']);
        }

        $currentInterviewers = is_array($interview->interviewers) ? $interview->interviewers : [];
        $currentRemarks = is_array($interview->remarks) ? $interview->remarks : [];
        $currentRoundDates = is_array($interview->round_dates) ? $interview->round_dates : [];

        $user = auth()->user()->name ?? 'System';
        $manualRemark = "Status manually changed from '{$oldStatus}' to '{$newStatus}' by {$user}.";
        if ($request->filled('remarks')) {
            $manualRemark .= " Reason: " . $request->remarks;
        }

        $currentInterviewers[] = [$user];
        $currentRemarks[] = [$manualRemark];
        $currentRoundDates[] = now()->toDateTimeString();

        $interview->interviewers = $currentInterviewers;
        $interview->remarks = $currentRemarks;
        $interview->round_dates = $currentRoundDates;

        $interview->status = $newStatus;

        if ($newStatus === 'Training') {
            $interview->training_start_date = $request->training_start_date;
            $interview->team_lead_id = $request->team_lead_id;
            $interview->training_end_date = null; // Reset end date just in case
        } elseif ($oldStatus === 'Rejected') {
            $interview->training_end_date = null; // Reset termination flag if reopening
        }

        if ($newStatus === 'On Hold') {
            $interview->on_hold_date = now();
        }

        $interview->save();

        return response()->json(['success' => true, 'message' => "Candidate status successfully changed to {$newStatus}."]);
    }

    /**
     * Delete an interview.
     */
    public function destroy($id)
    {
        $this->authorize('delete-interview');
        $interview = Interview::findOrFail($id);

        if ($interview->cv_path) {
            Storage::disk('public')->delete($interview->cv_path);
        }
        if ($interview->cnic_front_path) {
            Storage::disk('public')->delete($interview->cnic_front_path);
        }
        if ($interview->cnic_back_path) {
            Storage::disk('public')->delete($interview->cnic_back_path);
        }

        $interview->delete();
        return response()->json(['success' => true, 'message' => 'Interview record deleted successfully.']);
    }

    /**
     * Check if CNIC belongs to a former/current employee or an existing interview candidate.
     */
    public function checkCnic(Request $request)
    {
        $request->validate([
            'cnic' => 'required|string'
        ]);

        $cnic = trim($request->cnic);
        $cnicDigits = preg_replace('/\D+/', '', $cnic);

        if ($cnicDigits === '' || strlen($cnicDigits) < 5) {
            return response()->json(['exists' => false]);
        }

        $matchCnic = function ($query) use ($cnic, $cnicDigits) {
            $query->where('cnic', $cnic)
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(cnic, '-', ''), ' ', ''), '_', '') = ?", [$cnicDigits]);
        };

        // Former / current employees (incl. soft-deleted)
        $employee = Employee::withTrashed()
            ->with(['team', 'branch'])
            ->where($matchCnic)
            ->first();

        if ($employee) {
            $statusText = 'Active';
            $statusTone = 'hired';
            if ($employee->deleted_at !== null) {
                $statusText = 'Deleted / Purged';
                $statusTone = 'rejected';
            } elseif ($employee->resign_date !== null) {
                $statusText = 'Resigned (' . $employee->resign_date . ')';
                $statusTone = 'rejected';
            } elseif ((int) $employee->status === 0) {
                $statusText = 'Inactive / Suspended';
                $statusTone = 'onhold';
            }

            return response()->json([
                'exists' => true,
                'source' => 'employee',
                'employee' => [
                    'name' => $employee->name,
                    'position' => $employee->position ?? 'N/A',
                    'email' => $employee->email ?? 'N/A',
                    'contact_no' => $employee->contact_no ?? 'N/A',
                    'joining_date' => $employee->joining_date ?? 'N/A',
                    'resign_date' => $employee->resign_date ?? 'N/A',
                    'status' => $statusText,
                    'status_tone' => $statusTone,
                    'team' => $employee->team ? $employee->team->name : 'N/A',
                    'branch' => $employee->branch ? $employee->branch->name : 'N/A',
                ],
            ]);
        }

        // Existing interview pipeline candidates with same CNIC
        $excludeId = $request->integer('exclude_id') ?: null;
        $interview = Interview::query()
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where($matchCnic)
            ->latest('id')
            ->first();

        if ($interview) {
            return response()->json([
                'exists' => true,
                'source' => 'interview',
                'employee' => [
                    'name' => $interview->name,
                    'position' => $interview->position_applied ?? ($interview->category ?? 'N/A'),
                    'email' => $interview->email ?? 'N/A',
                    'contact_no' => $interview->phone ?? 'N/A',
                    'joining_date' => optional($interview->created_at)->format('Y-m-d') ?? 'N/A',
                    'resign_date' => 'N/A',
                    'status' => 'Interview: ' . ($interview->status ?? 'Applied'),
                    'status_tone' => 'pending',
                    'team' => 'N/A',
                    'branch' => 'Pipeline record #' . $interview->id,
                ],
            ]);
        }

        return response()->json([
            'exists' => false,
        ]);
    }

    /**
     * Revert onboarding for a mistakenly onboarded candidate.
     */
    public function revertOnboarding($id)
    {
        $this->authorize('edit-interview');
        $interview = Interview::findOrFail($id);

        if ($interview->status !== 'Onboarded') {
            return response()->json(['success' => false, 'message' => 'Candidate is not in onboarded status.']);
        }

        try {
            \DB::beginTransaction();

            $employee = \App\Models\Employee::where('cnic', $interview->cnic)->first();

            if ($employee) {
                $user = \App\Models\User::find($employee->user_id);
                if ($user) {
                    $user->delete();
                }
                $employee->delete();
            }

            $interview->status = 'Hired';
            $interview->save();

            \DB::commit();

            return response()->json(['success' => true, 'message' => 'Onboarding reverted. Candidate marked as Hired. Employee and User records removed.']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to revert onboarding: ' . $e->getMessage()]);
        }
    }

    /**
     * Serve dynamic CV file content safely and securely bypassing directory symbolic link constraints.
     */
    public function viewCv($filename)
    {
        $this->authorize('view-interview');

        $path = 'cvs/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);

        return response($file, 200)->header('Content-Type', $type);
    }

    /**
     * Serve dynamic CNIC documents safely and securely bypassing directory symbolic link constraints.
     */
    public function viewCnicDoc($id, $side)
    {
        $this->authorize('view-interview');
        $interview = Interview::findOrFail($id);

        $path = ($side === 'front') ? $interview->cnic_front_path : $interview->cnic_back_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);

        return response($file, 200)->header('Content-Type', $type);
    }

    /**
     * Quick onboard candidate into employees
     */
    public function onboard(Request $request, $id)
    {
        $this->authorize('create-interview');
        $interview = Interview::findOrFail($id);

        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'branch_id' => 'required|exists:branches,id',
            'position' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        try {
            \DB::beginTransaction();

            // Create system user first
            $user = User::create([
                'name' => $interview->name,
                'email' => $request->email,
                'password' => \Hash::make($request->password),
            ]);
            $user->assignRole('employee');

            // Create employee profile
            Employee::create([
                'user_id' => $user->id,
                'name' => $interview->name,
                'cnic' => $interview->cnic,
                'position' => $request->position,
                'joining_date' => $request->joining_date,
                'email' => $request->email,
                'contact_no' => $interview->phone,
                'team_id' => $request->team_id,
                'branch_id' => $request->branch_id,
                'status' => 1,
                'cnic_front_path' => $interview->cnic_front_path,
                'cnic_back_path' => $interview->cnic_back_path,
            ]);

            // Update interview status
            $interview->update([
                'status' => 'Onboarded',
                'training_status' => 'Appointed'
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Candidate successfully onboarded as an active employee!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to onboard candidate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the public application form.
     */
    public function publicForm()
    {
        return view('public.apply');
    }

    /**
     * Store a public application.
     */
    public function storePublic(Request $request)
    {
        // 🛡️ Security: Honeypot — reject if the hidden field is filled (bots only)
        if ($request->filled('website')) {
            return response()->json([
                'success' => true,
                'message' => 'Your application has been submitted successfully.'
            ]); // Fake success to avoid tipping off the bot
        }

        // 🛡️ Security: Timing gate — reject if form submitted in under 2 seconds
        if ($request->has('_form_loaded_at')) {
            $loadedAt = (int) $request->_form_loaded_at;
            $now = round(microtime(true) * 1000);
            $timeTaken = $now - $loadedAt;

            // If time taken is negative, the user's clock is ahead of the server.
            // We only block bots that explicitly complete the form between 0 and 1999ms.
            if ($loadedAt > 0 && $timeTaken >= 0 && $timeTaken < 2000) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your application has been submitted successfully.'
                ]); // Fake success
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'source' => 'required|string|max:255',
            'communication_skills' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'category' => 'nullable|string|in:BPO,Billing',
            'job_id' => 'nullable|exists:job_postings,id',
            'position_applied' => 'nullable|string|max:255',
        ]);

        $lockKey = 'apply_lock_' . md5($request->email . $request->phone);
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            return response()->json([
                'message' => 'Processing request',
                'errors' => [
                    'email' => ['Your application is currently being processed. Please wait.']
                ]
            ], 422);
        }

        try {
            // Prevent duplicate active applications
            $activeStatuses = ['Applied', 'Scheduled', 'In Progress', 'Training', 'On Hold', 'Selected'];
            $duplicate = Interview::whereIn('status', $activeStatuses)
                ->where(function ($query) use ($request) {
                    $query->where('email', $request->email)
                        ->orWhere('phone', $request->phone);

                    if ($request->filled('cnic')) {
                        $query->orWhere('cnic', $request->cnic);
                    }
                })
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Duplicate application',
                    'errors' => [
                        'email' => ['Your application has already been submitted and is currently under review. Please wait for our recruitment team to contact you.']
                    ]
                ], 422);
            }

            $cvPath = null;
            if ($request->hasFile('cv')) {
                $cvPath = $request->file('cv')->store('cvs', 'public');
            }

            Interview::create([
                'interview_type' => 'Online Application',
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'qualification' => $request->qualification,
                'experience' => $request->experience,
                'reference' => $request->reference,
                'source' => $request->source,
                'communication_skills' => $request->communication_skills,
                'address' => $request->address,
                'cv_path' => $cvPath,
                'status' => 'Applied',
                'interviewers' => [],
                'remarks' => [],
                'category' => $request->category ?? 'BPO',
                'job_posting_id' => $request->job_id,
                'position_applied' => $request->position_applied,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your application has been submitted successfully. Our HR team will contact you soon.'
            ]);
        } finally {
            $lock->release();
        }
    }
}
