@extends('employee.layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('breadcrumb')
<div class="col-sm-12 mb-3">
    <div class="d-flex align-items-center justify-content-between page-hero">
        <div>
            <h3 class="page-title">My Dashboard</h3>
            <ul class="page-breadcrumb">
                <li>Employee Portal</li>
                <li class="active">Dashboard</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('employee.dashboard') }}" method="GET" class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3 text-muted"></i>
                    <input type="date" name="date" class="date-filter-input" value="{{ $data['filter_date'] }}" onchange="this.form.submit()">
                </div>
                @if(!$data['is_live_mode'])
                    <a href="{{ route('employee.dashboard') }}" class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Live</a>
                @endif
            </form>
        </div>
    </div>
</div>
</div>

@endsection

@section('content')
<div class="employee-portal-page">
<div class="container-fluid">

    {{-- ═══ CELEBRATIONS CAROUSEL ═══ --}}
    @if($celebrations->count() > 0)
    <div class="row celebration-container" id="celebration-section">
        <div class="col-12">
            <div id="celebCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
                <div class="carousel-inner">
                    @foreach($celebrations as $index => $event)
                        @php
                            $emp = $event['employee'];
                            $isToday = $event['is_today'];
                            $type = $event['type'];
                            $diff = $event['days_until'];
                            $years = $event['years'] ?? 0;
                            $cleanName = trim(str_contains($emp->name, '_') ? substr($emp->name, strpos($emp->name, '_') + 1) : $emp->name);
                            $suffix = 'th';
                            if (!in_array(($years % 100), [11,12,13])) {
                                switch ($years % 10) { case 1: $suffix='st'; break; case 2: $suffix='nd'; break; case 3: $suffix='rd'; break; }
                            }
                        @endphp
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            @if($type === 'birthday')
                            <div class="celebration-card birthday-card {{ $isToday ? 'is-today' : '' }}">
                                <div class="firework firework-1"></div><div class="firework firework-2"></div>
                                <div class="celeb-glass">
                                    <div class="celeb-avatar float-anim"><img src="{{ get_profile_picture_url($emp->profile_pic, $emp->name) }}"></div>
                                    <div>
                                        <div class="celeb-subtitle">🎂 Happy Birthday</div>
                                        <div class="celeb-name">{{ $cleanName }}</div>
                                        <div class="celeb-message">@if($isToday)🎉 Wishing you a wonderful birthday! May your day be filled with joy!@else Upcoming on <b>{{ \Carbon\Carbon::parse($event['date'])->format('d M') }}</b> ({{ $diff == 1 ? 'Tomorrow' : 'in '.$diff.' days' }})@endif</div>
                                    </div>
                                </div>
                                <button class="celeb-close" onclick="toggleCelebrations(false)"><i class="bi bi-x-lg"></i></button>
                                @if($celebrations->count() > 1)<div class="celeb-dots">@foreach($celebrations as $idx => $e)<div class="celeb-dot {{ $idx === $index ? 'active' : '' }}" onclick="$('#celebCarousel').carousel({{ $idx }})"></div>@endforeach</div>@endif
                            </div>
                            @elseif($type === 'anniversary')
                            <div class="celebration-card anniversary-card {{ $isToday ? 'is-today' : '' }}">
                                @for($i=0;$i<8;$i++)<i class="bi bi-star-fill gold-sparkle" style="top:{{rand(5,90)}}%;left:{{rand(5,95)}}%;animation:floatY {{rand(2,4)}}s infinite {{rand(0,2)}}s;"></i>@endfor
                                <div class="celeb-glass">
                                    <div class="celeb-avatar gold float-anim"><img src="{{ get_profile_picture_url($emp->profile_pic, $emp->name) }}"></div>
                                    <div class="d-flex align-items-center gap-4 flex-wrap">
                                        <div class="anniv-year">{{ $years }}<span class="anniv-suffix">{{ $suffix }}</span></div>
                                        <div>
                                            <div class="celeb-subtitle gold-text">🏆 Work Anniversary</div>
                                            <div class="celeb-name">{{ $cleanName }}</div>
                                            <div class="celeb-message">@if($isToday)🎉 Congratulations on {{ $years }} years of dedication!@else Upcoming on <b>{{ \Carbon\Carbon::parse($event['date'])->format('d M') }}</b> ({{ $diff == 1 ? 'Tomorrow' : 'in '.$diff.' days' }})@endif</div>
                                        </div>
                                    </div>
                                </div>
                                <button class="celeb-close" onclick="toggleCelebrations(false)"><i class="bi bi-x-lg"></i></button>
                                @if($celebrations->count() > 1)<div class="celeb-dots">@foreach($celebrations as $idx => $e)<div class="celeb-dot {{ $idx === $index ? 'active' : '' }}" onclick="$('#celebCarousel').carousel({{ $idx }})"></div>@endforeach</div>@endif
                            </div>
                            @elseif($type === 'eotm')
                            <div class="celebration-card eotm-card">
                                <div class="celeb-glass">
                                    <div class="celeb-avatar gold float-anim"><img src="{{ get_profile_picture_url($emp->profile_pic, $emp->name) }}"></div>
                                    <div>
                                        <div class="celeb-subtitle gold-text">⭐ Employee of the Month</div>
                                        <div class="celeb-name">{{ $cleanName }}</div>
                                        <span class="status-badge badge-warning" style="margin-top:4px">{{ date('F Y', mktime(0,0,0,$event['eotm_month'],10,$event['eotm_year'])) }}</span>
                                        <div class="celeb-message mt-1">@if($event['bio_comments']){{ Str::limit($event['bio_comments'], 120) }}@else Outstanding performance and dedication this month!@endif</div>
                                    </div>
                                </div>
                                <button class="celeb-close" onclick="toggleCelebrations(false)"><i class="bi bi-x-lg"></i></button>
                                @if($celebrations->count() > 1)<div class="celeb-dots">@foreach($celebrations as $idx => $e)<div class="celeb-dot {{ $idx === $index ? 'active' : '' }}" onclick="$('#celebCarousel').carousel({{ $idx }})"></div>@endforeach</div>@endif
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="celeb-toggle-btn" id="celeb-toggle" onclick="toggleCelebrations(true)"><i class="bi bi-gift-fill"></i></div>
    <script>(function(){ var d=localStorage.getItem('celeb_dismissed')==='true'; document.getElementById('celebration-section').style.display=d?'none':'block'; document.getElementById('celeb-toggle').style.display=d?'flex':'none'; })();</script>
    @endif

    {{-- ═══ TODAY'S LIVE STATUS ═══ --}}
    @php
        $liveStatusCover = get_cover_picture_url($employee->cover_pic ?? null);
        $liveStatusCoverPos = parse_cover_pic_position($employee->cover_pic_position ?? null);
    @endphp
    <div class="live-status-section">
        <div class="live-status-cover {{ $liveStatusCover ? 'has-image' : 'no-image' }}" id="liveStatusCoverWrap">
            <img src="{{ $liveStatusCover ?? '' }}" alt="{{ $employee->name }} cover" id="liveStatusCoverImg"
                 style="object-position: {{ $liveStatusCoverPos['x'] }}% {{ $liveStatusCoverPos['y'] }}%; transform: scale({{ $liveStatusCoverPos['zoom'] / 100 }}); transform-origin: {{ $liveStatusCoverPos['x'] }}% {{ $liveStatusCoverPos['y'] }}%;">
            <div class="live-status-cover-overlay">
                <div class="section-title"><i class="bi bi-activity"></i> Today's Live Status</div>
                <p class="section-subtitle">{{ $data['filter_date'] == date('Y-m-d') ? 'Real-time shift overview' : 'Historical data for ' . $data['filter_date'] }}</p>
            </div>
            <div class="live-status-cover-actions">
                <label class="live-status-cover-btn" for="coverPhotoInput">
                    <i class="bi bi-camera"></i> Change Cover
                </label>
                @if($liveStatusCover)
                    <button type="button" class="live-status-cover-adjust" id="adjustCoverPhotoBtn">
                        <i class="bi bi-arrows-move"></i> Adjust
                    </button>
                    <button type="button" class="live-status-cover-remove" id="removeCoverPhotoBtn">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                @endif
            </div>
            <div class="live-status-cover-adjust-bar" id="coverAdjustBar">
                <span class="live-status-cover-adjust-hint"><i class="bi bi-hand-index"></i> Drag to reposition</span>
                <div class="live-status-cover-zoom">
                    <span>Zoom</span>
                    <input type="range" id="coverZoomSlider" min="100" max="200" step="1" value="{{ $liveStatusCoverPos['zoom'] }}">
                </div>
                <div class="live-status-cover-adjust-actions">
                    <button type="button" class="live-status-cover-adjust-cancel" id="cancelCoverAdjustBtn">Cancel</button>
                    <button type="button" class="live-status-cover-adjust-save" id="saveCoverAdjustBtn">Save</button>
                </div>
            </div>
        </div>
        <div class="live-status-body">
            <div class="section-header {{ $liveStatusCover ? 'd-none' : 'with-upload' }}" id="liveStatusPlainHeader">
                <div>
                    <div class="section-title"><i class="bi bi-activity"></i> Today's Live Status</div>
                    <p class="section-subtitle">{{ $data['filter_date'] == date('Y-m-d') ? 'Real-time shift overview' : 'Historical data for ' . $data['filter_date'] }}</p>
                </div>
                <div class="live-status-upload-inline">
                    <label for="coverPhotoInput">
                        <i class="bi bi-image"></i> Add Cover Photo
                    </label>
                </div>
            </div>
            <input type="file" id="coverPhotoInput" accept="image/jpeg,image/png,image/jpg,image/webp" class="d-none">
            <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-icon icon-sky"><i class="bi bi-briefcase"></i></div><div class="metric-body"><div class="metric-label">Assigned Shift</div><div class="metric-value" style="font-size:1.15rem">{{ $dashboardData['assigned_shift']['shift_name'] ?? 'No Shift' }}</div><div class="metric-hint">@if(isset($dashboardData['assigned_shift']['start_time'])){{ \Carbon\Carbon::parse($dashboardData['assigned_shift']['start_time'])->format('h:i A') }} - {{ \Carbon\Carbon::parse($dashboardData['assigned_shift']['end_time'])->format('h:i A') }}@else Off Day @endif</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-icon icon-emerald"><i class="bi bi-box-arrow-in-right"></i></div><div class="metric-body"><div class="metric-label">Check In</div><div class="metric-value">{{ $dashboardData['attendance_stats']['check_in'] ? \Carbon\Carbon::parse($dashboardData['attendance_stats']['check_in'])->format('h:i A') : '--:--' }}</div><div class="metric-hint">Status: <span class="fw-bold {{ $dashboardData['attendance_status'] == 'Checked In' ? 'text-success' : 'text-danger' }}">{{ $dashboardData['attendance_status'] }}</span></div></div></div></div>
        @php
            $todayAtt = $dashboardData['attendance_id'] ? \App\Models\Attendance::find($dashboardData['attendance_id']) : null;
            $checkOutDisplay = ($todayAtt && $todayAtt->check_out) ? \Carbon\Carbon::parse($todayAtt->check_out)->format('h:i A') : '--:--';
        @endphp
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-icon icon-rose"><i class="bi bi-box-arrow-left"></i></div><div class="metric-body"><div class="metric-label">Check Out</div><div class="metric-value">{{ $checkOutDisplay }}</div><div class="metric-hint">Out punch time</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="metric-card"><div class="metric-icon icon-indigo"><i class="bi bi-hourglass-split"></i></div><div class="metric-body"><div class="metric-label">Hours Worked</div><div class="metric-value">{{ $dashboardData['break_stats']['working_hours'] ?? '--' }}</div><div class="metric-hint">Active hours today</div></div></div></div>
            </div>
        </div>
    </div>

    {{-- ═══ MONTHLY ATTENDANCE OVERVIEW ═══ --}}
    <div class="section-header"><div class="section-title"><i class="bi bi-bar-chart-line-fill"></i> Attendance Overview</div><p class="section-subtitle">Your attendance performance this month</p></div>
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6"><div class="metric-card"><div class="metric-icon icon-indigo"><i class="bi bi-calendar3"></i></div><div class="metric-body"><div class="metric-label">Total Days</div><div class="metric-value">{{ $data['total_days'] }}</div><div class="metric-hint">Working days</div></div></div></div>
        <div class="col-xl-2 col-md-4 col-6"><div class="metric-card"><div class="metric-icon icon-emerald"><i class="bi bi-check-circle"></i></div><div class="metric-body"><div class="metric-label">On Time</div><div class="metric-value">{{ $data['on_time_percentage'] }}%</div><div class="metric-hint">Punctuality rate</div></div></div></div>
        <div class="col-xl-2 col-md-4 col-6"><div class="metric-card"><div class="metric-icon icon-sky"><i class="bi bi-clock-history"></i></div><div class="metric-body"><div class="metric-label">On Time</div><div class="metric-value">{{ $data['on_time_count'] }}</div><div class="metric-hint">Days on time</div></div></div></div>
        <div class="col-xl-2 col-md-4 col-6"><div class="metric-card"><div class="metric-icon icon-rose"><i class="bi bi-exclamation-circle"></i></div><div class="metric-body"><div class="metric-label">Late</div><div class="metric-value" style="color:var(--danger-rose)">{{ $data['late_count'] }}</div><div class="metric-hint">Late check-ins</div></div></div></div>
        <div class="col-xl-2 col-md-4 col-6"><div class="metric-card"><div class="metric-icon icon-amber"><i class="bi bi-clock"></i></div><div class="metric-body"><div class="metric-label">Half Days</div><div class="metric-value">{{ $data['half_day_count'] }}</div><div class="metric-hint">This month</div></div></div></div>
        <div class="col-xl-2 col-md-4 col-6"><div class="metric-card"><div class="metric-icon icon-slate"><i class="bi bi-x-circle"></i></div><div class="metric-body"><div class="metric-label">Absent</div><div class="metric-value">{{ $data['absent_days'] }}</div><div class="metric-hint">Missed days</div></div></div></div>
    </div>

    {{-- ═══ LEAVE & BREAK STATS + CHART ═══ --}}
    <div class="row g-3 mb-4">
        {{-- Leave Balance Card --}}
        <div class="col-xl-4 col-lg-6">
            <div class="panel-card">
                <div class="panel-header"><div class="panel-title"><i class="bi bi-airplane"></i> Leave Overview</div><span class="status-badge badge-info">{{ $dashboardData['leave_stats']['pending_count'] ?? 0 }} Pending</span></div>
                <div class="panel-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="metric-label">Annual Balance</div>
                            <div class="d-flex align-items-baseline gap-1"><span class="metric-value" style="font-size:2rem">{{ $dashboardData['leave_stats']['total_remaining'] ?? 0 }}</span><span class="metric-hint">/ {{ $dashboardData['leave_stats']['total_allocated'] ?? 0 }} days</span></div>
                        </div>
                        <div class="donut-wrapper">
                            @php $usedPct = ($dashboardData['leave_stats']['total_allocated'] ?? 1) > 0 ? round((($dashboardData['leave_stats']['total_used'] ?? 0) / ($dashboardData['leave_stats']['total_allocated'] ?? 1)) * 100) : 0; @endphp
                            <svg viewBox="0 0 36 36" style="width:100%;height:100%;transform:rotate(-90deg)">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#6366f1" stroke-width="3" stroke-dasharray="{{ $usedPct }} {{ 100 - $usedPct }}" stroke-linecap="round"/>
                            </svg>
                            <div class="donut-center"><div class="donut-value">{{ $usedPct }}%</div><div class="donut-label">Used</div></div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4 text-center"><div class="p-2 rounded-3" style="background:rgba(16,185,129,0.06)"><div style="font-size:0.65rem;font-weight:700;color:var(--slate-500);text-transform:uppercase">Approved</div><div style="font-family:'Outfit';font-weight:800;font-size:1.1rem;color:var(--success-emerald)">{{ $dashboardData['leave_stats']['approved_data']['year']['total'] ?? 0 }}</div></div></div>
                        <div class="col-4 text-center"><div class="p-2 rounded-3" style="background:rgba(244,63,94,0.06)"><div style="font-size:0.65rem;font-weight:700;color:var(--slate-500);text-transform:uppercase">Rejected</div><div style="font-family:'Outfit';font-weight:800;font-size:1.1rem;color:var(--danger-rose)">{{ $dashboardData['leave_stats']['cancelled_data']['year']['total'] ?? 0 }}</div></div></div>
                        <div class="col-4 text-center"><div class="p-2 rounded-3" style="background:rgba(245,158,11,0.06)"><div style="font-size:0.65rem;font-weight:700;color:var(--slate-500);text-transform:uppercase">Half Days</div><div style="font-family:'Outfit';font-weight:800;font-size:1.1rem;color:var(--warning-amber)">{{ $dashboardData['leave_stats']['half_days_data']['year'] ?? 0 }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Break Stats Card --}}
        <div class="col-xl-4 col-lg-6">
            <div class="panel-card">
                <div class="panel-header"><div class="panel-title"><i class="bi bi-cup-hot"></i> Break Status</div>
                    @if($dashboardData['isOnBreak'])<span class="status-badge badge-danger"><span style="width:6px;height:6px;background:#f43f5e;border-radius:50%;display:inline-block;animation:floatY 1.5s infinite"></span> On Break</span>@else<span class="status-badge badge-success">Available</span>@endif
                </div>
                <div class="panel-body">
                    @php $bs = $dashboardData['break_stats'] ?? []; @endphp
                    <div class="row g-3 mb-3">
                        <div class="col-6"><div class="metric-label">Break Used</div><div class="metric-value" style="font-size:1.3rem">{{ $bs['total_spent'] ?? '0 min' }}</div></div>
                        <div class="col-6"><div class="metric-label">Remaining</div><div class="metric-value" style="font-size:1.3rem;color:var(--success-emerald)">{{ $bs['remaining'] ?? '0 min' }}</div></div>
                    </div>
                    <div class="mb-2"><div class="d-flex justify-content-between mb-1"><span class="metric-label">Break Usage</span><span class="metric-hint">{{ $bs['total_spent_minutes'] ?? 0 }}/{{ $bs['allowed_break_minutes'] ?? 0 }} min</span></div>
                        @php $breakPct = ($bs['allowed_break_minutes'] ?? 0) > 0 ? min(100, round((($bs['total_spent_minutes'] ?? 0) / $bs['allowed_break_minutes']) * 100)) : 0; @endphp
                        <div class="progress-bar-track"><div class="progress-bar-fill" style="width:{{ $breakPct }}%;background:{{ $breakPct > 100 ? 'var(--danger-rose)' : 'var(--primary-indigo)' }}"></div></div>
                    </div>
                    @if(($bs['exceeded_minutes'] ?? 0) > 0)<div class="status-badge badge-danger mt-2" style="font-size:0.6rem"><i class="bi bi-exclamation-triangle"></i> Exceeded by {{ $bs['exceeded'] }}</div>@endif
                    <div class="mt-3"><div class="metric-label mb-2">Today's Breaks</div>
                        @forelse($todayBreaks->take(3) as $br)
                        <div class="d-flex align-items-center justify-content-between py-1" style="border-bottom:1px solid var(--slate-100)">
                            <span style="font-size:0.75rem;font-weight:600;color:var(--slate-700)">{{ \Carbon\Carbon::parse($br->created_at)->format('h:i A') }}</span>
                            <span style="font-size:0.7rem;font-weight:700;color:var(--slate-500)">{{ $br->spent_minutes ?? '-' }} min</span>
                            <span class="status-badge {{ $br->end_time ? 'badge-success' : 'badge-warning' }}">{{ $br->end_time ? 'Done' : 'Active' }}</span>
                        </div>
                        @empty<div class="metric-hint">No breaks recorded today</div>@endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Weekly Attendance Chart --}}
        <div class="col-xl-4 col-lg-12">
            <div class="panel-card">
                <div class="panel-header"><div class="panel-title"><i class="bi bi-graph-up"></i> Weekly Trend</div><span class="metric-hint">Last 4 weeks</span></div>
                <div class="panel-body">
                    <div class="d-flex align-items-end justify-content-around" style="height:140px;padding-bottom:10px">
                        @foreach($weeklyAttendance as $week)
                        <div class="text-center">
                            <div class="d-flex align-items-end gap-1 justify-content-center" style="height:110px">
                                <div style="width:18px;background:var(--primary-indigo);border-radius:5px 5px 0 0;height:{{ max(8, ($week['on_time'] ?? 0) * 18) }}px;opacity:0.85" title="On Time: {{ $week['on_time'] }}"></div>
                                <div style="width:18px;background:var(--danger-rose);border-radius:5px 5px 0 0;height:{{ max(4, ($week['late'] ?? 0) * 18) }}px;opacity:0.85" title="Late: {{ $week['late'] }}"></div>
                            </div>
                            <div style="font-size:0.62rem;font-weight:700;color:var(--slate-500);margin-top:6px">{{ $week['short_label'] }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-center gap-4 mt-3">
                        <div class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:3px;background:var(--primary-indigo);display:inline-block"></span><span style="font-size:0.68rem;font-weight:700;color:var(--slate-500)">On Time</span></div>
                        <div class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:3px;background:var(--danger-rose);display:inline-block"></span><span style="font-size:0.68rem;font-weight:700;color:var(--slate-500)">Late</span></div>
                    </div>
                    <div class="row g-2 mt-3">
                        <div class="col-6"><div class="p-2 rounded-3 text-center" style="background:rgba(99,102,241,0.06)"><div style="font-size:0.6rem;font-weight:700;color:var(--slate-500);text-transform:uppercase">Avg Hours</div><div style="font-family:'Outfit';font-weight:800;font-size:1rem;color:var(--primary-indigo)">{{ $data['avg_working_hours'] }}</div></div></div>
                        <div class="col-6"><div class="p-2 rounded-3 text-center" style="background:rgba(16,185,129,0.06)"><div style="font-size:0.6rem;font-weight:700;color:var(--slate-500);text-transform:uppercase">Present</div><div style="font-family:'Outfit';font-weight:800;font-size:1rem;color:var(--success-emerald)">{{ $data['present_days'] }}/{{ $data['work_days_in_month'] }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ UPCOMING HOLIDAYS + RECENT LEAVES + ATTENDANCE LOG ═══ --}}
    <div class="row g-3 mb-4">
        {{-- Upcoming Holidays --}}
        <div class="col-xl-4 col-lg-6">
            <div class="panel-card-v2">
                <div class="panel-header-v2">
                    <div class="panel-title-v2"><span class="pt-icon icon-indigo"><i class="bi bi-calendar-heart"></i></span> Upcoming Holidays</div>
                </div>
                <div class="panel-body-v2">
                    <div class="timeline-list">
                        @forelse($upcomingHolidays as $holiday)
                        <div class="timeline-item">
                            <div class="tl-icon tl-indigo"><i class="bi bi-flag-fill"></i></div>
                            <div class="tl-body">
                                <div class="tl-title">{{ $holiday->title }}</div>
                                <div class="tl-sub">{{ \Carbon\Carbon::parse($holiday->start_date)->format('d M, Y') }}@if($holiday->end_date && $holiday->end_date != $holiday->start_date) - {{ \Carbon\Carbon::parse($holiday->end_date)->format('d M') }}@endif</div>
                            </div>
                            <div class="tl-badge">
                                @php $daysLeft = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($holiday->start_date)->startOfDay(), false); @endphp
                                <span class="tl-status {{ $daysLeft == 0 ? 'st-today' : ($daysLeft <= 3 ? 'st-warning' : 'st-neutral') }}">{{ $daysLeft == 0 ? 'TODAY' : ($daysLeft == 1 ? 'TOMORROW' : 'IN '.$daysLeft.' DAYS') }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state"><i class="bi bi-calendar-check"></i><p>No upcoming holidays</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Leave Requests --}}
        <div class="col-xl-4 col-lg-6">
            <div class="panel-card-v2">
                <div class="panel-header-v2">
                    <div class="panel-title-v2"><span class="pt-icon icon-emerald"><i class="bi bi-send-check"></i></span> Recent Leave Requests</div>
                    @if($pendingLeaves > 0)<span class="tl-status st-warning">{{ $pendingLeaves }} PENDING</span>@endif
                </div>
                <div class="panel-body-v2">
                    <div class="timeline-list">
                        @forelse($recentLeaves as $leave)
                        <div class="timeline-item">
                            <div class="tl-icon {{ $leave->status == 'Approved' ? 'tl-emerald' : ($leave->status == 'Rejected' || $leave->status == 'Cancelled' ? 'tl-rose' : 'tl-amber') }}">
                                <i class="bi {{ $leave->status == 'Approved' ? 'bi-check-lg' : ($leave->status == 'Rejected' || $leave->status == 'Cancelled' ? 'bi-x-lg' : 'bi-hourglass-split') }}"></i>
                            </div>
                            <div class="tl-body">
                                <div class="tl-title">{{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }}@if($leave->end_date != $leave->start_date) - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M') }}@endif</div>
                                <div class="tl-sub">{{ Str::limit($leave->reason, 35) }}</div>
                            </div>
                            <div class="tl-badge">
                                <span class="tl-status {{ $leave->status == 'Approved' ? 'st-success' : ($leave->status == 'Rejected' || $leave->status == 'Cancelled' ? 'st-danger' : 'st-warning') }}">{{ strtoupper($leave->status) }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state"><i class="bi bi-inbox"></i><p>No recent leave requests</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Late Arrival History --}}
        <div class="col-xl-4 col-lg-12">
            <div class="panel-card-v2">
                <div class="panel-header-v2">
                    <div class="panel-title-v2"><span class="pt-icon icon-rose"><i class="bi bi-alarm"></i></span> Late Arrivals</div>
                </div>
                <div class="panel-body-v2">
                    <div class="timeline-list">
                        @forelse($recentLateArrivals as $late)
                        <div class="timeline-item">
                            <div class="tl-icon tl-rose"><i class="bi bi-clock-history"></i></div>
                            <div class="tl-body">
                                <div class="tl-title">{{ \Carbon\Carbon::parse($late->date)->format('d M, Y') }}</div>
                                <div class="tl-sub">{{ $late->late_minutes ?? 0 }} minutes late</div>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state"><i class="bi bi-emoji-laughing"></i><p>No late arrivals — Keep it up!</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ ATTENDANCE LOG TABLE ═══ --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="panel-card">
                <div class="panel-header"><div class="panel-title"><i class="bi bi-table"></i> Monthly Attendance Log</div><span class="metric-hint">{{ now()->format('F Y') }}</span></div>
                <div class="panel-body p-0">
                    <div class="table-responsive">
                        <table class="saas-table">
                            <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse($monthlyHistory as $att)
                                <tr>
                                    <td><span style="font-weight:700">{{ \Carbon\Carbon::parse($att->shift_date)->format('d M, D') }}</span></td>
                                    <td>{{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '--' }}</td>
                                    <td>{{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '--' }}</td>
                                    <td>@if($att->check_in && $att->check_out)@php $ci=\Carbon\Carbon::parse($att->check_in);$co=\Carbon\Carbon::parse($att->check_out);if($co->lt($ci))$co->addDay();$mins=$ci->diffInMinutes($co); @endphp {{ intdiv($mins,60) }}h {{ $mins%60 }}m @else -- @endif</td>
                                    <td>@if($att->lateArrival && $att->lateArrival->late_minutes > 0)<span class="status-badge badge-danger">Late</span>@elseif($att->check_in)<span class="status-badge badge-success">On Time</span>@else<span class="status-badge badge-neutral">--</span>@endif</td>
                                </tr>
                                @empty<tr><td colspan="5" class="text-center py-4"><div class="metric-hint">No attendance records this month</div></td></tr>@endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('assets/pages/dashboard.js') }}"></script>
