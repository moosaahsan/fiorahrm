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
                                        $probationMonths = (int)($employee->probation ?: 3);
                                        $completionDate = \Carbon\Carbon::parse($employee->joining_date)->addMonths($probationMonths);
                                        $isProbation = now()->lt($completionDate);
                                        $daysLeft = now()->diffInDays($completionDate, false);
                                    @endphp
                                    
                                    @if($isProbation)
                                        <span class="badge bg-soft-warning text-warning px-3 py-2 rounded-pill" style="font-size: 0.75rem; background: #fffbeb; border: 1px solid #fde68a;">
                                            <i class="fas fa-hourglass-half mr-1"></i> Under Probation ({{ $daysLeft }}d left)
                                        </span>
                                    @else
                                        <span class="badge bg-soft-success text-success px-3 py-2 rounded-pill" style="font-size: 0.75rem; background: #ecfdf5; border: 1px solid #a7f3d0;">
                                            <i class="fas fa-check-circle mr-1"></i> Permanent / Confirmed
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
                        <div class="info-label mb-0">Break Window</div>
                        <div class="info-value" style="font-size: 0.9rem;">{{ $employee->break_duration }}m</div>
                    </div>
                    <div class="info-group mb-2 d-flex justify-content-between align-items-center">
                        <div class="info-label mb-0">Late Margin</div>
                        <div class="info-value" style="font-size: 0.9rem;">{{ $employee->late_minutes_margin }}m</div>
                    </div>
                    <div class="info-group mb-0 d-flex justify-content-between align-items-center">
                        <div class="info-label mb-0">Paid Leaves</div>
                        <div class="info-value" style="font-size: 0.9rem;">{{ $employee->leaves_allowed_in_year }}d</div>
                    </div>
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
@endsection
