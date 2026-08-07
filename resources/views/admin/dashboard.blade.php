@extends('admin.layouts.app') {{-- Or layouts.employee if you have one --}}

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
    <div class="col-12">
        <div class="dashboard-page-header">
            <div class="dashboard-page-header__title">
                <h3 class="page-title mb-0">Today's Overview</h3>
                <nav aria-label="breadcrumb">
                    <ol class="dashboard-breadcrumb">
                        <li><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="active" aria-current="page">Attendance</li>
                    </ol>
                </nav>
            </div>

            <div class="dashboard-page-header__actions">
                <form action="{{ route('admin.dashboard') }}" method="GET" class="dashboard-date-form">
                    <label class="dashboard-date-picker" title="Filter by date">
                        <span class="dashboard-date-picker__icon" aria-hidden="true">
                            <i class="bi bi-calendar3"></i>
                        </span>
                        <input
                            type="date"
                            name="date"
                            class="dashboard-date-picker__input"
                            value="{{ $data['filter_date'] }}"
                            onchange="this.form.submit()"
                        >
                    </label>
                    @if(!$data['is_live_mode'])
                        <a href="{{ route('admin.dashboard') }}" class="dashboard-date-reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Live</span>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="admin-dashboard-page">
    <div class="container-fluid">
        @if($celebrations->count() > 0)
        <!-- Celebration Banner Section (Birthdays & Anniversaries) -->
        <div class="row birthday-banner-container" id="birthday-section">
            <div class="col-12">
                <div id="birthdayCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
                    <div class="carousel-inner">
                        @foreach($celebrations as $index => $event)
                            @php
                                $emp = $event['employee'];
                                $isToday = $event['is_today'];
                                $type = $event['type'];
                                $diff = $event['days_until'];
                                $years = $event['years'] ?? 0;
                                
                                $cleanName = trim(str_contains($emp->name, '_') ? substr($emp->name, strpos($emp->name, '_') + 1) : $emp->name);

                                // Ordinal Suffix helper
                                $suffix = 'th';
                                if (!in_array(($years % 100), [11, 12, 13])) {
                                    switch ($years % 10) {
                                        case 1: $suffix = 'st'; break;
                                        case 2: $suffix = 'nd'; break;
                                        case 3: $suffix = 'rd'; break;
                                    }
                                }
                            @endphp
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                @if($type === 'birthday')
                                    <div class="birthday-banner birthday-theme {{ $isToday ? 'celebration-today' : '' }}">
                                        <!-- Swinging Garland -->
                                        <div class="bunting-garland">
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                            <div class="bunting-flag"></div>
                                        </div>

                                        <!-- Fireworks Laser / Burst Layers -->
                                        <div class="firework firework-1"></div>
                                        <div class="firework firework-2"></div>
                                        <div class="firework firework-3"></div>
                                        <div class="firework firework-4"></div>

                                        <!-- Shockwaves -->
                                        <div class="shockwave shockwave-1"></div>
                                        <div class="shockwave shockwave-2"></div>

                                        <!-- Floating Birthday Modern Elements -->
                                        <span class="birthday-prop prop-balloon-1">🎈</span>
                                        <span class="birthday-prop prop-balloon-2">🎈</span>
                                        <span class="birthday-prop prop-cake">🍰</span>
                                        <span class="birthday-prop prop-hat">🥳</span>
                                        <span class="birthday-prop prop-sparkles-1">✨</span>
                                        <span class="birthday-prop prop-sparkles-2">✨</span>
                                        <span class="birthday-prop prop-popper">🎉</span>
                                        
                                        <div class="birthday-glass-card">
                                            <div class="birthday-avatar-glow float-animation">
                                                <img src="{{ get_profile_picture_url($emp->profile_pic, $emp->name) }}">
                                                <span class="birthday-badge-icon">🎂</span>
                                            </div>
                                            
                                            <div class="birthday-details">
                                                <div class="birthday-subtitle-text">Happy Celebration</div>
                                                <div class="birthday-title-text">{{ $cleanName }}</div>
                                                
                                                <div class="birthday-message-text">
                                                    @if($isToday)
                                                        🎉 Wishing you a wonderful Happy Birthday! May your day be filled with celebration, joy, and grand success! 🥂
                                                    @else
                                                        Upcoming celebration on <b>{{ \Carbon\Carbon::parse($event['date'])->format('d M') }}</b> ({{ $diff == 1 ? 'Tomorrow' : 'in ' . $diff . ' days' }})
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Controls -->
                                        @if($celebrations->count() > 1)
                                            <div class="birthday-slider-controls">
                                                @foreach($celebrations as $idx => $e)
                                                    <div class="slider-dot {{ $idx === $index ? 'active' : '' }}" onclick="$('#birthdayCarousel').carousel({{ $idx }})"></div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <button class="close-birthday" onclick="toggleBirthdaySection(false)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @elseif($type === 'anniversary')
                                    <div class="birthday-banner anniversary-theme {{ $isToday ? 'celebration-today' : '' }}">
                                        <!-- Decorative Elements -->
                                        @for($i=0; $i<12; $i++)
                                            <i class="bi bi-star-fill gold-sparkle" style="top: {{ rand(5, 90) }}%; left: {{ rand(5, 95) }}%; animation: saas-pulse {{ rand(2, 4) }}s infinite {{ rand(0, 2) }}s;"></i>
                                        @endfor
                                        
                                        <div class="anniversary-left-pane">
                                            <div class="anniversary-photo-frame float-animation">
                                                <img src="{{ get_profile_picture_url($emp->profile_pic, $emp->name) }}" class="anniversary-frame-avatar">
                                            </div>
                                            <div class="anniversary-emp-name">{{ $cleanName }}</div>
                                        </div>

                                        <div class="anniversary-right-pane">
                                            <div class="anniversary-milestone-box">
                                                <div class="anniversary-year-display">
                                                    {{ $years }}<span class="suffix text-uppercase">{{ $suffix }}</span>
                                                </div>
                                                <div class="anniversary-text-block">
                                                    <div class="anniversary-label-top">Celebrating</div>
                                                    <div class="anniversary-label-main">Year Work<br>Anniversary</div>
                                                </div>
                                            </div>
                                            
                                            <div class="anniversary-congrats">
                                                @if($isToday)
                                                    🎉 Congratulations! Thank you for your exceptional dedication and contribution over the last {{ $years }} years! 🚀
                                                @else
                                                    Upcoming milestone on <b>{{ \Carbon\Carbon::parse($event['date'])->format('d M') }}</b> ({{ $diff == 1 ? 'Tomorrow' : 'in ' . $diff . ' days' }})
                                                @endif
                                            </div>

                                            <!-- Controls -->
                                            @if($celebrations->count() > 1)
                                                <div class="birthday-slider-controls">
                                                    @foreach($celebrations as $idx => $e)
                                                        <div class="slider-dot {{ $idx === $index ? 'active' : '' }}" onclick="$('#birthdayCarousel').carousel({{ $idx }})"></div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <button class="close-birthday" onclick="toggleBirthdaySection(false)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                @elseif($type === 'eotm')
                                    <div class="eotm-banner">
                                        <i class="bi bi-quote eotm-quote-icon"></i>
                                        <div class="eotm-left-pane">
                                            <div class="eotm-avatar-wrapper float-animation">
                                                <img src="{{ get_profile_picture_url($emp->profile_pic, $emp->name) }}" alt="{{ $emp->name }}">
                                                <div class="eotm-badge"><i class="bi bi-star-fill"></i></div>
                                            </div>
                                            <div class="eotm-name">{{ $cleanName }}</div>
                                            <div class="eotm-designation">{{ $emp->position ?? 'Team Member' }}</div>
                                            @if(auth()->user()->hasRole('admin') && $emp->team)
                                                <div class="eotm-designation" style="color: #ffd700; margin-top: 2px;">{{ $emp->team->name }}</div>
                                            @endif
                                        </div>
                                        <div class="eotm-right-pane">
                                            <div class="eotm-title">
                                                Employee of the Month
                                                <span class="eotm-month">{{ date('F Y', mktime(0, 0, 0, $event['eotm_month'], 10, $event['eotm_year'])) }}</span>
                                            </div>
                                            <div class="eotm-message">
                                                @if($event['bio_comments'])
                                                    "{!! nl2br(e($event['bio_comments'])) !!}"
                                                @else
                                                    "Outstanding performance, exceptional dedication, and continuous commitment to excellence. We are proud to recognize their hard work this month!"
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Controls -->
                                        @if($celebrations->count() > 1)
                                            <div class="birthday-slider-controls">
                                                @foreach($celebrations as $idx => $e)
                                                    <div class="slider-dot {{ $idx === $index ? 'active' : '' }}" onclick="$('#birthdayCarousel').carousel({{ $idx }})"></div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <button class="close-birthday" onclick="toggleBirthdaySection(false)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Birthday Toggle Trigger -->
        <div class="birthday-toggle-btn pulse-attract" id="birthday-toggle" onclick="toggleBirthdaySection(true)">
            <i class="bi bi-gift-fill"></i>
        </div>

        <!-- Flicker Prevention Script -->
        <script>
            (function() {
                const isDismissed = localStorage.getItem('birthday_banner_dismissed') === 'true';
                document.getElementById('birthday-section').style.display = isDismissed ? 'none' : 'block';
                document.getElementById('birthday-toggle').style.display = isDismissed ? 'flex' : 'none';
            })();
        </script>
        @endif

        <!-- SaaS-Ultra Stat Grid -->
        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card-ultra stat-primary filter-card active-filter" data-filter="all" style="cursor: pointer;">
                    <div class="stat-icon-wrapper">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Staff</div>
                        <div class="stat-value">{{ $data['total_employees'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card-ultra stat-info">
                    <div class="stat-icon-wrapper">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">On Time Rate</div>
                        <div class="stat-value">{{ $data['on_time_percentage'] }}%</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card-ultra stat-success filter-card" data-filter="punctual" style="cursor: pointer;">
                    <div class="stat-icon-wrapper">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">On Time Today</div>
                        <div class="stat-value">{{ $data['on_time_today'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card-ultra stat-danger filter-card" data-filter="late" style="cursor: pointer;">
                    <div class="stat-icon-wrapper">
                        <i class="bi bi-alarm-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Late Today</div>
                        <div class="stat-value">{{ $data['late_today'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card-ultra stat-warning filter-card" data-filter="half_day" style="cursor: pointer;">
                    <div class="stat-icon-wrapper">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Half Day Today</div>
                        <div class="stat-value">{{ $data['half_day_today'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stat-card-ultra stat-absent filter-card" data-filter="absent" style="cursor: pointer;">
                    <div class="stat-icon-wrapper">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Absent Today</div>
                        <div class="stat-value">{{ $data['absent_today'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Intelligence Matrix Board -->
        <div class="matrix-card">
            <div class="matrix-header">
                <div>
                    <h5 class="matrix-title">Who's In Today</h5>
                    <p class="text-muted small mb-0 fw-600">See who checked in, who's on break, and who's absent</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <span class="tw-badge-success rounded-pill px-3 py-1 d-flex align-items-center gap-2" style="font-size: 0.65rem; background: rgba(16, 185, 129, 0.1);">
                        <span class="pulse-dot dot-active" style="width: 6px; height: 6px;"></span> Live Updates
                    </span>
                </div>
            </div>
            
            <div class="p-0">
                <div class="table-responsive">
                    <table id="dashboard-datatable" class="table table-hover w-100 mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Job Status</th>
                                <th>Leave Balance</th>
                                <th>Last Month</th>
                                <th>This Month</th>
                                <th>This Week</th>
                                <th>Hours Today</th>
                                <th>Break Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                @php
                                    $presence = $employee->presence_status ?? [];
                                    $presenceType = $employee->presence_type_calc ?? 'workforce';
                                    $attendance = $employee->attendances->first();
                                    $stats = $employee->break_stats ?? ['spent' => 0, 'allowed' => 45, 'remaining' => 45, 'exceeded' => 0, 'isOnBreak' => false, 'percent' => 0];
                                    $summary = $employee->working_summary ?? ['last_month' => '0 min', 'this_month' => '0 min', 'this_week' => '0 min'];
                                    $earlyLeaves = get_early_leaves_summary($employee->id);
                                    $leave = $employee->leave_balance ?? ['remaining' => 0];
                                    $isCheckedOut = ($presence['code'] ?? '') === 'CHECKED_OUT';
                                    $isAbsent = ($presence['code'] ?? '') === 'ABSENT';
                                    $barColor = $stats['spent'] > $stats['allowed'] ? 'var(--danger-rose)' : ($stats['percent'] > 80 ? 'var(--warning-amber)' : 'var(--success-emerald)');

                                    $rowClass = match (true) {
                                        $isAbsent => 'bg-soft-danger',
                                        $isCheckedOut => 'row-checked-out',
                                        ($presence['code'] ?? '') === 'BREAK_EXCEEDED' => 'row-break-exceeded',
                                        default => '',
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}" data-presence-type="{{ $presenceType }}">
                                    <td class="text-muted small fw-bold dt-row-index"></td>
                                    <td>
                                        <div class="identity-block">
                                            <a href="{{ route('admin.employees.show', $employee->id) }}" target="_blank" title="View Profile">
                                                <img src="{{ get_profile_picture_url($employee->profile_pic, $employee->name) }}" class="identity-avatar">
                                            </a>
                                            <div class="identity-info">
                                                <a href="{{ route('admin.employees.show', $employee->id) }}" target="_blank" class="name text-dark fw-bold" style="text-decoration: none;">{{ $employee->name }}</a>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="sub text-uppercase">{{ $employee->position ?? 'Operations' }}</span>
                                                    @if($isCheckedOut)
                                                        <span class="badge-soft-secondary border-0 px-1 py-0 shadow-none" style="font-size: 0.55rem;">Checked Out</span>
                                                    @elseif($attendance && $attendance->check_in)
                                                        <span class="badge-soft-success border-0 px-1 py-0 shadow-none" style="font-size: 0.55rem;">Checked In</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php $empStatus = $employee->employment_status ?? 'Confirmed'; @endphp
                                        <span class="{{ $empStatus === 'Probation' ? 'badge-soft-warning' : 'badge-soft-secondary' }}">{{ $empStatus === 'Probation' ? 'On Probation' : 'Confirmed' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge-soft-success">{{ $leave['remaining'] ?? 0 }} days left</span>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-slate-800">{{ $summary['last_month'] }}</div>
                                        <div class="small text-muted" style="font-size: 0.65rem;">Left early: {{ $earlyLeaves['last_month'] }}</div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-indigo-600">{{ $summary['this_month'] }}</div>
                                        <div class="small text-muted" style="font-size: 0.65rem;">Left early: {{ $earlyLeaves['this_month'] }}</div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-slate-800">{{ $summary['this_week'] }}</div>
                                        <div class="small text-muted" style="font-size: 0.65rem;">Left early: {{ $earlyLeaves['this_week'] }}</div>
                                    </td>
                                    <td class="bg-soft-indigo border-indigo-opacity-10">
                                        <div class="small fw-bold text-primary">{{ $employee->daily_hours_today ?? '0 min' }}</div>
                                        <div class="small text-muted" style="font-size: 0.65rem;">Today</div>
                                    </td>
                                    <td>
                                        @php
                                            $barColor = $stats['spent'] > $stats['allowed'] ? 'var(--danger-rose)' : ($stats['percent'] > 80 ? 'var(--warning-amber)' : 'var(--success-emerald)');
                                        @endphp
                                        
                                        <div class="intelligence-break-card {{ $stats['isOnBreak'] ? 'on-break-active' : '' }} {{ $isCheckedOut ? 'break-card-checked-out' : '' }}">
                                            @if($isCheckedOut)
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="label text-muted">Used: <b class="text-slate-600">{{ $stats['spent'] }}m</b></span>
                                                    <span class="label text-muted" style="font-size: 0.55rem;">Day finished</span>
                                                </div>
                                                <div class="break-progress-bg" style="height: 4px; margin: 4px 0;">
                                                    <div class="break-progress-fill" style="width: {{ $stats['percent'] }}%; background: #94a3b8; height: 100%; border-radius: 10px;"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-1">
                                                    <span class="label text-muted" style="font-size: 0.6rem;">Total break used</span>
                                                    <span class="label text-muted" style="font-size: 0.55rem; opacity: 0.6;">{{ round($stats['percent']) }}%</span>
                                                </div>
                                            @else
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="label text-muted">Used: <b class="text-slate-800">{{ $stats['spent'] }}m</b></span>
                                                @if($stats['isOnBreak'])
                                                    <span class="tw-badge-danger pulse-on-break animate-pulse">On Break</span>
                                                @else
                                                    <span class="label text-muted">Allowed: <b>{{ $stats['allowed'] }}m</b></span>
                                                @endif
                                            </div>
                                            
                                            <div class="break-progress-bg" style="height: 4px; margin: 4px 0;">
                                                <div class="break-progress-fill" style="width: {{ $stats['percent'] }}%; background: {{ $barColor }}; height: 100%; border-radius: 10px;"></div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                 @if($stats['exceeded'] > 0)
                                                    <span class="label text-danger" style="font-size: 0.6rem;">Over: <b>+{{ $stats['exceeded'] }}m</b></span>
                                                 @else
                                                    <span class="label text-success" style="font-size: 0.6rem;">Left: <b>{{ $stats['remaining'] }}m</b></span>
                                                 @endif
                                                 @if(!$stats['isOnBreak'])
                                                    <span class="label text-muted" style="font-size: 0.55rem; opacity: 0.6;">{{ round($stats['percent']) }}%</span>
                                                 @endif
                                            </div>
                                            @endif
                                        </div>
                                        
                                        {{-- Mark Break — only while actively checked in (not checked out) --}}
                                        @if($attendance && $attendance->check_in && !$attendance->check_out)
                                            @can('view-attendance')
                                                @php $isOnBreakNow = $stats['isOnBreak'] ?? false; @endphp
                                                @if($isOnBreakNow)
                                                    {{-- On Break: disabled, admin cannot end it --}}
                                                    <button type="button"
                                                        class="btn-instant-break mt-2 w-100 break-active"
                                                        disabled
                                                        style="opacity:0.85; cursor:not-allowed;"
                                                        title="Employee is on break. They must end it themselves.">
                                                        <i class="bi bi-cup-hot-fill me-1"></i> On Break
                                                    </button>
                                                @else
                                                    {{-- Clickable purple button --}}
                                                    <button type="button"
                                                        class="btn-instant-break mt-2 w-100 break-idle"
                                                        data-emp-id="{{ $employee->id }}">
                                                        <i class="bi bi-cup-hot-fill me-1"></i> Mark Break
                                                    </button>
                                                @endif
                                            @endcan
                                        @endif
                                    </td>
                                    <td>
                                        <div class="pulse-cell">
                                            <div class="d-flex flex-column gap-1 mt-1">
                                                <span class="saas-badge-soft {{ $presence['badge_class'] ?? 'badge-soft-secondary' }}" style="font-size: 10px;">{{ $presence['label'] ?? '—' }}</span>
                                                @if(!empty($presence['late_minutes']))
                                                    <span class="tw-badge-danger" style="font-size: 0.65rem; font-weight: 800; border-radius: 4px; padding: 2px 6px; letter-spacing: 0.05em; width: fit-content;">
                                                        {{ formatMinutesToHours($presence['late_minutes']) }}
                                                    </span>
                                                @endif
                                                @if(($presence['alert'] ?? null) === 'break_exceeded')
                                                    <span class="tw-badge-danger" style="font-size: 0.6rem;">Over break limit (+{{ $presence['break_exceeded_minutes'] ?? 0 }}m)</span>
                                                @endif
                                                @if(!empty($presence['show_checkout_time']) && !empty($presence['checkout_time']))
                                                    <span class="checkout-time-label">at {{ $presence['checkout_time'] }}</span>
                                                @endif
                                            </div>
                                            @if(($data['is_live_mode'] ?? false) && ($employee->is_checked_in_active ?? false) && ($data['can_checkout_employee'] ?? false))
                                                <button type="button"
                                                    class="btn-admin-checkout mt-2 w-100"
                                                    data-emp-id="{{ $employee->id }}"
                                                    data-emp-name="{{ $employee->name }}">
                                                    <i class="bi bi-box-arrow-right me-1"></i> Checkout
                                                </button>
                                            @endif
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

    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/pages/dashboard.js') }}"></script>

    <script>
        // Active filter tracking variable (default is 'all')
        window.currentPresenceFilter = 'all';

        // Custom search filter for Presence Type
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (!window.currentPresenceFilter || window.currentPresenceFilter === 'all') {
                    return true;
                }
                const row = settings.aoData[dataIndex].nTr;
                const type = $(row).data('presence-type');
                
                return type === window.currentPresenceFilter;
            }
        );

        $(document).ready(function () {
            // DataTables SaaS Premium Init
            if ($.fn.DataTable.isDataTable('#dashboard-datatable')) {
                $('#dashboard-datatable').DataTable().destroy();
            }
            
            const table = $('#dashboard-datatable').DataTable({
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "order": [[1, "asc"]],
                "responsive": true,
                "columnDefs": [
                    { targets: 0, orderable: false, searchable: false }
                ],
                "drawCallback": function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = '#' + (api.page.info().start + i + 1);
                    });
                },
                "dom": '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search by name...",
                    "lengthMenu": "_MENU_ per view"
                }
            });

            $('.filter-card').on('click', function() {
                const filter = $(this).data('filter');
                if (!filter) return;

                // Toggle active styling
                $('.filter-card').removeClass('active-filter');
                $(this).addClass('active-filter');

                // Set the filter variable
                window.currentPresenceFilter = filter;

                // Redraw table
                table.draw();
                
                // Scroll down smoothly to the table
                $('html, body').animate({
                    scrollTop: $(".matrix-card").offset().top - 20
                }, 500);
            });

            // Style Search & Length controls
            $('.dataTables_filter input').addClass('tw-form-input bg-slate-50 border-0 small').css('width', '280px');
            $('.dataTables_length select').addClass('tw-form-input bg-slate-50 border-0 small');

            $(document).on('click', '.trigger_ajax_modal', function () {
                const id = $(this).data('id');
                const action = $(this).data('action');
                let route = '';

                if(action === 'view') {
                    route = "{{ route('ajax_modal_contents', 'view_employee') }}" + "?id=" + id;
                }

                $.ajax({
                    url: '/admin/employee_data/' + id,
                    type: "GET",
                    dataType: 'json',
                    beforeSend: function() {
                        $('#shift_update_ajax_modal .modal-dialog').html('<div class="modal-content border-0 shadow-lg" style="border-radius: 20px; min-height: 400px; display: flex; align-items: center; justify-content: center; background: #fff;"><div class="text-center"><div class="spinner-border text-primary shadow-sm" style="width: 3rem; height: 3rem; border-width: 0.25em;" role="status"></div><h6 class="mt-3 text-muted fw-bold">Fetching Profile...</h6></div></div>');
                        $('#shift_update_ajax_modal').modal('show');
                    },
                    success: function (data) {
                        ajax_modal(data, route);
                    },
                    error: function () {
                        toastr.error('Error fetching employee data.');
                    }
                });
            });
        });

        // ============================================================
        // ✅ Admin Mark Break (One-Click Start Only)
        // ============================================================
        $(document).on('click', '.btn-instant-break', function () {
            const $btn  = $(this);
            const empId = $btn.data('emp-id');

            // Guard: only idle buttons are clickable (on-break buttons are disabled via HTML)
            if ($btn.prop('disabled')) return;

            // Disable to prevent double-click
            $btn.prop('disabled', true).css('opacity', '0.6').html('<i class="bi bi-hourglass-split me-1"></i> Starting...');

            $.ajax({
                url:  '{{ route("admin.breaks.instantStart") }}',
                type: 'POST',
                data: { employee_id: empId, _token: '{{ csrf_token() }}' },
                success: function (res) {
                    if (res.success) {
                        toastr.success('Break started at ' + (res.start_time ?? 'now') + ' for employee.', 'Break Started');
                        setTimeout(() => location.reload(), 900);
                    } else {
                        toastr.warning(res.message || 'Could not start break.');
                        $btn.prop('disabled', false).css('opacity', '1').html('<i class="bi bi-cup-hot-fill me-1"></i> Mark Break');
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Server error. Please try again.');
                    $btn.prop('disabled', false).css('opacity', '1').html('<i class="bi bi-cup-hot-fill me-1"></i> Mark Break');
                }
            });
        });

        // ============================================================
        // Admin Checkout (checked-in employees, live mode only)
        // ============================================================
        $(document).on('click', '.btn-admin-checkout', function () {
            const $btn = $(this);
            const empId = $btn.data('emp-id');
            const empName = $btn.data('emp-name') || 'this employee';

            Swal.fire({
                title: 'Do you want to checkout?',
                text: empName + ' will be checked out from today\'s shift.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                confirmButtonText: 'Yes, Checkout',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) return;

                $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Checking out...');

                $.ajax({
                    url: '{{ route("admin.dashboard.checkout_employee") }}',
                    type: 'POST',
                    data: { employee_id: empId, _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        if (res.success) {
                            toastr.success(res.message || 'Employee checked out successfully.');
                            setTimeout(() => location.reload(), 900);
                        } else {
                            toastr.warning(res.message || 'Could not checkout employee.');
                            $btn.prop('disabled', false).html('<i class="bi bi-box-arrow-right me-1"></i> Checkout');
                        }
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Server error. Please try again.');
                        $btn.prop('disabled', false).html('<i class="bi bi-box-arrow-right me-1"></i> Checkout');
                    }
                });
            });
        });

        function toggleBirthdaySection(show) {
            if (show) {
                $('#birthday-toggle').fadeOut(300, function() {
                    $('#birthday-section').slideDown(600);
                });
                localStorage.setItem('birthday_banner_dismissed', 'false');
            } else {
                $('#birthday-section').slideUp(600, function() {
                    $('#birthday-toggle').css('display', 'flex').hide().fadeIn(300);
                });
                localStorage.setItem('birthday_banner_dismissed', 'true');
            }
        }
    </script>
@endsection