<script>
function toggleCelebrations(show) {
    if (show) {
        $('#celeb-toggle').fadeOut(300, function() { $('#celebration-section').slideDown(500); });
        localStorage.setItem('celeb_dismissed', 'false');
    } else {
        $('#celebration-section').slideUp(500, function() { $('#celeb-toggle').css('display','flex').hide().fadeIn(300); });
        localStorage.setItem('celeb_dismissed', 'true');
    }
}

(function () {
    const input = document.getElementById('coverPhotoInput');
    const coverWrap = document.getElementById('liveStatusCoverWrap');
    const coverImg = document.getElementById('liveStatusCoverImg');
    const plainHeader = document.getElementById('liveStatusPlainHeader');
    const removeBtn = document.getElementById('removeCoverPhotoBtn');
    const adjustBtn = document.getElementById('adjustCoverPhotoBtn');
    const cancelAdjustBtn = document.getElementById('cancelCoverAdjustBtn');
    const saveAdjustBtn = document.getElementById('saveCoverAdjustBtn');
    const zoomSlider = document.getElementById('coverZoomSlider');
    const csrf = '{{ csrf_token() }}';

    if (!input || !coverWrap) return;

    const defaults = { x: 50, y: 35, zoom: 100 };
    let savedPos = @json($liveStatusCoverPos);
    let pos = { ...savedPos };
    let adjusting = false;
    let dragging = false;
    let dragStart = null;

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function applyCoverTransform() {
        if (!coverImg) return;
        coverImg.style.objectPosition = `${pos.x}% ${pos.y}%`;
        coverImg.style.transform = `scale(${pos.zoom / 100})`;
        coverImg.style.transformOrigin = `${pos.x}% ${pos.y}%`;
        if (zoomSlider) zoomSlider.value = String(Math.round(pos.zoom));
    }

    function showCover(url, position) {
        coverImg.src = url;
        coverWrap.classList.remove('no-image');
        coverWrap.classList.add('has-image');
        plainHeader.classList.add('d-none');
        savedPos = position || { ...defaults };
        pos = { ...savedPos };
        applyCoverTransform();
        ensureCoverActionButtons();
    }

    function hideCover() {
        exitAdjustMode(false);
        coverImg.removeAttribute('src');
        coverWrap.classList.add('no-image');
        coverWrap.classList.remove('has-image');
        plainHeader.classList.remove('d-none');
        savedPos = { ...defaults };
        pos = { ...savedPos };
    }

    function ensureCoverActionButtons() {
        const actions = coverWrap.querySelector('.live-status-cover-actions');
        if (!actions) return;

        if (!document.getElementById('adjustCoverPhotoBtn')) {
            const adjust = document.createElement('button');
            adjust.type = 'button';
            adjust.id = 'adjustCoverPhotoBtn';
            adjust.className = 'live-status-cover-adjust';
            adjust.innerHTML = '<i class="bi bi-arrows-move"></i> Adjust';
            adjust.addEventListener('click', enterAdjustMode);
            const remove = document.getElementById('removeCoverPhotoBtn');
            actions.insertBefore(adjust, remove || null);
        }

        if (!document.getElementById('removeCoverPhotoBtn')) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'removeCoverPhotoBtn';
            btn.className = 'live-status-cover-remove';
            btn.innerHTML = '<i class="bi bi-trash"></i> Remove';
            btn.addEventListener('click', removeCover);
            actions.appendChild(btn);
        }
    }

    function enterAdjustMode() {
        if (!coverWrap.classList.contains('has-image')) return;
        adjusting = true;
        pos = { ...savedPos };
        applyCoverTransform();
        coverWrap.classList.add('is-adjusting');
    }

    function exitAdjustMode(restoreSaved) {
        adjusting = false;
        dragging = false;
        dragStart = null;
        coverWrap.classList.remove('is-adjusting', 'is-dragging');
        if (restoreSaved) {
            pos = { ...savedPos };
            applyCoverTransform();
        }
    }

    function onPointerDown(e) {
        if (!adjusting || e.target.closest('.live-status-cover-adjust-bar')) return;
        dragging = true;
        dragStart = { x: e.clientX, y: e.clientY, posX: pos.x, posY: pos.y };
        coverWrap.classList.add('is-dragging');
        e.preventDefault();
    }

    function onPointerMove(e) {
        if (!dragging || !dragStart) return;
        const dx = e.clientX - dragStart.x;
        const dy = e.clientY - dragStart.y;
        pos.x = clamp(dragStart.posX - dx * 0.18, 0, 100);
        pos.y = clamp(dragStart.posY - dy * 0.18, 0, 100);
        applyCoverTransform();
    }

    function onPointerUp() {
        dragging = false;
        dragStart = null;
        coverWrap.classList.remove('is-dragging');
    }

    function saveAdjustments() {
        saveAdjustBtn.disabled = true;

        fetch('{{ route('employee.profile.cover.position') }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                x: Math.round(pos.x * 10) / 10,
                y: Math.round(pos.y * 10) / 10,
                zoom: Math.round(pos.zoom * 10) / 10,
            }),
        })
            .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) throw new Error(data.message || 'Could not save position');
                savedPos = data.cover_pic_position || { ...pos };
                pos = { ...savedPos };
                applyCoverTransform();
                exitAdjustMode(false);
            })
            .catch((err) => alert(err.message || 'Could not save cover position'))
            .finally(() => { saveAdjustBtn.disabled = false; });
    }

    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('cover_pic', file);
        formData.append('_token', csrf);

        fetch('{{ route('employee.profile.cover.update') }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) throw new Error(data.message || 'Upload failed');
                showCover(data.cover_pic_url, data.cover_pic_position);
            })
            .catch((err) => alert(err.message || 'Could not upload cover photo'))
            .finally(() => { input.value = ''; });
    });

    function removeCover() {
        if (!confirm('Remove cover photo?')) return;

        fetch('{{ route('employee.profile.cover.remove') }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) throw new Error(data.message || 'Remove failed');
                hideCover();
                document.getElementById('adjustCoverPhotoBtn')?.remove();
                document.getElementById('removeCoverPhotoBtn')?.remove();
            })
            .catch((err) => alert(err.message || 'Could not remove cover photo'));
    }

    if (removeBtn) removeBtn.addEventListener('click', removeCover);
    if (adjustBtn) adjustBtn.addEventListener('click', enterAdjustMode);
    if (cancelAdjustBtn) cancelAdjustBtn.addEventListener('click', () => exitAdjustMode(true));
    if (saveAdjustBtn) saveAdjustBtn.addEventListener('click', saveAdjustments);
    if (zoomSlider) {
        zoomSlider.addEventListener('input', () => {
            pos.zoom = clamp(parseFloat(zoomSlider.value) || 100, 100, 200);
            applyCoverTransform();
        });
    }

    coverWrap.addEventListener('mousedown', onPointerDown);
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseup', onPointerUp);
    coverWrap.addEventListener('touchstart', (e) => {
        if (!adjusting || !e.touches[0]) return;
        onPointerDown({ clientX: e.touches[0].clientX, clientY: e.touches[0].clientY, target: e.target, preventDefault: () => e.preventDefault() });
    }, { passive: false });
    window.addEventListener('touchmove', (e) => {
        if (!dragging || !e.touches[0]) return;
        onPointerMove({ clientX: e.touches[0].clientX, clientY: e.touches[0].clientY });
    }, { passive: true });
    window.addEventListener('touchend', onPointerUp);

    applyCoverTransform();
})();
</script>
@endsection