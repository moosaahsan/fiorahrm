@extends('employee.layouts.app')

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    </div>

    
    @endsection

@section('content')
<div class="employee-portal-page">
<div class="perf-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title-elite">My Performance History</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-elite">
                    <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Performance</li>
                </ol>
            </nav>
        </div>
        <div class="d-none d-md-block">
            <span class="text-muted small"><i class="far fa-user me-1"></i> {{ $employee->name }}</span>
        </div>
    </div>

    <!-- Info Card -->
    <div class="info-card">
        <h5><i class="fas fa-chart-line me-2"></i> Your Performance Evaluations</h5>
        <p>This is your monthly performance record. Scores are calculated automatically (attendance, leaves, breaks, lates) and combined with manual ratings from your manager (dress code, work performance, behavior). Total adds up to 100.</p>
    </div>

    <!-- Table -->
    <div class="perf-card">
        <table id="perf-table" class="table w-100">
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="text-center">Attendance</th>
                    <th class="text-center">Leaves</th>
                    <th class="text-center">Breaks</th>
                    <th class="text-center">Lates</th>
                    <th class="text-center">Auto Score</th>
                    <th class="text-center">Dress Code</th>
                    <th class="text-center">Work Perf</th>
                    <th class="text-center">Behavior</th>
                    <th class="text-center">Manual Score</th>
                    <th class="text-center">Total (100)</th>
                    <th>Evaluator</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#perf-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "{{ route('employee.performance.data') }}",
            columns: [
                {data: 'month_name', name: 'month', render: d => `<span class="fw-bold">${d}</span>`},
                {data: 'attendance_score', name: 'attendance_score', className: 'text-center', render: d => `<span class="score-badge score-auto">${d}</span>`},
                {data: 'leave_score', name: 'leave_score', className: 'text-center', render: d => `<span class="score-badge score-auto">${d}</span>`},
                {data: 'break_score', name: 'break_score', className: 'text-center', render: d => `<span class="score-badge score-auto">${d}</span>`},
                {data: 'late_score', name: 'late_score', className: 'text-center', render: d => `<span class="score-badge score-auto">${d}</span>`},
                {data: 'auto_score', name: 'auto_score', className: 'text-center', render: d => `<span class="score-badge score-auto fw-bold">${d}</span>`},
                {data: 'dress_code_score', name: 'dress_code_score', className: 'text-center', render: d => `<span class="score-badge score-manual">${d}</span>`},
                {data: 'work_performance_score', name: 'work_performance_score', className: 'text-center', render: d => `<span class="score-badge score-manual">${d}</span>`},
                {data: 'behavior_score', name: 'behavior_score', className: 'text-center', render: d => `<span class="score-badge score-manual">${d}</span>`},
                {data: 'manual_score', name: 'manual_score', className: 'text-center', render: d => `<span class="score-badge score-manual fw-bold">${d}</span>`},
                {data: 'total_score', name: 'total_score', className: 'text-center', render: d => `<span class="score-badge score-total">${d}</span>`},
                {data: 'evaluator_name', name: 'evaluator_name'}
            ],
            order: [[0, 'desc']],
            language: {
                search: "",
                searchPlaceholder: "Search...",
                lengthMenu: "_MENU_ per page",
                paginate: { next: '<i class="fas fa-chevron-right"></i>', previous: '<i class="fas fa-chevron-left"></i>' }
            },
            dom: '<"d-flex justify-content-between align-items-center p-3"l<"ms-2"f>>rt<"d-flex justify-content-between align-items-center p-3"ip>'
        });
    });
</script>
@endpush
