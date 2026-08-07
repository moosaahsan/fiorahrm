@extends('employee.layouts.app')

@section('content')
<div class="employee-portal-page">
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-sm-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">My Attendance Sheet</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}" class="text-muted text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Monthly Timesheet</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <span class="badge bg-white text-primary border px-3 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-calendar3 me-1"></i> {{ date('F Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header bg-white border-0 p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">Monthly Attendance Grid</h5>
                    <p class="text-muted small mb-0">Visual summary of your presence, leaves, and weekends.</p>
                </div>

                {{-- Month Navigation --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group shadow-sm" role="group">
                        <button id="btnPrevMonth" type="button" class="btn btn-white border-light">
                            <i class="fa fa-chevron-left me-1"></i>
                        </button>
                        <button id="btnThisMonth" type="button" class="btn btn-white border-light text-primary fw-bold">
                            Current
                        </button>
                        <button id="btnNextMonth" type="button" class="btn btn-white border-light">
                            <i class="fa fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-4 pt-0">
            {{-- Filter Bar --}}
            <form id="filterForm" class="row g-3 mb-4 align-items-end p-3 bg-light rounded-4 border border-white">
                <div class="col-md-4">
                    <label for="year" class="form-label fw-bold small text-uppercase text-muted">Year</label>
                    <select class="form-select border-0 shadow-sm" id="year" name="year">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $start->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="month" class="form-label fw-bold small text-uppercase text-muted">Month</label>
                    <select class="form-select border-0 shadow-sm" id="month" name="month">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $start->month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-4 d-grid">
                    <button type="submit" class="tw-btn-primary fw-bold">
                        <i class="fa fa-sync-alt me-2"></i> Update Display
                    </button>
                </div>
            </form>

            {{-- Legend --}}
            <div class="legend-container mb-4">
                <span class="legend-label">VIEW LEGEND:</span>
                <span class="legend-badge badge-present">Present</span>
                <span class="legend-badge badge-late">Late</span>
                <span class="legend-badge badge-half">Half Day</span>
                <span class="legend-badge badge-holiday">Holiday</span>
                <span class="legend-badge badge-leave">Approved (On Leave)</span>
                <span class="legend-badge badge-pending">Pending</span>
                <span class="legend-badge badge-off">OFF</span>
                <span class="legend-badge badge-upcoming">Upcoming</span>
                <span class="legend-badge badge-absent-deducted">Absent (Deducted)</span>
                <span class="legend-badge badge-absent-unpaid">Absent (Unpaid)</span>
            </div>

            <div class="position-relative">
                <div id="tableOverlay" class="loading-overlay d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>

                <div class="border rounded-4 overflow-hidden shadow-sm">
                    <table id="attendanceTable" class="table table-hover table-sm align-middle mb-0 nowrap w-100">
                        <thead id="dynamicTableHead"></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection



@push('scripts')
    <script>
        $(function () {
            const initStart = '{{ $start->format("Y-m-d") }}';
            const initEnd = '{{ $end->format("Y-m-d") }}';
            let table = null;

            function buildHeadersAndColumns(start, end) {
                const $thead = $('#dynamicTableHead').empty();
                const $tr = $('<tr></tr>');

                const columns = [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'position', name: 'position' },
                    { data: 'paid', name: 'paid', render: data => `<span class="stat-val text-paid">${data}</span>` },
                    { data: 'unpaid', name: 'unpaid', render: data => `<span class="stat-val text-unpaid">${data}</span>` },
                    { data: 'allocated', name: 'allocated', render: data => `<span class="stat-val text-alloc">${data}</span>` },
                    { data: 'remaining', name: 'remaining', render: data => `<span class="stat-val text-rem">${data}</span>` }
                ];
                
                $tr.append('<th style="min-width:50px">Sr.#</th><th style="min-width:150px">Employee Name</th><th>Position</th><th>Paid</th><th>Unpaid</th><th>Allocated</th><th>Remaining</th>');

                let current = moment(start), last = moment(end);
                const dayIdx = [];
                while (current <= last) {
                    const label = current.format('DD MMM');
                    const day = current.format('ddd');
                    const colKey = current.format('YYYY_MM_DD');

                    $tr.append(`<th class="text-center" data-key="${colKey}">${label}<br><small class="text-muted fw-normal" style="font-size:0.65rem">${day}</small></th>`);

                    const idx = columns.length;
                    columns.push({
                        data: colKey,
                        name: colKey,
                        orderable: false,
                        searchable: false,
                        defaultContent: 'A',
                        className: 'day-cell'
                    });
                    dayIdx.push(idx);
                    current.add(1, 'day');
                }
                $thead.append($tr);

                for (const i of dayIdx) {
                    columns[i].render = function (data) {
                        if (!data) return '<span class="legend-badge badge-absent-deducted">Absent</span>';
                        if (typeof data === 'string' && data.includes('<span')) {
                            // If it's Late HTML, maybe parse it? But simple fallback:
                            if (data.toLowerCase().includes('late')) return '<span class="legend-badge badge-late">Late</span>';
                            if (data.toLowerCase().includes('half')) return '<span class="legend-badge badge-half">Half Day</span>';
                            return '<span class="legend-badge badge-present">Present</span>';
                        }
                        if (data === 'L') return '<span class="legend-badge badge-leave">Leave</span>';
                        if (data === 'O') return '<span class="legend-badge badge-off">OFF</span>';
                        if (data === 'U') return '<span class="legend-badge badge-upcoming">Upcoming</span>';
                        if (data === 'A') return '<span class="legend-badge badge-absent-deducted">Absent</span>';
                        
                        return '<span class="legend-badge badge-absent-deducted">Absent</span>';
                    };
                }
                return columns;
            }

            function initializeTable(start, end) {
                const $table = $('#attendanceTable');
                if ($.fn.DataTable.isDataTable('#attendanceTable')) $table.DataTable().clear().destroy();
                
                const columns = buildHeadersAndColumns(start, end);
                $('#tableOverlay').removeClass('d-none');

                table = $table.DataTable({
                    processing: false,
                    serverSide: true,
                    dom: 'tr',
                    scrollX: true,
                    autoWidth: false,
                    ordering: false,
                    pageLength: 31,
                    ajax: {
                        url: "{{ route('employee.timesheet.data') }}",
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: d => { d.start = start; d.end = end; },
                        complete: () => {
                            $('#tableOverlay').addClass('d-none');
                            markHeaderClasses();
                        }
                    },
                    columns: columns
                });
            }

            function markHeaderClasses() {
                const today = moment().format('YYYY_MM_DD');
                $('table thead th').each(function() {
                    const key = $(this).data('key');
                    if(!key) return;
                    const day = moment(key, 'YYYY_MM_DD').format('ddd');
                    if(['Sat', 'Sun'].includes(day)) $(this).addClass('th-weekend');
                    if(key === today) $(this).addClass('th-today');
                });
            }

            $('#filterForm').on('submit', e => {
                e.preventDefault();
                const m = String($('#month').val()).padStart(2, '0');
                const y = $('#year').val();
                const start = moment(`${y}-${m}-01`).startOf('month').format('YYYY-MM-DD');
                const end = moment(`${y}-${m}-01`).endOf('month').format('YYYY-MM-DD');
                initializeTable(start, end);
            });

            // Navigation Actions
            $('#btnPrevMonth').click(() => moveMonth(-1));
            $('#btnNextMonth').click(() => moveMonth(1));
            $('#btnThisMonth').click(() => {
                const now = moment();
                $('#month').val(now.month() + 1);
                $('#year').val(now.year());
                $('#filterForm').submit();
            });

            function moveMonth(delta) {
                let m = parseInt($('#month').val());
                let y = parseInt($('#year').val());
                const cur = moment(`${y}-${m}-01`, "YYYY-MM-DD");
                const next = cur.add(delta, 'month');
                $('#month').val(next.month() + 1);
                $('#year').val(next.year());
                $('#filterForm').submit();
            }

            initializeTable(initStart, initEnd);
        });
    </script>
@endpush
