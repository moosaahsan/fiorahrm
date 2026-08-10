@extends('admin.layouts.app')

@section('breadcrumb')
    
    <div class="col-sm-6">
        <h4 class="page-title text-left">Attendance Sheet</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Admin</a></li>
            <li class="breadcrumb-item active">Monthly Attendance Sheet</li>
        </ol>
    </div>
@endsection

@push('styles')
    <style>
        .attendance-monthly-page div.dataTables_wrapper div.dataTables_info,
        .attendance-monthly-page div.dataTables_wrapper div.dataTables_paginate {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .attendance-monthly-page div.dataTables_wrapper div.dataTables_paginate ul.pagination {
            display: flex !important;
        }
    </style>
@endpush

@section('content')
    <div class="card shadow-sm border-0 attendance-monthly-page">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">Monthly Attendance</h5>
                    <p class="text-muted mb-0">Presence, leave, weekends & upcoming days.</p>
                </div>

                {{-- Toolbar: month nav + search --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Month nav">
                        <button id="btnPrevMonth" type="button" class="btn btn-outline-secondary">
                            <i class="fa fa-chevron-left me-1"></i>Prev
                        </button>
                        <button id="btnThisMonth" type="button" class="btn btn-outline-secondary d-none d-md-inline-block">
                            This Month
                        </button>
                        <button id="btnNextMonth" type="button" class="btn btn-outline-secondary">
                            Next<i class="fa fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                    <div class="input-group input-group-sm ms-2" style="min-width: 240px;">
                        <span class="input-group-text bg-white"><i class="fa fa-search"></i></span>
                        <input id="globalSearch" type="text" class="form-control" placeholder="Search name or position">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Filters --}}
            <form id="filterForm" class="row g-3 mb-3 align-items-end">
                <div class="col-md-2">
                    <label for="employee_id" class="form-label fw-semibold">Employee</label>
                    <select class="form-control form-control-sm form-select form-select-sm shadow-none" name="employee_id"
                        id="employee_id">
                        <option value="">All Employees</option>
                        @foreach($activeEmployees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="left_employee_id" class="form-label fw-semibold">Left Employees</label>
                    <select class="form-control form-control-sm form-select form-select-sm shadow-none" name="left_employee_id"
                        id="left_employee_id">
                        <option value="">Select Employee</option>
                        @foreach($leftEmployees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="year" class="form-label fw-semibold">Year</label>
                    <select class="form-control form-control-sm form-select form-select-sm shadow-none" id="year"
                        name="year">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $end->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="month" class="form-label fw-semibold">Month</label>
                    <select class="form-control form-control-sm form-select form-select-sm shadow-none" id="month"
                        name="month">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $end->month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="shift" class="form-label fw-semibold">Shift</label>
                    <select class="form-control form-control-sm form-select form-select-sm shadow-none" name="shift"
                        id="shift">
                        <option value="">All Shifts</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->shift_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-saas-primary w-100">
                        Apply
                    </button>
                    <button type="button" id="resetFilter" class="btn btn-saas-outline w-100">
                        Reset
                    </button>
                </div>
            </form>

            {{-- Legend Section --}}
            <div class="z-status-legend d-flex flex-wrap align-items-center mb-4 p-3 rounded-3 shadow-sm border bg-light">
                <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: 0.1em; min-width: 120px;">View Legend:</div>
                <div class="d-flex flex-wrap" style="flex: 1; gap: 10px;">
                    <span class="z-badge z-badge-present">Present</span>
                    <span class="z-badge z-badge-late">Late</span>
                    <span class="z-badge z-badge-half">Half Day</span>
                    <span class="z-badge z-badge-holiday">Holiday</span>
                    <span class="z-badge z-badge-approved">Approved (On Leave)</span>
                    <span class="z-badge z-badge-pending">Pending</span>
                    <span class="z-badge z-badge-off">OFF</span>
                    <span class="z-badge z-badge-upcoming">Upcoming</span>
                    <span class="z-badge z-badge-absent-deducted">Absent (Deducted)</span>
                    <span class="z-badge z-badge-absent-unpaid">Absent (Unpaid)</span>
                </div>
            </div>

            <div class="position-relative">
                {{-- Loading overlay --}}
                <div id="tableOverlay" class="loading-overlay d-none">
                    <div class="spinner-border" role="status" aria-hidden="true"></div>
                </div>

                {{-- IMPORTANT: directory-table-container matches employees directory layout --}}
                <div class="directory-table-container">
                    <div class="table-responsive border rounded-3">
                        <table id="attendanceTable"
                            class="table table-striped table-hover table-sm align-middle mb-0 nowrap w-100">
                            <thead id="dynamicTableHead"></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            // Guard: DataTables loaded?
            if (typeof $.fn.DataTable !== 'function') {
                console.error('DataTables library not found. Check layout JS order.');
                return;
            }

            const initStart = '{{ $start->format("Y-m-d") }}';
            const initEnd = '{{ $end->format("Y-m-d") }}';
            let table = null;

            const $overlay = $('#tableOverlay');
            const $globalSearch = $('#globalSearch');

            function showOverlay() { $overlay.removeClass('d-none'); }
            function hideOverlay() { $overlay.addClass('d-none'); }

            function markHeaderClasses() {
                const todayStr = moment().format('YYYY_MM_DD');
                // Style both original and cloned header th
                $('table.dataTable thead th, div.dataTables_scrollHead thead th').each(function () {
                    const key = $(this).data('key'); // set when building headers
                    if (!key) return;
                    const dt = moment(key, 'YYYY_MM_DD');
                    if (!dt.isValid()) return;
                    if (['Sat', 'Sun'].includes(dt.format('ddd'))) $(this).addClass('th-weekend');
                    if (key === todayStr) $(this).addClass('th-today');
                });
            }

            function buildHeadersAndColumns(start, end) {
                const $thead = $('#dynamicTableHead').empty();
                const $tr = $('<tr></tr>');

                const columns = [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'sticky-col-sr text-center' },
                    { data: 'name', name: 'name', className: 'sticky-col-name fw-semibold' },
                    { data: 'position', name: 'position', className: 'text-muted small' },
                    { data: 'total_paid_leaves', name: 'total_paid_leaves', className: 'text-center fw-bold text-success' },
                    { data: 'total_lop', name: 'total_lop', className: 'text-center fw-bold text-danger' },
                    { data: 'total_allocated_leaves', name: 'total_allocated_leaves', className: 'text-center fw-bold text-secondary' },
                    { data: 'remaining_leaves', name: 'remaining_leaves', className: 'text-center fw-bold text-primary' },
                    { data: 'assigned_check_in', name: 'assigned_check_in', className: 'text-center fw-semibold text-muted' }
                ];
                $tr.append(
                    '<th class="sticky-col-sr text-center" style="width:60px; min-width:60px">Sr.#</th>' +
                    '<th class="sticky-col-name text-start" style="width:220px; min-width:220px">Employee Name</th>' +
                    '<th style="min-width:160px">Position</th>' +
                    '<th style="min-width:80px" class="text-center" title="Paid Leaves">Paid</th>' +
                    '<th style="min-width:80px" class="text-center" title="Unpaid LOP">Unpaid</th>' +
                    '<th style="min-width:100px" class="text-center">Allocated</th>' +
                    '<th style="min-width:100px" class="text-center">Remaining</th>' +
                    '<th style="min-width:120px" class="text-center">Assigned Check-in</th>'
                );

                let current = moment(start), last = moment(end);
                const dayIdx = [];
                while (current <= last) {
                    const label = current.format('DD MMM');
                    const day = current.format('ddd');
                    const colKey = current.format('YYYY_MM_DD'); // matches backend Y_m_d

                    const $dayTh = $(
                        `<th class="text-center" data-key="${colKey}">
                         ${label}<br><small class="text-muted">${day}</small>
                       </th>`
                    );
                    $tr.append($dayTh);

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

                // renderers (unchanged) — you can still use row.emp_status here
                for (const i of dayIdx) {
                    columns[i].render = function (data, type, row) {
                        if (data === 'EX' || data === '') return '';
                        if (!data) return '<span class="z-badge z-badge-absent-deducted"><i class="fa fa-times me-1"></i>Absent</span>';

                        // If server sent HTML (from helper), return as is
                        if (typeof data === 'string' && data.trim().startsWith('<')) {
                            return data;
                        }

                        // Code Fallbacks
                        if (data === 'AL') return '<span class="z-badge z-badge-approved">Approved (On Leave)</span>';
                        if (data === 'PL') return '<span class="z-badge z-badge-pending">Pending</span>';
                        if (data === 'H')  return '<span class="z-badge z-badge-holiday">Holiday</span>';
                        if (data === 'O')  return '<span class="z-badge z-badge-off">OFF</span>';
                        if (data === 'U')  return '<span class="z-badge z-badge-upcoming">Upcoming</span>';
                        
                        return '<span class="z-badge z-badge-absent-deducted">Absent</span>';
                    };
                }

                return columns;
            }


            function initializeTable(start, end) {
                try {
                    const $table = $('#attendanceTable');

                    if ($.fn.DataTable.isDataTable('#attendanceTable')) {
                        $table.DataTable().clear().destroy();
                    }
                    if ($table.find('thead').length === 0) $table.prepend('<thead id="dynamicTableHead"></thead>');
                    if ($table.find('tbody').length === 0) $table.append('<tbody></tbody>');
                    $table.find('thead,tbody').empty();

                    const columns = buildHeadersAndColumns(start, end);

                    showOverlay();

                    table = $table.DataTable({
                        processing: true,
                        serverSide: true,

                        dom: '<"directory-table-toolbar d-flex justify-content-between align-items-center mb-3"l>rt<"directory-table-footer d-flex justify-content-between align-items-center mt-3"ip>',
                        language: {
                            lengthMenu: "Display _MENU_",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            infoEmpty: "Showing 0 to 0 of 0 entries",
                            zeroRecords: "No matching records found",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next &raquo;",
                                previous: "&laquo; Prev"
                            }
                        },

                        // Performance
                        deferRender: true,
                        scrollX: false, // disabled internal scrollX to use z-table-wrapper sticky
                        autoWidth: false,
                        ordering: false,
                        searching: true,
                        stateSave: false,
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],

                        ajax: {
                            url: "{{ route('admin.attendance.monthly.data') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            cache: true,
                            data: function (d) {
                                d.date_start = start;
                                d.date_end = end;
                                d.employee_id = $('#employee_id').val() || $('#left_employee_id').val();
                                d.shift = $('#shift').val();
                                // DataTables passes search[value] automatically via table.search()
                            },
                            complete: function () {
                                hideOverlay();
                                markHeaderClasses();
                            },
                            error: function (xhr, error, thrown) {
                                console.error('DataTables AJAX error:', xhr.responseText, error, thrown);
                                hideOverlay();
                                alert('Failed to load attendance data. Please check the console for details.');
                            }
                        },

                        columns: columns,
                        drawCallback: function () {
                            if (table && table.columns && table.columns.adjust) table.columns.adjust();
                        }
                    });

                } catch (e) {
                    console.error('Error initializing DataTable:', e);
                    hideOverlay();
                    alert('Error initializing table. Please check the console for details.');
                }
            }

            $('#employee_id').on('change', function() {
                if ($(this).val()) {
                    $('#left_employee_id').val('');
                }
            });

            $('#left_employee_id').on('change', function() {
                if ($(this).val()) {
                    $('#employee_id').val('');
                }
            });

            function getStartEndFromYearMonth() {
                const year = parseInt($('#year').val(), 10);
                const month = parseInt($('#month').val(), 10);
                
                // Payroll cycle follows the calendar month: 1st to last day
                const startMoment = moment(new Date(year, month - 1, 1));
                const endMoment = moment(new Date(year, month - 1, 1)).endOf('month');

                const start = startMoment.format('YYYY-MM-DD');
                const end = endMoment.format('YYYY-MM-DD');
                return { start, end };
            }

            // Month nav
            function moveMonth(delta) {
                const curYear = parseInt($('#year').val(), 10);
                const curMonth = parseInt($('#month').val(), 10);
                const cur = moment(`${curYear}-${String(curMonth).padStart(2, '0')}-01`);
                const next = cur.add(delta, 'month');
                $('#year').val(next.year());
                $('#month').val(next.month() + 1);
                const { start, end } = getStartEndFromYearMonth();
                initializeTable(start, end);
            }

            initializeTable(initStart, initEnd);

            // Apply filter
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                const { start, end } = getStartEndFromYearMonth();
                initializeTable(start, end);
            });

            function setFiltersToPayrollMonth(m) {
                let payrollMonth = m.clone();
                if (payrollMonth.date() >= 21) {
                    payrollMonth.add(1, 'month');
                }
                $('#year').val(payrollMonth.year());
                $('#month').val(payrollMonth.month() + 1);
            }

            // Reset
            $('#resetFilter').on('click', function () {
                $('#employee_id').val('');
                $('#left_employee_id').val('');
                $('#shift').val('');
                setFiltersToPayrollMonth(moment());
                $('#globalSearch').val('');
                const { start, end } = getStartEndFromYearMonth();
                initializeTable(start, end);
            });

            // Month navigation
            $('#btnPrevMonth').on('click', () => moveMonth(-1));
            $('#btnNextMonth').on('click', () => moveMonth(1));
            $('#btnThisMonth').on('click', () => {
                setFiltersToPayrollMonth(moment());
                const { start, end } = getStartEndFromYearMonth();
                initializeTable(start, end);
            });

            // Debounced global search (name/position/id) – server-side
            let searchTimer = null;
            $('#globalSearch').on('input', function () {
                if (!table) return;
                clearTimeout(searchTimer);
                const q = this.value;
                searchTimer = setTimeout(() => {
                    table.search(q).draw();
                }, 350);
            });

            // Handle page length change
            $('#tableLength').on('change', function () {
                if (!table) return;
                table.page.len($(this).val()).draw();
            });
        });
    </script>
@endpush