@extends('employee.layouts.app')

@section('content')
<div class="employee-portal-page">
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-sm-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">Performance Intelligence Report</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}" class="text-muted text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active fw-bold text-indigo" aria-current="page">Monthly Report</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-white border-light shadow-sm rounded-pill px-3">
                        <i class="bi bi-printer me-2"></i> Print Report
                    </button>
                    <span class="badge bg-indigo-soft text-indigo border px-3 py-2 rounded-pill shadow-sm d-flex align-items-center">
                        <i class="bi bi-calendar-check me-2"></i> {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Selector -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
        <div class="card-body p-3">
            <form action="{{ route('employee.report') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="month" class="form-select border-0 bg-light-soft rounded-pill px-4" onchange="this.form.submit()">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="year" class="form-select border-0 bg-light-soft rounded-pill px-4" onchange="this.form.submit()">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Auto-Generated Report</small>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Matrix -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="report-stat-card bg-white p-4 h-100 shadow-sm border-0 border-bottom border-indigo border-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-indigo-soft text-indigo rounded-circle me-3">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <span class="text-muted fw-bold text-uppercase ls-1 small">Attendance Rate</span>
                </div>
                <h3 class="fw-bold mb-2">{{ $stats['attendance_rate'] }}%</h3>
                <div class="progress" style="height: 6px; border-radius: 10px;">
                    <div class="progress-bar bg-indigo" role="progressbar" style="width: {{ $stats['attendance_rate'] }}%"></div>
                </div>
                <small class="text-muted d-block mt-2">{{ $stats['present_days'] }} / {{ $stats['total_work_days'] }} Work Days</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="report-stat-card bg-white p-4 h-100 shadow-sm border-0 border-bottom border-success border-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-success-soft text-success rounded-circle me-3">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <span class="text-muted fw-bold text-uppercase ls-1 small">Total Productivity</span>
                </div>
                <h3 class="fw-bold mb-2 text-dark">{{ $stats['productive_hours'] }}</h3>
                <small class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i> Logged Work Hours</small>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="report-stat-card bg-white p-4 h-100 shadow-sm border-0 border-bottom border-warning border-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-warning-soft text-warning rounded-circle me-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <span class="text-muted fw-bold text-uppercase ls-1 small">Compliance Issues</span>
                </div>
                <h3 class="fw-bold mb-2">{{ $stats['late_days'] + $stats['half_days'] }}</h3>
                <div class="d-flex gap-3">
                    <small class="text-warning">Late: {{ $stats['late_days'] }}</small>
                    <small class="text-danger">Half: {{ $stats['half_days'] }}</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="report-stat-card bg-white p-4 h-100 shadow-sm border-0 border-bottom border-danger border-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-danger-soft text-danger rounded-circle me-3">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <span class="text-muted fw-bold text-uppercase ls-1 small">Absenteeism</span>
                </div>
                <h3 class="fw-bold mb-2 text-danger">{{ $stats['absent_days'] }} <span class="text-muted h6">Days</span></h3>
                <small class="text-muted">Exc. {{ $stats['leaves'] }} Approved Leaves</small>
            </div>
        </div>
    </div>

    <!-- Detailed Log -->
    <div class="card border-0 shadow-lg overflow-hidden mb-5" style="border-radius: 24px;">
        <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Outfit', sans-serif;">Daily Performance Journal</h5>
                <p class="text-muted small mb-0">Detailed list of all activities for this period</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-normal">
                    <i class="bi bi-file-earmark-text me-1"></i> Data Audit
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft text-uppercase small fw-bold text-muted">
                        <tr>
                            <th class="ps-4 py-3">Date</th>
                            <th class="py-3">Shift</th>
                            <th class="py-3">Punch IN</th>
                            <th class="py-3">Punch OUT</th>
                            <th class="py-3">Working Time</th>
                            <th class="py-3">Break Time</th>
                            <th class="py-3 text-end pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                            @php
                                $breakStats = calculate_break_stats($employee, null, $att->shift_date);
                                $statusClass = 'bg-success';
                                if($att->lateArrival) $statusClass = 'bg-warning';
                                if($att->halfDay) $statusClass = 'bg-danger';
                                if($att->status !== 'Present') $statusClass = 'bg-secondary';
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($att->shift_date)->format('d-M') }}</span>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($att->shift_date)->format('l') }}</small>
                                    </div>
                                </td>
                                <td><span class="text-muted small fw-bold">{{ $att->shift->shift_name ?? '-' }}</span></td>
                                <td>{{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('H:i:s') : '-' }}</td>
                                <td>
                                    @if($att->check_out)
                                        {{ \Carbon\Carbon::parse($att->check_out)->format('H:i:s') }}
                                    @elseif($att->status === 'Present')
                                        <span class="badge bg-soft-primary text-primary px-2 rounded-pill">Active</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold {{ floatval($breakStats['working_minutes']) > 480 ? 'text-success' : 'text-dark' }}">
                                        {{ calculateWorkedHours($att) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $breakStats['total_spent_minutes'] }} min</span>
                                    @if($breakStats['exceeded_minutes'] > 0)
                                        <small class="text-danger ms-1">+{{ $breakStats['exceeded_minutes'] }}m</small>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($att->status !== 'Present')
                                        <span class="badge bg-soft-danger text-danger rounded-pill px-3">Absent</span>
                                    @elseif($att->halfDay)
                                        <span class="badge bg-soft-danger text-danger rounded-pill px-3">Half Day</span>
                                    @elseif($att->lateArrival)
                                        <span class="badge bg-soft-warning text-warning rounded-pill px-3">Late</span>
                                    @else
                                        <span class="badge bg-soft-success text-success rounded-pill px-3">On Time</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted py-4">
                                        <i class="bi bi-folder2-open display-4 d-block mb-3"></i>
                                        No logs found for this period.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection


