@extends('admin.layouts.app')

@section('title', 'Candidate Profile & Timeline')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title interviews-header">Candidate Profile & Pipeline</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.interviews.index') }}">Interviews</a></li>
        <li class="breadcrumb-item active">Timeline</li>
    </ol>
</div>
@endsection

@section('button')
    @php
        $backUrl = route('admin.interviews.index');
        if (request()->has('tab')) {
            $backUrl .= '?tab=' . request('tab');
        }
    @endphp
    <a class="btn-premium-back" href="{{ $backUrl }}">
        <i class="fas fa-arrow-left mr-2"></i> Back to Directory
    </a>
@endsection

@section('content')
<div class="container-fluid interview-show-page py-4">
<div class="row">
    <!-- Left: Profile Info -->
    <div class="col-lg-4">
        <div class="candidate-card-premium">
            <div class="profile-hero">
                <div class="avatar-saas">
                    {{ strtoupper(substr($interview->name, 0, 1)) }}
                </div>
                <h2 class="font-weight-bold mb-2">{{ $interview->name }}</h2>
                @php
                    $statusClass = 'status-pending';
                    if($interview->status == 'Onboarded' || $interview->status == 'Hired') $statusClass = 'status-hired';
                    elseif($interview->status == 'Rejected' || $interview->status == 'No Show') $statusClass = 'status-rejected';
                    elseif($interview->status == 'On Hold') $statusClass = 'status-onhold';
                @endphp
                <div class="status-pill {{ $statusClass }}">
                    <i class="fas fa-circle mr-2" style="font-size: 0.5rem"></i> {{ $interview->status }}
                </div>
            </div>

            <div class="row">
                <div class="col-6 info-group">
                    <div class="info-label"><i class="fas fa-id-card"></i> CNIC</div>
                    <div class="info-value">{{ $interview->cnic }}</div>
                </div>
                <div class="col-6 info-group">
                    <div class="info-label"><i class="fas fa-phone"></i> Phone</div>
                    <div class="info-value">{{ $interview->phone }}</div>
                </div>
                <div class="col-12 info-group">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="info-value">{{ $interview->email ?: 'N/A' }}</div>
                </div>
                <div class="col-6 info-group">
                    <div class="info-label"><i class="fas fa-graduation-cap"></i> Quals</div>
                    <div class="info-value">{{ $interview->qualification ?: 'N/A' }}</div>
                </div>
                <div class="col-6 info-group">
                    <div class="info-label"><i class="fas fa-history"></i> Experience</div>
                    <div class="info-value">{{ $interview->experience ?: 'N/A' }}</div>
                </div>
            </div>

            <div class="mt-4 p-4 rounded-3xl" style="background: #f1f5f9; border-radius: 24px;">
                <div class="info-label mb-2"><i class="fas fa-info-circle"></i> Origin & Audit</div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small font-weight-bold text-muted">Interview Type:</span>
                    <span class="tw-badge-muted shadow-sm border px-2 py-1">{{ $interview->interview_type ?: 'Walk-in' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small font-weight-bold text-muted">Current Handler:</span>
                    <span class="small font-weight-bold text-primary">{{ $interview->assignedTo ? $interview->assignedTo->name : ($interview->creator ? $interview->creator->name : 'System') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small font-weight-bold text-muted">Original Creator:</span>
                    <span class="small font-weight-bold text-dark">{{ $interview->creator ? $interview->creator->name : 'System' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="small font-weight-bold text-muted">Recorded On:</span>
                    <span class="small font-weight-bold text-dark">{{ $interview->created_at->format('d M, Y') }}</span>
                </div>
            </div>

            <div class="mt-5 space-y-3 d-flex flex-column gap-3">
                @if($interview->cv_path)
                    <a href="{{ route('admin.interviews.view-cv', basename($interview->cv_path)) }}" target="_blank" class="btn-outline-premium">
                        <i class="fas fa-file-pdf text-danger"></i> View Resume / CV
                    </a>
                @endif
                @if($interview->cnic_front_path)
                    <a href="{{ route('admin.interviews.view-cnic-doc', ['id' => $interview->id, 'side' => 'front']) }}" target="_blank" class="btn-outline-premium">
                        <i class="fas fa-image text-primary"></i> CNIC Front Side
                    </a>
                @endif
                @if($interview->cnic_back_path)
                    <a href="{{ route('admin.interviews.view-cnic-doc', ['id' => $interview->id, 'side' => 'back']) }}" target="_blank" class="btn-outline-premium">
                        <i class="fas fa-image text-primary"></i> CNIC Back Side
                    </a>
                @endif
                @if($interview->status === 'Onboarded')
                    <button type="button" id="undo-onboarding-btn" class="btn btn-outline-danger w-100 mt-2">
                        <i class="fas fa-undo mr-2"></i> Undo Onboarding (Record Only)
                    </button>
                    <form id="undo-onboarding-form" action="{{ route('admin.interviews.revertOnboarding', $interview->id) }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Right: Pipeline Timeline -->
    <div class="col-lg-8">
        <div class="candidate-card-premium">
            <h4 class="font-weight-800 mb-5 d-flex align-items-center gap-3">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><i class="fas fa-project-diagram"></i></span>
                Interactive Interview Pipeline
            </h4>

            <div class="pipeline-container">
                <!-- Step 1: Entry -->
                <div class="pipeline-step">
                    <div class="step-dot active"><i class="fas fa-check" style="font-size: 0.6rem"></i></div>
                    <div class="step-card">
                        <div class="step-header">
                            <h5 class="step-title">Interview Record Created</h5>
                            <span class="step-date">{{ $interview->created_at->format('M j, Y — g:i a') }}</span>
                        </div>
                        <p class="text-muted small mb-0">The candidate was officially registered for a <strong>{{ $interview->interview_type ?: 'Walk-in' }}</strong> interview.</p>
                    </div>
                </div>

                <!-- Step Rounds -->
                @php
                    $interviewers = is_array($interview->interviewers) ? $interview->interviewers : [];
                    $remarks = is_array($interview->remarks) ? $interview->remarks : [];
                @endphp

                @foreach($interviewers as $index => $roundInterviewers)
                    <div class="pipeline-step">
                        <div class="step-dot active"><i class="fas fa-check" style="font-size: 0.6rem"></i></div>
                        <div class="step-card">
                            <div class="step-header">
                                <h5 class="step-title">Round {{ $index + 1 }} Assessment</h5>
                                <span class="step-date">{{ isset($interview->round_dates[$index]) ? \Carbon\Carbon::parse($interview->round_dates[$index])->format('M j, Y — g:i a') : 'N/A' }}</span>
                            </div>
                            
                            @php
                                if (!is_array($roundInterviewers)) $roundInterviewers = [$roundInterviewers];
                                $roundRemarks = isset($remarks[$index]) ? $remarks[$index] : [];
                                if (!is_array($roundRemarks)) $roundRemarks = [$roundRemarks];
                            @endphp

                            @foreach($roundInterviewers as $pIndex => $panelMember)
                                <div class="feedback-bubble">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div class="p-1 bg-white rounded-circle border shadow-sm"><i class="fas fa-user-circle text-muted"></i></div>
                                        <span class="font-weight-bold small text-dark">{{ $panelMember }}</span>
                                    </div>
                                    <p class="small text-muted mb-0 italic">"{{ $roundRemarks[$pIndex] ?: 'No specific notes provided.' }}"</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Dynamic Next Action or Final Result -->
                @if($interview->status === 'Training')
                    <div class="pipeline-step">
                        <div class="step-dot" style="background:#f0fdfa; border-color:#0d9488; color:#0f766e"><i class="fas fa-chalkboard-teacher" style="font-size: 0.6rem"></i></div>
                        <div class="step-card" style="border: 1px solid #ccfbf1; background: #f0fdfa">
                            <div class="py-3 px-2">
                                <h5 class="font-weight-800 text-teal-700 mb-2">Training Assignment</h5>
                                <p class="text-muted small mb-3">Candidate is currently in the training phase. You can assign or update the Team Lead supervising this candidate.</p>
                                
                                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                                    <form action="{{ route('admin.interviews.updateTeamLead', $interview->id) }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center">
                                        @csrf
                                        <select class="form-control" name="team_lead_id" style="max-width: 250px;" required>
                                            <option value="">Select Team Lead...</option>
                                            @php
                                                $teamLeads = collect();
                                                if (Spatie\Permission\Models\Role::where('name', 'team-lead')->exists()) {
                                                    $teamLeads = \App\Models\User::role('team-lead')->get();
                                                }
                                            @endphp
                                            @foreach($teamLeads as $lead)
                                                <option value="{{ $lead->id }}" {{ $interview->team_lead_id == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary shadow-sm" style="border-radius: 6px; padding: 7px 15px;"><i class="fas fa-save mr-1"></i> Update Team Lead</button>
                                    </form>

                                    <form action="{{ route('admin.interviews.updateTrainingStatus', $interview->id) }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center" id="training-status-form">
                                        @csrf
                                        <select class="form-control" name="training_status" id="training_status_select" style="max-width: 150px;" required>
                                            <option value="">Training Status...</option>
                                            <option value="On Floor" {{ $interview->training_status == 'On Floor' ? 'selected' : '' }}>On Floor</option>
                                            <option value="Left" {{ $interview->training_status == 'Left' ? 'selected' : '' }}>Left</option>
                                            <option value="Appointed" {{ $interview->training_status == 'Appointed' ? 'selected' : '' }}>Appointed</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-info shadow-sm" style="border-radius: 6px; padding: 7px 15px;" id="update-training-status-btn"><i class="fas fa-sync-alt mr-1"></i> Update Status</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($interview->status !== 'Rejected' && $interview->status !== 'Onboarded' && $interview->status !== 'No Show')
                    <div class="pipeline-step">
                        <div class="step-dot" style="background:#fefce8; border-color:#eab308; color:#a16207"><i class="fas fa-play" style="font-size: 0.6rem"></i></div>
                        <div class="step-card" style="border: 2px dashed var(--saas-primary); background: #f5f3ff">
                            <div class="text-center py-4">
                                <h5 class="font-weight-800 text-indigo-600 mb-2">Awaiting Intelligence Assessment</h5>
                                <p class="text-muted small mb-4">Record feedback for the current round to advance the candidate through the funnel.</p>
                                <button class="btn-premium max-w-sm" style="width:auto" data-toggle="modal" data-target="#conductRoundModal">
                                    <i class="fas fa-bolt"></i> Record Round Feedback
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif($interview->status === 'No Show')
                    <div class="pipeline-step">
                        <div class="step-dot" style="background:#f1f5f9; border-color:#64748b; color:#1e293b"><i class="fas fa-user-slash" style="font-size: 0.6rem"></i></div>
                        <div class="step-card" style="border: 1px solid #cbd5e1; background: #f8fafc">
                            <h5 class="step-title text-slate-600">Candidate No-Show</h5>
                            <p class="text-muted small mb-0 mt-2">Candidate did not appear for the scheduled engagement. Record was archived on {{ $interview->updated_at->format('M j, Y') }}.</p>
                        </div>
                    </div>
                @elseif($interview->status === 'Rejected')
                    <div class="pipeline-step">
                        <div class="step-dot" style="background:#fef2f2; border-color:#ef4444; color:#b91c1c"><i class="fas fa-times" style="font-size: 0.6rem"></i></div>
                        <div class="step-card" style="border: 1px solid #fee2e2; background: #fffafb">
                            <h5 class="step-title text-danger">Application Rejected</h5>
                            <p class="text-muted small mb-3 mt-2">Based on the assessments, the candidate's profile did not meet our current requirements.</p>
                            @can('edit-interview')
                            <button class="btn btn-sm btn-outline-danger mt-1" data-toggle="modal" data-target="#changeStatusModal">
                                <i class="fas fa-undo mr-1"></i> Re-evaluate / Change Status
                            </button>
                            @endcan
                        </div>
                    </div>
                @elseif($interview->status === 'Onboarded')
                    <div class="pipeline-step">
                        <div class="step-dot" style="background:#f0fdf4; border-color:#10b981; color:#047857"><i class="fas fa-star" style="font-size: 0.6rem"></i></div>
                        <div class="step-card" style="border: 1px solid #d1fae5; background: #fafffc">
                            <h5 class="step-title text-success">Engagment Finalized — Hired</h5>
                            <p class="text-muted small mb-0 mt-2">Candidate has successfully cleared all rounds and transition to official onboarding on {{ $interview->updated_at->format('M j, Y') }}.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Premium Conduct Round Modal -->
<div class="modal fade" id="conductRoundModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered conduct-round-dialog" role="document">
        <div class="modal-content conduct-round-modal">
            <div class="conduct-round-header">
                <div class="conduct-round-header-glow" aria-hidden="true"></div>
                <div class="conduct-round-header-inner">
                    <div class="conduct-round-icon" aria-hidden="true">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="conduct-round-heading">
                        <p class="conduct-round-kicker">Interview pipeline</p>
                        <h5 class="conduct-round-title">Record Round Feedback</h5>
                        <p class="conduct-round-subtitle">
                            Intelligence assessment for
                            <span class="conduct-round-candidate">{{ $interview->name }}</span>
                        </p>
                    </div>
                    <button type="button" class="conduct-round-close" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <form id="conduct-round-form">
                @csrf
                <div class="modal-body conduct-round-body">
                    <section class="conduct-round-section">
                        <div class="conduct-round-section-head">
                            <span class="conduct-round-step">1</span>
                            <div>
                                <h6 class="conduct-round-section-title">Panel assessors</h6>
                                <p class="conduct-round-section-hint">Capture who interviewed and their notes</p>
                            </div>
                        </div>
                        <div id="panel-members-container" class="conduct-round-panels"></div>
                        <button type="button" class="conduct-round-add-btn" id="add-panel-member-btn">
                            <i class="fas fa-plus"></i>
                            Add another interviewer
                        </button>
                    </section>

                    <section class="conduct-round-section conduct-round-section--outcome">
                        <div class="conduct-round-section-head">
                            <span class="conduct-round-step conduct-round-step--accent">2</span>
                            <div>
                                <h6 class="conduct-round-section-title">Final outcome</h6>
                                <p class="conduct-round-section-hint">Decide how this candidate moves next</p>
                            </div>
                        </div>

                        <div class="conduct-round-field">
                            <label class="conduct-round-label" for="decision-select">Assessment decision</label>
                            <select class="conduct-round-control" name="decision" id="decision-select" required>
                                <option value="" disabled selected>Select an outcome…</option>
                                <option value="advance">Advance — proceed to next round</option>
                                <option value="hold">On hold — keep in pipeline</option>
                                <option value="training">Training — 1-week test onboard</option>
                                <option value="hire">Hire — immediate final selection</option>
                                <option value="hire_only">Hire — record only (no onboarding)</option>
                                <option value="noshow">Absence — mark as no-show</option>
                                <option value="reject">Decline — do not proceed</option>
                            </select>
                        </div>

                        <div id="onboard-fields" class="conduct-round-extra" style="display:none">
                            <div class="conduct-round-extra-head">
                                <i class="fas fa-user-check"></i>
                                <span>Enterprise onboarding profile</span>
                            </div>
                            <div class="conduct-round-grid">
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Joining date</label>
                                    <input type="date" class="conduct-round-control" name="joining_date">
                                </div>
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Position title</label>
                                    <input type="text" class="conduct-round-control" name="position" placeholder="e.g. Sales Executive">
                                </div>
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Team</label>
                                    <select class="conduct-round-control" name="team_id">
                                        @foreach($teams as $team) <option value="{{ $team->id }}">{{ $team->name }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Branch</label>
                                    <select class="conduct-round-control" name="branch_id">
                                        @foreach($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->name }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Portal email</label>
                                    <input type="email" class="conduct-round-control" name="email" value="{{ $interview->email }}">
                                </div>
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Portal password</label>
                                    <input type="text" class="conduct-round-control" name="password" placeholder="Leave blank to auto-generate">
                                </div>
                            </div>
                        </div>

                        <div id="training-fields-container" class="conduct-round-extra" style="display:none">
                            <div class="conduct-round-extra-head">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Training Assignment</span>
                            </div>
                            <div class="conduct-round-grid">
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Training start date</label>
                                    <input type="date" class="conduct-round-control" name="training_start_date" id="training_start_date_input">
                                </div>
                                <div class="conduct-round-field">
                                    <label class="conduct-round-label">Assign Team Lead</label>
                                    <select class="conduct-round-control" name="team_lead_id" id="team_lead_select">
                                        <option value="" disabled selected>Select a Team Lead…</option>
                                        @php
                                            $teamLeads = collect();
                                            if (Spatie\Permission\Models\Role::where('name', 'team-lead')->exists()) {
                                                $teamLeads = \App\Models\User::role('team-lead')->get();
                                            }
                                        @endphp
                                        @foreach($teamLeads as $lead)
                                            <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="conduct-round-footer">
                    <button type="button" class="conduct-round-cancel" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="conduct-round-submit" id="save-round-btn">
                        Finalize assessment
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Premium Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="position-relative p-4 pb-3" style="background-color: #fef2f2; border-bottom: 1px solid #fee2e2;">
                <button type="button" class="close position-absolute" data-dismiss="modal" aria-label="Close" style="top: 16px; right: 20px; color: #9ca3af; text-shadow: none; opacity: 1;">
                    <span aria-hidden="true"><i class="fas fa-times"></i></span>
                </button>
                <div class="text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #fee2e2; color: #ef4444; border-radius: 50%;">
                        <i class="fas fa-undo-alt" style="font-size: 1.25rem;"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1" style="color: #111827; font-size: 1.25rem;">Re-evaluate Candidate</h5>
                    <p class="mb-0 text-muted small">Update recruitment status for <span class="font-weight-bold text-dark">{{ $interview->name }}</span></p>
                </div>
            </div>

            <form id="change-status-form">
                @csrf
                <div class="modal-body p-4" style="background-color: #ffffff;">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark small mb-2" for="change-status-select">New Status <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="change-status-select" required style="border-radius: 8px; border: 1px solid #d1d5db; padding: 0.6rem 1rem; height: auto; font-size: 0.95rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                            <option value="" disabled selected>Select an appropriate stage…</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Training">Training</option>
                            <option value="Rejected">Rejected</option>
                            <option value="No Show">No Show</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark small mb-2">Reason / Remarks <span class="text-muted font-weight-normal">(Optional)</span></label>
                        <textarea class="form-control" name="remarks" rows="3" placeholder="Provide context on why this application is being reopened or updated..." style="border-radius: 8px; border: 1px solid #d1d5db; padding: 0.75rem 1rem; font-size: 0.95rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); resize: none;"></textarea>
                    </div>

                    <div id="cs-training-fields-container" class="mt-4 p-3" style="display:none; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                        <h6 class="font-weight-bold text-dark small mb-3 border-bottom pb-2"><i class="fas fa-chalkboard-teacher text-primary mr-2"></i>Training Assignment Setup</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="font-weight-bold text-dark" style="font-size: 0.8rem;">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="training_start_date" id="cs_training_start_date_input" style="border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.85rem;">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark" style="font-size: 0.8rem;">Team Lead <span class="text-danger">*</span></label>
                                <select class="form-control" name="team_lead_id" id="cs_team_lead_select" style="border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.85rem;">
                                    <option value="" disabled selected>Select Supervisor…</option>
                                    @php
                                        $teamLeads = collect();
                                        if (Spatie\Permission\Models\Role::where('name', 'team-lead')->exists()) {
                                            $teamLeads = \App\Models\User::role('team-lead')->get();
                                        }
                                    @endphp
                                    @foreach($teamLeads as $lead)
                                        <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3" style="background-color: #f9fafb; border-top: 1px solid #f3f4f6; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-light border font-weight-bold text-muted shadow-sm" data-dismiss="modal" style="border-radius: 8px; padding: 0.5rem 1rem;">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold shadow-sm d-flex align-items-center" id="save-status-btn" style="border-radius: 8px; padding: 0.5rem 1.25rem;">
                        <i class="fas fa-check-circle mr-2"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<!-- Premium Appointed Onboarding Modal -->
<div class="modal fade" id="appointOnboardModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 600px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="position-relative p-4 pb-3" style="background-color: #f0fdf4; border-bottom: 1px solid #d1fae5;">
                <button type="button" class="close position-absolute" data-dismiss="modal" aria-label="Close" style="top: 16px; right: 20px; color: #9ca3af; text-shadow: none; opacity: 1;">
                    <span aria-hidden="true"><i class="fas fa-times"></i></span>
                </button>
                <div class="text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #d1fae5; color: #10b981; border-radius: 50%;">
                        <i class="fas fa-user-check" style="font-size: 1.25rem;"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1" style="color: #111827; font-size: 1.25rem;">Appoint Candidate</h5>
                    <p class="mb-0 text-muted small">Complete enterprise onboarding profile for <span class="font-weight-bold text-dark">{{ $interview->name }}</span></p>
                </div>
            </div>

            <form id="appoint-onboard-form">
                @csrf
                <div class="modal-body p-4" style="background-color: #ffffff;">
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark small mb-2">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="joining_date" required style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.95rem;">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark small mb-2">Position Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="position" placeholder="e.g. Sales Executive" required style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.95rem;">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark small mb-2">Team <span class="text-danger">*</span></label>
                            <select class="form-control" name="team_id" required style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.95rem;">
                                @foreach($teams as $team) <option value="{{ $team->id }}">{{ $team->name }}</option> @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark small mb-2">Branch <span class="text-danger">*</span></label>
                            <select class="form-control" name="branch_id" required style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.95rem;">
                                @foreach($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->name }}</option> @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark small mb-2">Portal Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="{{ $interview->email }}" required style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.95rem;">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark small mb-2">Portal Password <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="password" placeholder="Min 8 characters" required style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.95rem;" minlength="8">
                        </div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3" style="background-color: #f9fafb; border-top: 1px solid #f3f4f6; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-light border font-weight-bold text-muted shadow-sm" data-dismiss="modal" style="border-radius: 8px; padding: 0.5rem 1rem;">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-bold shadow-sm d-flex align-items-center" id="save-appoint-btn" style="border-radius: 8px; padding: 0.5rem 1.25rem;">
                        <i class="fas fa-check-circle mr-2"></i> Confirm Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Premium Left Reason Modal -->
<div class="modal fade" id="leftReasonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="position-relative p-4 pb-3" style="background-color: #fef2f2; border-bottom: 1px solid #fee2e2;">
                <button type="button" class="close position-absolute" data-dismiss="modal" aria-label="Close" style="top: 16px; right: 20px; color: #9ca3af; text-shadow: none; opacity: 1;">
                    <span aria-hidden="true"><i class="fas fa-times"></i></span>
                </button>
                <div class="text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #fee2e2; color: #ef4444; border-radius: 50%;">
                        <i class="fas fa-sign-out-alt" style="font-size: 1.25rem;"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1" style="color: #111827; font-size: 1.25rem;">Candidate Left During Training</h5>
                    <p class="mb-0 text-muted small">Please provide the reason why <span class="font-weight-bold text-dark">{{ $interview->name }}</span> left.</p>
                </div>
            </div>

            <form id="left-reason-form">
                @csrf
                <input type="hidden" name="training_status" value="Left">
                <div class="modal-body p-4" style="background-color: #ffffff;">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark small mb-2">Reason for Leaving <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Provide reason why candidate left..." style="border-radius: 8px; border: 1px solid #d1d5db; padding: 0.75rem 1rem; font-size: 0.95rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); resize: none;"></textarea>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3" style="background-color: #f9fafb; border-top: 1px solid #f3f4f6; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-light border font-weight-bold text-muted shadow-sm" data-dismiss="modal" style="border-radius: 8px; padding: 0.5rem 1rem;">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold shadow-sm d-flex align-items-center" id="save-left-btn" style="border-radius: 8px; padding: 0.5rem 1.25rem;">
                        <i class="fas fa-check-circle mr-2"></i> Mark as Left
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function addPanelMemberInput() {
        const index = $('.panel-member-input-row').length + 1;
        const html = `
            <div class="panel-member-input-row conduct-round-panel">
                <div class="conduct-round-panel-head">
                    <span class="conduct-round-panel-label">
                        <i class="fas fa-user-tie"></i>
                        Assessor ${index}
                    </span>
                    <button type="button" class="conduct-round-remove remove-panel-input-btn" aria-label="Remove">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <div class="conduct-round-field">
                    <label class="conduct-round-label">Interviewer name</label>
                    <input type="text" class="conduct-round-control" name="interviewers[]" placeholder="e.g. Uzair (HR Lead)" required>
                </div>
                <div class="conduct-round-field">
                    <label class="conduct-round-label">Feedback notes</label>
                    <textarea class="conduct-round-control conduct-round-notes" name="remarks[]" rows="3" placeholder="Strengths, gaps, culture fit, recommendation…"></textarea>
                </div>
            </div>
        `;
        $('#panel-members-container').append(html);
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        const rows = $('.panel-member-input-row');
        rows.each(function(i) {
            $(this).find('.conduct-round-panel-label').html('<i class="fas fa-user-tie"></i> Assessor ' + (i + 1));
        });
        $('.remove-panel-input-btn').toggle(rows.length > 1);
    }

    $('#conductRoundModal').on('show.bs.modal', function() {
        $('#panel-members-container').empty();
        addPanelMemberInput();
        $('#onboard-fields').hide();
        $('#training-fields-container').hide();
        $('#decision-select').val('');
    });

    $('#add-panel-member-btn').click(addPanelMemberInput);
    $(document).on('click', '.remove-panel-input-btn', function() {
        $(this).closest('.panel-member-input-row').remove();
        updateRemoveButtons();
    });

    $('#decision-select').change(function() {
        const val = $(this).val();
        $('#onboard-fields').toggle(val === 'hire');
        $('#training-fields-container').toggle(val === 'training');
        $('#onboard-fields input, #onboard-fields select').prop('required', val === 'hire');
        $('#training_start_date_input').prop('required', val === 'training');
        $('#team_lead_select').prop('required', val === 'training');
    });

    $('#conduct-round-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#save-round-btn');
        btn.html('<i class="fas fa-circle-notch fa-spin"></i> Saving…').prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.interviews.conductRound', $interview->id) }}",
            method: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    const urlParams = new URLSearchParams(window.location.search);
                    const tab = urlParams.get('tab');
                    let redirectUrl = "{{ route('admin.interviews.index') }}";
                    if (tab) {
                        redirectUrl += '?tab=' + tab;
                    }
                    window.location.href = redirectUrl;
                }
            },
            error: function(err) {
                btn.html('Finalize assessment <i class="fas fa-arrow-right"></i>').prop('disabled', false);
                toastr.error(err.responseJSON?.message || 'Error submitting assessment.');
            }
        });
    });

    // Change Status Logic
    $('#changeStatusModal').on('show.bs.modal', function() {
        $('#cs-training-fields-container').hide();
        $('#change-status-select').val('');
        $('#change-status-form')[0].reset();
    });

    $('#change-status-select').change(function() {
        const val = $(this).val();
        $('#cs-training-fields-container').toggle(val === 'Training');
        $('#cs_training_start_date_input').prop('required', val === 'Training');
        $('#cs_team_lead_select').prop('required', val === 'Training');
    });

    $('#change-status-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#save-status-btn');
        btn.html('<i class="fas fa-circle-notch fa-spin"></i> Saving…').prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.interviews.changeStatus', $interview->id) }}",
            method: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    const urlParams = new URLSearchParams(window.location.search);
                    const tab = urlParams.get('tab');
                    let redirectUrl = "{{ route('admin.interviews.index') }}";
                    if (tab) {
                        redirectUrl += '?tab=' + tab;
                    }
                    window.location.href = redirectUrl;
                }
            },
            error: function(err) {
                btn.html('Update Status <i class="fas fa-check"></i>').prop('disabled', false);
                toastr.error(err.responseJSON?.message || 'Error updating status.');
            }
        });
    });

    // Training Status Logic
    $('#update-training-status-btn').click(function(e) {
        e.preventDefault();
        const status = $('#training_status_select').val();
        if (!status) {
            toastr.error('Please select a training status.');
            return;
        }

        if (status === 'Appointed') {
            $('#appointOnboardModal').modal('show');
        } else if (status === 'Left') {
            $('#leftReasonModal').modal('show');
        } else {
            $('#training-status-form').submit();
        }
    });

    $('#left-reason-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#save-left-btn');
        btn.html('<i class="fas fa-circle-notch fa-spin"></i> Processing…').prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.interviews.updateTrainingStatus', $interview->id) }}",
            method: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    location.reload();
                }
            },
            error: function(err) {
                btn.html('<i class="fas fa-check-circle mr-2"></i> Mark as Left').prop('disabled', false);
                toastr.error(err.responseJSON?.message || 'Error processing status update.');
            }
        });
    });

    $('#appoint-onboard-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#save-appoint-btn');
        btn.html('<i class="fas fa-circle-notch fa-spin"></i> Processing…').prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.interviews.onboard', $interview->id) }}",
            method: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    location.reload();
                }
            },
            error: function(err) {
                btn.html('<i class="fas fa-check-circle mr-2"></i> Confirm Appointment').prop('disabled', false);
                toastr.error(err.responseJSON?.message || 'Error processing appointment.');
            }
        });
    });
    $('#undo-onboarding-btn').click(function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to undo onboarding? This will permanently remove their employee and user records from the system.')) {
            const btn = $(this);
            btn.html('<i class="fas fa-circle-notch fa-spin"></i> Processing...').prop('disabled', true);
            
            $.ajax({
                url: $('#undo-onboarding-form').attr('action'),
                method: 'POST',
                data: $('#undo-onboarding-form').serialize(),
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        btn.html('<i class="fas fa-undo mr-2"></i> Undo Onboarding (Record Only)').prop('disabled', false);
                        toastr.error(res.message);
                    }
                },
                error: function(err) {
                    btn.html('<i class="fas fa-undo mr-2"></i> Undo Onboarding (Record Only)').prop('disabled', false);
                    toastr.error(err.responseJSON?.message || 'Error reverting onboarding.');
                }
            });
        }
    });
});
</script>
@endpush
