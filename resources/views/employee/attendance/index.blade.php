@extends('employee.layouts.app')

@section('title', 'My Attendance')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="employee-portal-page">
    <div class="container-fluid">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="page-title mb-1">My Timesheet</h3>
                <p class="text-muted small mb-0 fw-bold">View your monthly attendance records</p>
            </div>
        </div>

        @php use Carbon\Carbon; @endphp

        <!-- Filters (Command Center) -->
        <div class="command-center">
            <form method="GET" action="{{ route('employee.attendance.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em;">Select Shift</label>
                        <select name="shift_id" class="saas-filter-select">
                            <option value="">All Shifts</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->shift_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em;">Month</label>
                        <select name="month" class="saas-filter-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em;">Year</label>
                        <select name="year" class="saas-filter-select">
                            @for ($i = now()->year; $i >= now()->year - 5; $i--)
                                <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn-saas-generate w-100 justify-content-center">
                            <i class="bi bi-funnel-fill"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($attendances->isEmpty())
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
                <h5 class="fw-bold" style="color: var(--slate-600);">No Attendance Records</h5>
                <p class="text-muted small">You don't have any attendance records for the selected filters.</p>
            </div>
        @else
            <div class="logs-table-card">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Late Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                                @php
                                    $statusClass = 'present';
                                    $statusLower = strtolower($attendance->status);
                                    if(str_contains($statusLower, 'late')) $statusClass = 'late';
                                    elseif(str_contains($statusLower, 'absent')) $statusClass = 'absent';
                                    elseif(str_contains($statusLower, 'half')) $statusClass = 'halfday';
                                    elseif(str_contains($statusLower, 'holiday')) $statusClass = 'holiday';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold" style="color: var(--primary-indigo);">
                                            {{ \Carbon\Carbon::parse($attendance->shift_date)->format('d M, Y') }}
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            {{ \Carbon\Carbon::parse($attendance->shift_date)->format('l') }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                            {{ $attendance->shift->shift_name }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($attendance->check_in)
                                            <span class="time-box text-success">
                                                <i class="bi bi-box-arrow-in-right me-1"></i> {{ $attendance->check_in->format('h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-muted small fw-bold">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->check_out)
                                            <span class="time-box text-danger">
                                                <i class="bi bi-box-arrow-left me-1"></i> {{ $attendance->check_out->format('h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-muted small fw-bold">--:--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->late_duration > 0)
                                            <span class="text-danger fw-bold"><i class="bi bi-clock-history"></i> {{ $attendance->late_duration }} mins</span>
                                        @else
                                            <span class="text-muted small">On Time</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="saas-status-badge {{ $statusClass }}">
                                            {{ $attendance->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Links -->
            <div class="mt-4 d-flex justify-content-end">
                {{ $attendances->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection