@extends('employee.layouts.app')

@section('title', 'Attendance Logs')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="page-title mb-1" style="font-size: 1.85rem;">Attendance Logs</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">My Space</a></li>
                        <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1" aria-current="page">Attendance Records</li>
                    </ol>
                </nav>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted fw-bold uppercase">Live Updates</span>
                    <span class="badge live-status-active animate-pulse">ACTIVE</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('content')
<div class="employee-portal-page">
    <!-- Summary Section at the top -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-lg">
            <div class="stat-card-saas h-100">
                <div class="icon-container" style="background: rgba(99, 102, 241, 0.08); color: var(--primary-indigo);">
                    <i class="bi bi-folder-fill"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total Records</div>
                    <div class="stat-value" id="stat-total-logs">0</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card-saas h-100">
                <div class="icon-container" style="background: #f0fdf4; color: #16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">On Time</div>
                    <div class="stat-value" id="stat-on-time">0</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card-saas h-100">
                <div class="icon-container" style="background: #fef2f2; color: #dc2626;">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Late Arrivals</div>
                    <div class="stat-value" id="stat-late-arrivals">0</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="stat-card-saas h-100">
                <div class="icon-container" style="background: rgba(14, 165, 233, 0.08); color: #0ea5e9;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Average Hours</div>
                    <div class="stat-value" style="font-size: 1.45rem;" id="stat-avg-hours">-</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg">
            <div class="stat-card-saas h-100">
                <div class="icon-container" style="background: rgba(100, 116, 139, 0.08); color: #64748b;">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Today's Activity</div>
                    <div class="stat-value" style="font-size: 1.15rem; font-weight: 800;" id="stat-active-today">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="command-center">
        <div class="row align-items-end g-3">
            <div class="col-12 col-md-3">
                <label class="small fw-extrabold text-muted uppercase mb-2">Select Shift</label>
                <select id="filter-shift" class="saas-filter-input">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->shift_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="small fw-extrabold text-muted uppercase mb-2">Select Status</label>
                <select id="filter-status" class="saas-filter-input">
                    <option value="">All Statuses</option>
                    <option value="On Time">On Time</option>
                    <option value="Late">Late Arrival</option>
                    <option value="Half Day">Needs Review</option>
                    <option value="Absent">Absent</option>
                    <option value="Holiday">Holiday</option>
                    <option value="Off Day">Off Day</option>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="small fw-extrabold text-muted uppercase mb-2">Select Date Range</label>
                <input type="text" id="filter-date" class="saas-filter-input" placeholder="Click to select dates...">
            </div>

            <div class="col-12 col-md-3">
                <div class="d-flex gap-2 justify-content-end">
                    <button id="filter-today" class="btn-saas-action-main flex-grow-1" style="background: var(--slate-100); color: var(--slate-600) !important; border: 1.5px solid var(--slate-200); box-shadow: none;">
                        <i class="bi bi-calendar-event"></i> Today
                    </button>
                    <button id="filter-apply" class="btn-saas-action-main flex-grow-1">
                        <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                    <button id="reset-filters" class="btn-saas-reset" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="logs-table-card">
        <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-extrabold mb-0 text-dark" style="font-family: 'Outfit'; font-weight: 800;">Attendance Overview</h5>
                <p class="text-muted small mb-0">Track check-in, check-out, and total hours easily.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button id="refresh-btn" class="tw-btn-secondary text-sm" style="border-radius: 8px; font-size: 0.8rem; padding: 6px 10px;" title="Refresh List"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover w-100" id="attendance-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Check-in Time</th>
                            <th>Check-out Time</th>
                            <th>Total Time</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" role="dialog" aria-hidden="true" data-id="">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-0 bg-light p-4">
                    <h5 class="modal-title fw-extrabold text-dark"><i class="bi bi-info-circle-fill me-2 text-primary"></i>Attendance Details</h5>
                    <button type="button" class="close shadow-none p-0 bg-transparent border-0" data-dismiss="modal" aria-label="Close" style="opacity: 0.4; font-size: 1.8rem; outline: none; display: flex;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="attendance-details-content">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small uppercase fw-bold">Loading records...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Default date range: last 30 days
            const startInit = moment().subtract(29, 'days').format('YYYY-MM-DD');
            const endInit = moment().format('YYYY-MM-DD');
            $('#filter-date').val(startInit + ' - ' + endInit);

            // Initialize select inputs
            $('#filter-shift, #filter-status').select2({
                width: '100%'
            });

            /* ---------- DATATABLE SETUP ---------- */
            const table = $('#attendance-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('employee.attendance.logs.data') }}",
                    data: function (d) {
                        d.shift_id = $('#filter-shift').val();
                        d.status = $('#filter-status').val();
                        d.date_range = $('#filter-date').val();
                    },
                    dataSrc: function (json) {
                        // Smooth stats animation
                        const animateCount = (id, target) => {
                            const $el = $(id);
                            if ($el.length) {
                                $({ count: parseInt($el.text()) || 0 }).animate({ count: target }, {
                                    duration: 500,
                                    step: function () {
                                        $el.text(Math.ceil(this.count));
                                    },
                                    complete: function () {
                                        $el.text(this.count);
                                    }
                                });
                            }
                        };

                        animateCount('#stat-total-logs', json.total_logs || 0);
                        animateCount('#stat-on-time', json.count_on_time || 0);
                        animateCount('#stat-late-arrivals', json.count_late || 0);
                        
                        $('#stat-avg-hours').text(json.avg_work_hours || '-');
                        
                        const statusColors = {
                            'On Duty': 'text-success',
                            'On Break': 'text-warning animate-pulse',
                            'Checked Out': 'text-primary',
                            'Completed': 'text-secondary',
                            'Offline': 'text-muted'
                        };
                        const statusClass = statusColors[json.active_today] || 'text-dark';
                        $('#stat-active-today')
                            .attr('class', 'stat-value ' + statusClass)
                            .text(json.active_today || '-');

                        return json.data;
                    }
                },
                columns: [
                    {
                        data: 'shift_date',
                        name: 'shift_date',
                        render: d => d !== '-' ? moment(d).format('DD MMM, YYYY') : '-'
                    },
                    {
                        data: 'employee_name',
                        name: 'employee_name',
                        render: function (data, type, row) {
                            const initials = data.split(' ').map(n => n[0]).join('').toUpperCase();
                            const avatar = row.employee_avatar ? `<img src="${row.employee_avatar}" class="emp-avatar" alt="profile picture">` : `<div class="emp-avatar">${initials}</div>`;
                            return `
                                <div class="emp-identity">
                                    ${avatar}
                                    <div class="emp-info">
                                        <span class="name">${data}</span>
                                        <span class="id-badge">${row.employee_id}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'department_team',
                        name: 'department_team',
                        render: d => {
                            // Extract department portion if slash separates department and team
                            let parts = d.split('/');
                            return `<span class="fw-semibold text-secondary" style="font-size: 0.82rem;">${parts[0].trim()}</span>`;
                        }
                    },
                    {
                        data: 'check_in',
                        name: 'check_in',
                        render: d => d !== '-' ? moment(d, 'HH:mm:ss').format('hh:mm A') : '-'
                    },
                    {
                        data: 'check_out',
                        name: 'check_out',
                        render: (d, t, row) => {
                            if (!d || d === '-') return '-';
                            if (d.indexOf('<') !== -1) return d;
                            return moment(d, 'HH:mm:ss').format('hh:mm A');
                        }
                    },
                    { 
                        data: 'worked_hours', 
                        name: 'worked_hours',
                        render: d => {
                            if (d.includes('duration-hub')) return d;
                            return `
                                <div class="duration-hub">
                                    <div class="net-time">${d}</div>
                                    <div class="lbl-time">Total Time</div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'status_badge',
                        name: 'status_badge',
                        render: (d, type, row) => {
                            let badge = d.toLowerCase();
                            const todayStr = moment().format('YYYY-MM-DD');


                            
                            if (badge.includes('on time') || badge.includes('success')) {
                                return '<span class="saas-status-badge on-time"><i class="bi bi-check-circle-fill"></i> On Time</span>';
                            } else if (badge.includes('late') || badge.includes('danger')) {
                                return '<span class="saas-status-badge late"><i class="bi bi-clock-fill"></i> Late</span>';
                            } else if (badge.includes('absent') || badge.includes('secondary')) {
                                return '<span class="saas-status-badge absent"><i class="bi bi-x-circle-fill"></i> Absent</span>';
                            } else if (badge.includes('half day') || badge.includes('warning')) {
                                return '<span class="saas-status-badge needs-review"><i class="bi bi-exclamation-triangle-fill"></i> Needs Review</span>';
                            } else if (badge.includes('leave')) {
                                return '<span class="saas-status-badge leave"><i class="bi bi-airplane-fill"></i> Leave</span>';
                            } else if (badge.includes('holiday')) {
                                return '<span class="saas-status-badge holiday"><i class="bi bi-star-fill"></i> Holiday</span>';
                            } else if (badge.includes('off day')) {
                                return '<span class="saas-status-badge offday"><i class="bi bi-calendar-x-fill"></i> Off Day</span>';
                            }
                            return d;
                        }
                    },
                    { 
                        data: 'action', 
                        name: 'action', 
                        orderable: false, 
                        searchable: false, 
                        className: 'text-end',
                        render: function (data, type, row) {
                            if (row.id) {
                                return `
                                    <button class="btn-saas-action view-details" data-id="${row.id}" title="View details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                `;
                            }
                            return '-';
                        }
                    }
                ],
                order: [[0, 'desc']],
                createdRow: function (row, data, dataIndex) {
                    if (data.status_badge && data.status_badge.toLowerCase().includes('absent')) {
                        $(row).addClass('row-absent');
                    }
                },
                dom: '<"p-3 d-flex justify-content-between align-items-center"lf>rt<"p-3 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Showing _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "",
                    zeroRecords: "No attendance records found.",
                    emptyTable: "No attendance records found."
                },
                pageLength: 20
            });

            // Adjust search field styling
            $('.dataTables_filter input').addClass('saas-filter-input').css('width', '240px');

            // Click Today filter handler
            $('#filter-today').click(function () {
                const today = moment().format('YYYY-MM-DD');
                $('#filter-date').val(today + ' - ' + today);
                table.draw();
            });

            // Run search filter options selection
            $('#filter-apply').click(function () {
                const $btn = $(this);
                $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Filter');
                table.draw();
                setTimeout(() => $btn.html('<i class="bi bi-funnel-fill"></i> Filter'), 400);
            });

            // Reset selection metrics in filter group
            $('#reset-filters').click(function () {
                $('#filter-shift').val('').trigger('change');
                $('#filter-status').val('').trigger('change');
                $('#filter-date').val(startInit + ' - ' + endInit);
                table.draw();
            });

            // Reload table contents list
            $('#refresh-btn').click(function () {
                table.ajax.reload(null, false);
            });

            // Initialize Date Picker input field
            $('#filter-date').daterangepicker({
                autoUpdateInput: false,
                locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
            }).on('apply.daterangepicker', function (e, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.draw();
            }).on('cancel.daterangepicker', function () {
                $(this).val('');
                table.draw();
            });

            /* ---------- VIEW DETAILS ACTION ---------- */
            $(document).on('click', '.view-details', function () {
                const id = $(this).data('id');
                const $content = $('#attendance-details-content');

                $content.html(`
                    <div class="modal-loader-wrapper">
                        <div class="saas-spinner"></div>
                        <div class="loader-text animate-pulse">Loading details...</div>
                    </div>
                `);

                $('#attendanceDetailsModal').attr('data-id', id).modal('show');

                $content.load(`/employee/attendance/details/${id}`, function (response, status, xhr) {
                    if (status === "error") {
                        $content.html(`
                            <div class="p-5 text-center">
                                <i class="bi bi-exclamation-octagon text-danger mb-3" style="font-size: 2.8rem;"></i>
                                <h5 class="fw-bold text-dark">Data Retrieval Failed</h5>
                                <p class="text-muted small">Could not load the attendance details. Please try again.</p>
                            </div>
                        `);
                    }
                });
            });

        });
    </script>
@endpush