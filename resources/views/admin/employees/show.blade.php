@extends('admin.layouts.app')

@section('title', 'Employee Spotlight')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title directory-header">Employee Spotlight</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Workforce</a></li>
        <li class="breadcrumb-item active">Profile Spotlight</li>
    </ol>
</div>
@endsection

@section('button')
    <div class="d-flex gap-2">
        <a class="btn btn-premium-edit" style="background: #10b981; border-color: #10b981;"
            href="{{ route('admin.employees.export', $employee->id) }}">
            <i class="fas fa-file-excel mr-2"></i> Download Excel
        </a>
        @can('edit-employee')
            <a class="btn btn-premium-edit" href="{{ route('admin.employees.edit', $employee->id) }}">
                <i class="fas fa-magic mr-2"></i> Refine Talent
            </a>
        @endcan
        <a class="btn btn-premium-back" href="{{ route('admin.employees.index') }}">
            <i class="fas fa-chevron-left mr-2"></i> Return
        </a>
    </div>
@endsection

@section('content')

<div class="container-fluid employee-spotlight-page">
    <div class="spotlight-container">

        @include('includes.messages')

        <!-- Hero Section -->
        <div class="profile-hero">
            <div class="hero-avatar-wrapper">
                <img src="{{ $employee->profile_pic_url }}" class="hero-avatar" alt="{{ $employee->name }}">
            </div>
            <div class="hero-content">
                <h1 class="hero-name">{{ $employee->name }}</h1>
                <div class="hero-meta">
                    <div class="hero-badge">
                        <i class="fas fa-briefcase"></i> {{ $employee->position }}
                    </div>
                    <div class="hero-badge">
                        <i class="fas fa-fingerprint"></i> EMP-{{ $employee->id }}
                    </div>
                    <div class="hero-badge">
                        <i class="fas fa-map-marker-alt"></i> {{ $employee->branch->name ?? 'Main Hub' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Col 1: Profile & Connectivity -->
            <div class="col-lg-4">
                <div class="glass-card">
                    <div class="card-label-top">
                        <div class="icon-box"><i class="fas fa-user-check"></i></div>
                        Identity & Contact
                    </div>
                    <div class="info-group">
                        <div class="info-label">Communication Channel</div>
                        <div class="info-value"><a href="mailto:{{ $employee->email }}" class="email-link">{{ $employee->email }}</a></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Active Contact</div>
                        <div class="info-value">{{ $employee->contact_no }}</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Emergency Protocol</div>
                        <div class="info-value text-danger">{{ $employee->emergency_no }}</div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="info-group">
                                <div class="info-label">Gender</div>
                                <div class="info-value" style="text-transform: capitalize;">{{ $employee->gender }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-group">
                                <div class="info-label">Birth Date</div>
                                <div class="info-value">{{ $employee->dob ? date('d M, Y', strtotime($employee->dob)) : 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->hasRole(['admin', 'administrator']))
                        @if($employee->cnic_front_path || $employee->cnic_back_path)
                            <div class="mt-4 pt-4 border-top" style="border-color: #f1f5f9 !important;">
                                <div class="info-label mb-2">Identity Documents</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @if($employee->cnic_front_path)
                                        <a href="{{ route('admin.employees.cnic.view', [$employee->id, 'front']) }}" target="_blank" class="badge bg-soft-primary text-primary px-3 py-2 rounded-lg" style="background: #e0e7ff; border: 1px solid #c7d2fe; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-id-card"></i> CNIC Front
                                        </a>
                                    @endif
                                    @if($employee->cnic_back_path)
                                        <a href="{{ route('admin.employees.cnic.view', [$employee->id, 'back']) }}" target="_blank" class="badge bg-soft-primary text-primary px-3 py-2 rounded-lg" style="background: #e0e7ff; border: 1px solid #c7d2fe; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-id-card"></i> CNIC Back
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Col 2: Tenancy & Deployment -->
            <div class="col-lg-5">
                <div class="glass-card">
                    <div class="card-label-top">
                        <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                        Deployment Insights
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="mini-stat">
                                <span class="mini-stat-lbl">Joined Date</span>
                                <span class="mini-stat-val" style="font-size: 1rem;">{{ date('M d, Y', strtotime($employee->joining_date)) }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat">
                                <span class="mini-stat-lbl">Service Tenure</span>
                                <span class="mini-stat-val" style="font-size: 0.9rem;">
                                    @php
                                        $joined = \Carbon\Carbon::parse($employee->joining_date);
                                        $diff = $joined->diff(now());
                                        $parts = [];
                                        if ($diff->y > 0) $parts[] = $diff->y . 'y';
                                        if ($diff->m > 0) $parts[] = $diff->m . 'm';
                                        if ($diff->d > 0 && $diff->y == 0) $parts[] = $diff->d . 'd';
                                        echo count($parts) > 0 ? implode(' ', $parts) : 'Joined Today';
                                    @endphp
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-group">
                                <div class="info-label">Department / Team</div>
                                <div class="info-value text-primary">{{ $employee->team->name ?? 'Global Pool' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <div class="info-label">Operational Shift</div>
                                <div class="info-value">{{ optional($employee->currentShiftAssignment)->shift->shift_name ?? 'Floating' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <div class="info-label">Employment Status</div>
                                <div class="info-value">
                                    @php
                                        $completionDate = $employee->probationEndsOn();
                                        $isProbation = !$employee->isConfirmed();
                                        $daysLeft = $completionDate ? now()->diffInDays($completionDate, false) : null;
                                    @endphp

                                    @if($employee->confirmed_at)
                                        <span class="badge bg-soft-success text-success px-3 py-2 rounded-pill" style="font-size: 0.75rem; background: #ecfdf5; border: 1px solid #a7f3d0;">
                                            <i class="fas fa-check-circle mr-1"></i> Confirmed {{ $employee->confirmed_at->format('d M Y') }}
                                        </span>
                                    @elseif($isProbation)
                                        <span class="badge bg-soft-warning text-warning px-3 py-2 rounded-pill" style="font-size: 0.75rem; background: #fffbeb; border: 1px solid #fde68a;">
                                            <i class="fas fa-hourglass-half mr-1"></i>
                                            Under Probation @if($daysLeft !== null)({{ max($daysLeft, 0) }}d left)@endif
                                        </span>
                                    @else
                                        <span class="badge px-3 py-2 rounded-pill" style="font-size: 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8;">
                                            <i class="fas fa-clipboard-check mr-1"></i> Probation ended &mdash; awaiting confirmation
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <div class="info-label">Access Level</div>
                                <div class="info-value">
                                    <span class="badge bg-soft-success text-success px-3 py-2 rounded-pill" style="font-size: 0.75rem; background: #eefdf8;">
                                        <i class="fas fa-shield-alt mr-1"></i> {{ $employee->user->roles->first()->name ?? 'Portal Access' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 3: Financials & Governance -->
            <div class="col-lg-3">
                <div class="glass-card bank-tile mb-4">
                    <div class="card-label-top">
                        <div class="icon-box" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-wallet"></i></div>
                        Disbursements
                    </div>

                    @if(!empty($employee->bank_name) || !empty($employee->account_number))
                        <div class="info-group mb-3">
                            <div class="info-label">Bank Entity</div>
                            <div class="info-value" style="font-size: 0.95rem;">{{ $employee->bank_name ?: 'N/A' }}</div>
                        </div>
                        <div class="info-group mb-3">
                            <div class="info-label">Account Identification</div>
                            <div class="info-value" style="font-size: 0.95rem; letter-spacing: 1px;">{{ $employee->account_number ?: 'N/A' }}</div>
                        </div>
                        <div class="info-group mb-3">
                            <div class="info-label">IBAN / Routing</div>
                            <div class="info-value" style="font-size: 0.8rem; word-break: break-all;">{{ $employee->iban ?: 'N/A' }}</div>
                        </div>
                        <div class="info-group mb-0">
                            <div class="info-label">Branch Code</div>
                            <div class="info-value" style="font-size: 0.95rem;">{{ $employee->branch_code ?: 'N/A' }}</div>
                        </div>

                    @endif

                    @if(auth()->user()->hasRole(['admin', 'administrator']))
                        <div class="mt-4 pt-4 border-top" style="border-color: rgba(99, 102, 241, 0.1) !important;">
                            <div class="card-label-top mb-3">
                                <div class="icon-box" style="background: #fff1f2; color: #e11d48;"><i class="fas fa-hand-holding-usd"></i></div>
                                Compensation Profile
                            </div>
                            <div class="info-group mb-0">
                                <div class="info-label">Standard Base Salary</div>
                                <div class="info-value" style="font-size: 1.5rem; color: #e11d48; letter-spacing: -0.5px;">
                                    {{ number_format($employee->salary) }} <small class="text-muted" style="font-size: 0.7rem; font-weight: 600;">{{ app_settings('app_currency_symbol') ?? 'PKR' }}</small>
                                </div>
                                <div class="text-muted small mt-1" style="font-size: 0.65rem;">* Sensitive data visible to Admin only</div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="glass-card" style="background: #f8fafc; border-style: dashed;">
                    <div class="card-label-top">
                        <div class="icon-box"><i class="fas fa-gavel"></i></div>
                        Governance
                    </div>
                    <div class="info-group mb-2 d-flex justify-content-between align-items-center">
                        <div class="info-label mb-0">Late Margin</div>
                        <div class="info-value" style="font-size: 0.9rem;">{{ $employee->late_minutes_margin }}m</div>
                    </div>
                    @php
                        $paidRemaining = $employee->leaveBalances
                            ->where('leaveType.is_paid', true)
                            ->sum('remaining');
                    @endphp
                    <div class="info-group mb-0 d-flex justify-content-between align-items-center">
                        <div class="info-label mb-0">Paid Leave Left</div>
                        <div class="info-value" style="font-size: 0.9rem;">{{ rtrim(rtrim(number_format((float) $paidRemaining, 2), '0'), '.') }}d</div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $canManageCareer = auth()->user()->can('edit-employee');
            $canSeeSalary = auth()->user()->hasRole(['admin', 'administrator']);
            $currency = app_settings('app_currency_symbol') ?? 'PKR';
            $lastIncrement = $employee->lastIncrement;
            $lastPromotion = $employee->lastPromotion;
            $confirmation = $employee->confirmationEvent;
        @endphp

        <!-- Career Milestones -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="glass-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4" style="gap: 1rem;">
                        <div class="card-label-top mb-0">
                            <div class="icon-box" style="background: #eef2ff; color: #4f46e5;"><i class="fas fa-chart-line"></i></div>
                            Career Progression
                        </div>
                        @if($canManageCareer)
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-toggle="modal" data-target="#careerEventModal">
                                <i class="fas fa-plus mr-1"></i> Add Record
                            </button>
                        @endif
                    </div>

                    <div class="row g-3">
                        <!-- Last increment -->
                        <div class="col-md-4">
                            <div class="mini-stat h-100">
                                <span class="mini-stat-lbl"><i class="fas fa-arrow-trend-up mr-1"></i> Last Increment</span>
                                @if($lastIncrement)
                                    <span class="mini-stat-val" style="font-size: 1rem;">{{ $lastIncrement->effective_date->format('d M Y') }}</span>
                                    @if($canSeeSalary)
                                        @php $rise = $lastIncrement->increase(); @endphp
                                        <div class="small text-muted mt-1">
                                            {{ number_format((float) $lastIncrement->previous_salary) }}
                                            &rarr;
                                            <strong style="color: #059669;">{{ number_format((float) $lastIncrement->new_salary) }}</strong>
                                            {{ $currency }}
                                            @if($rise['percent'] !== null)
                                                <span style="color: #059669;">(+{{ $rise['percent'] }}%)</span>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="small text-muted mt-1">{{ $lastIncrement->effective_date->diffForHumans() }}</div>
                                @else
                                    <span class="mini-stat-val text-muted" style="font-size: 0.95rem;">No increment recorded</span>
                                @endif
                            </div>
                        </div>

                        <!-- Probation / confirmation -->
                        <div class="col-md-4">
                            <div class="mini-stat h-100">
                                <span class="mini-stat-lbl"><i class="fas fa-user-check mr-1"></i> Probation / Confirmation</span>
                                @php $probationEnds = $employee->probationEndsOn(); @endphp

                                @if($employee->confirmed_at)
                                    <span class="mini-stat-val" style="font-size: 1rem; color: #059669;">Confirmed</span>
                                    <div class="small text-muted mt-1">{{ $employee->confirmed_at->format('d M Y') }}</div>
                                @elseif(!$probationEnds)
                                    <span class="mini-stat-val text-muted" style="font-size: 0.95rem;">No joining date</span>
                                @elseif($probationEnds->isFuture())
                                    <span class="mini-stat-val" style="font-size: 1rem; color: #b45309;">On Probation</span>
                                    <div class="small text-muted mt-1">
                                        {{ (int) ($employee->probation ?: 0) }} months &middot;
                                        ends {{ $probationEnds->format('d M Y') }}
                                    </div>
                                @else
                                    {{-- Probation ran out but nobody has confirmed them yet. --}}
                                    <span class="mini-stat-val" style="font-size: 1rem; color: #1d4ed8;">Awaiting Confirmation</span>
                                    <div class="small text-muted mt-1">
                                        Probation ended {{ $probationEnds->format('d M Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Last promotion -->
                        <div class="col-md-4">
                            <div class="mini-stat h-100">
                                <span class="mini-stat-lbl"><i class="fas fa-award mr-1"></i> Last Promotion</span>
                                @if($lastPromotion)
                                    <span class="mini-stat-val" style="font-size: 1rem;">{{ $lastPromotion->effective_date->format('d M Y') }}</span>
                                    <div class="small text-muted mt-1">
                                        {{ $lastPromotion->previous_position ?: '—' }}
                                        &rarr;
                                        <strong>{{ $lastPromotion->new_position }}</strong>
                                    </div>
                                    <div class="small text-muted mt-1">{{ $lastPromotion->effective_date->diffForHumans() }}</div>
                                @else
                                    <span class="mini-stat-val text-muted" style="font-size: 0.95rem;">No promotion recorded</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($employee->careerEvents->count() > 0)
                        <div class="table-responsive mt-4">
                            <table class="table table-borderless align-middle mb-0">
                                <thead class="text-muted small text-uppercase fw-bold" style="border-bottom: 2px solid #f1f5f9;">
                                    <tr>
                                        <th class="ps-0 py-3">Effective</th>
                                        <th class="py-3">Record</th>
                                        <th class="py-3">Change</th>
                                        <th class="py-3">Notes</th>
                                        <th class="py-3">Recorded By</th>
                                        @if($canManageCareer)<th class="py-3 pe-0 text-right">&nbsp;</th>@endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employee->careerEvents as $event)
                                        <tr style="border-bottom: 1px solid #f8fafc;">
                                            <td class="ps-0 py-3 fw-bold">{{ $event->effective_date->format('d M, Y') }}</td>
                                            <td class="py-3">
                                                <span class="badge rounded-pill px-3 py-2" style="font-size: 0.7rem; background: {{ match($event->type) {
                                                    'increment' => '#ecfdf5',
                                                    'promotion' => '#eef2ff',
                                                    default => '#f0f9ff',
                                                } }}; color: {{ match($event->type) {
                                                    'increment' => '#059669',
                                                    'promotion' => '#4f46e5',
                                                    default => '#0284c7',
                                                } }};">
                                                    {{ $event->label() }}
                                                </span>
                                            </td>
                                            <td class="py-3 small">
                                                @if($event->new_position)
                                                    {{ $event->previous_position ?: '—' }} &rarr; <strong>{{ $event->new_position }}</strong><br>
                                                @endif
                                                @if($event->new_salary !== null)
                                                    @if($canSeeSalary)
                                                        {{ number_format((float) $event->previous_salary) }} &rarr;
                                                        <strong>{{ number_format((float) $event->new_salary) }}</strong> {{ $currency }}
                                                    @else
                                                        <span class="text-muted">Salary change (admin only)</span>
                                                    @endif
                                                @endif
                                                @if($event->type === 'confirmation')
                                                    <span class="text-muted">Confirmed off probation</span>
                                                @endif
                                            </td>
                                            <td class="py-3 small text-muted">{{ $event->notes ?: '—' }}</td>
                                            <td class="py-3 small">{{ $event->recordedBy->name ?? 'System' }}</td>
                                            @if($canManageCareer)
                                                <td class="py-3 pe-0 text-right">
                                                    <form method="POST"
                                                        action="{{ route('admin.employees.career_events.destroy', [$employee->id, $event->id]) }}"
                                                        onsubmit="return confirm('Remove this record and restore the previous values?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remove">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($employee->exitRecords->count() > 0)
        <!-- Historical Records -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="glass-card">
                    <div class="card-label-top">
                        <div class="icon-box"><i class="fas fa-history"></i></div>
                        Historical Employment Terms
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="text-muted small text-uppercase fw-bold" style="border-bottom: 2px solid #f1f5f9;">
                                <tr>
                                    <th class="ps-0 py-3">Exit Date</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Served Notice</th>
                                    <th class="py-3">Reason / Context</th>
                                    <th class="py-3 pe-0">Processed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employee->exitRecords as $record)
                                <tr style="border-bottom: 1px solid #f8fafc;">
                                    <td class="ps-0 py-3 fw-bold">{{ date('d M, Y', strtotime($record->exit_date)) }}</td>
                                    <td class="py-3">
                                        <span class="badge rounded-pill px-3 py-2 {{ match($record->exit_type) {
                                            'terminated' => 'bg-soft-dark text-dark',
                                            'suspended' => 'bg-soft-warning text-warning',
                                            default => 'bg-soft-danger text-danger',
                                        } }}" style="font-size: 0.7rem; background: {{ match($record->exit_type) {
                                            'terminated' => '#f1f5f9',
                                            'suspended' => '#fffbeb',
                                            default => '#fff1f2',
                                        } }};">
                                            {{ ucfirst($record->exit_type) }}
                                        </span>
                                        @if($record->exit_type === 'suspended' && $record->suspended_start_date && $record->suspended_end_date)
                                            <div class="small text-muted mt-1">{{ date('d M, Y', strtotime($record->suspended_start_date)) }} — {{ date('d M, Y', strtotime($record->suspended_end_date)) }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @if($record->served_notice)
                                            <span class="text-success fw-bold small"><i class="fas fa-check-circle mr-1"></i> Yes</span>
                                        @else
                                            <span class="text-muted small">No Notice</span>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <div class="p-3 rounded-3" style="background: #f8fafc; border-left: 3px solid #cbd5e1; max-width: 400px; white-space: normal;">
                                            <div class="info-label mb-1" style="font-size: 0.6rem;">Exit Rationale</div>
                                            <p class="mb-0 text-dark small font-italic" style="line-height: 1.4;">
                                                "{{ $record->exit_reason ?: 'No specific reason documented.' }}"
                                            </p>
                                        </div>
                                    </td>
                                    <td class="py-3 pe-0">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.6rem;">
                                                <i class="fas fa-user-shield"></i>
                                            </div>
                                            <span class="small fw-semibold">{{ $record->processedBy->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@if($canManageCareer)
<div class="modal fade" id="careerEventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="{{ route('admin.employees.career_events.store', $employee->id) }}" class="modal-content"
            style="border-radius: 20px; overflow: hidden;">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Career Record</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label>Record Type</label>
                    <select name="type" id="career-type" class="form-control" required>
                        <option value="increment">Salary Increment</option>
                        <option value="promotion">Promotion</option>
                        <option value="confirmation" @if($employee->confirmed_at) disabled @endif>
                            Confirmation off Probation
                            @if($employee->confirmed_at) (already confirmed) @endif
                        </option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Effective Date</label>
                    <input type="date" name="effective_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>

                <div class="form-group mb-3" data-career-field="promotion" style="display: none;">
                    <label>New Designation</label>
                    <input type="text" name="new_position" class="form-control"
                        placeholder="Current: {{ $employee->position ?: 'not set' }}">
                </div>

                <div class="form-group mb-3" data-career-field="salary">
                    <label>
                        New Salary
                        <span class="text-muted" data-career-optional style="display: none;">(optional)</span>
                    </label>
                    <input type="number" name="new_salary" step="0.01" min="0" class="form-control"
                        placeholder="Current: {{ number_format((float) $employee->salary, 2) }}">
                    <small class="text-muted">This becomes the employee's salary from the effective date.</small>
                </div>

                <div class="form-group mb-0">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Reason or approval reference"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        // Each record type needs a different set of fields.
        var type = document.getElementById('career-type');
        if (!type) return;

        var promotionField = document.querySelector('[data-career-field="promotion"]');
        var salaryField = document.querySelector('[data-career-field="salary"]');
        var optionalHint = document.querySelector('[data-career-optional]');
        var positionInput = promotionField.querySelector('input');
        var salaryInput = salaryField.querySelector('input');

        function sync() {
            var value = type.value;

            promotionField.style.display = value === 'promotion' ? '' : 'none';
            positionInput.required = value === 'promotion';
            if (value !== 'promotion') positionInput.value = '';

            salaryField.style.display = value === 'confirmation' ? 'none' : '';
            salaryInput.required = value === 'increment';
            if (value === 'confirmation') salaryInput.value = '';

            // A promotion may or may not carry a raise.
            optionalHint.style.display = value === 'promotion' ? '' : 'none';
        }

        type.addEventListener('change', sync);
        sync();
    })();
</script>
@endif
@endsection
