@extends('admin.layouts.app')

@section('title', 'Half-Day Compliance Hub')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
<div class="mb-4 flex w-full items-center justify-between">
    <div>
        <h3 class="tw-page-title text-2xl">Half-Day Compliance Hub</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-xs font-bold uppercase tracking-wide">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-slate-500 hover:text-brand-600">Audit</a></li>
                <li class="breadcrumb-item active text-brand-600">Incident Records</li>
            </ol>
        </nav>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="tw-stat-card">
            <div class="icon-container !bg-amber-50 !text-amber-500">
                <i class="bi bi-clock-split"></i>
            </div>
            <div>
                <div class="stat-label">Incident Volume</div>
                <div class="stat-value" id="stat-incidents">0</div>
            </div>
        </div>
        <div class="tw-stat-card">
            <div class="icon-container">
                <i class="bi bi-person-exclamation"></i>
            </div>
            <div>
                <div class="stat-label">Late Origins</div>
                <div class="stat-value" id="stat-late">0</div>
            </div>
        </div>
        <div class="tw-stat-card">
            <div class="icon-container !bg-pink-50 !text-pink-600">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="stat-label">Short Shifts</div>
                <div class="stat-value" id="stat-short">0</div>
            </div>
        </div>
    </div>

    <div class="tw-command-hub">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
            <div class="md:col-span-4">
                <label class="tw-filter-label">Search Personnel</label>
                <input type="text" id="filter-employee" class="tw-form-input" placeholder="Enter name...">
            </div>
            <div class="md:col-span-4">
                <label class="tw-filter-label">Incident Date</label>
                <input type="date" id="filter-date" class="tw-form-input">
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button id="filter-button" class="tw-btn-primary h-12 flex-1">
                    <i class="bi bi-funnel-fill"></i> Filter Matrix
                </button>
                <button id="reset-filters" class="tw-action-btn h-12 w-12 shrink-0" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="tw-directory-card overflow-hidden p-0">
        <div class="border-b border-slate-100 p-5">
            <h5 class="mb-0 font-display text-lg font-extrabold text-slate-900">Variance Matrix</h5>
            <p class="mb-0 text-sm text-slate-500">Detailed breakdown of half-day deviations</p>
        </div>
        <div class="table-responsive p-1">
            <table class="table table-hover w-100 tw-admin-table" id="halfday-table">
                <thead>
                    <tr>
                        <th>Identity Architecture</th>
                        <th>Timestamp</th>
                        <th>Evaluation Reason</th>
                        <th>Allocated Shift</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Working Net</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        const table = $('#halfday-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.half_days.data") }}',
                data: d => {
                    d.name = $('#filter-employee').val();
                    d.date = $('#filter-date').val();
                },
                dataSrc: json => {
                    $('#stat-incidents').text(json.total_incidents);
                    $('#stat-late').text(json.late_start_count);
                    $('#stat-short').text(json.short_shift_count);
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'emp_name',
                    name: 'employees.name',
                    render: function(data, type, row) {
                        const initials = data.split(' ').map(n => n[0]).join('').toUpperCase();
                        const avatar = row.profile_pic_url ? `<img src="${row.profile_pic_url}" class="emp-avatar">` : `<div class="emp-avatar">${initials}</div>`;
                        return `
                            <div class="emp-identity">
                                ${avatar}
                                <div class="emp-info">
                                    <span class="name">${data}</span>
                                    <span class="text-xs font-semibold text-slate-500">EMP-${row.emp_id}</span>
                                </div>
                            </div>
                        `;
                    }
                },
                { data: 'date', name: 'half_days.date' },
                { data: 'reason', name: 'half_days.reason' },
                { data: 'shift', name: 'attendance.shift.shift_name', className: 'fw-bold text-indigo small uppercase' },
                { data: 'check_in', name: 'attendance.check_in', className: 'time-text' },
                { data: 'check_out', name: 'attendance.check_out', className: 'time-text' },
                {
                    data: 'working_duration',
                    name: 'attendance.working_duration',
                    render: d => `<span class="font-extrabold text-slate-900">${d}</span>`
                }
            ],
            order: [[1, 'desc']],
            dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
            language: {
                search: "",
                searchPlaceholder: "Search violations...",
            },
            pageLength: 20
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

        $('#filter-button').on('click', () => table.draw());
        $('#reset-filters').on('click', () => {
            $('#filter-employee').val('');
            $('#filter-date').val('');
            table.draw();
        });
    });
</script>
@endpush
