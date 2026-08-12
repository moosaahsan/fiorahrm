@extends('admin.layouts.app')

@section('title', 'Absence Intelligence Matrix')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="tw-page-title mb-1 text-2xl">Absence Intelligence Matrix</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">Governance</a></li>
                        <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1" aria-current="page">Leave Management</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                @can('approve-leave')
                <button class="btn btn-dark rounded-full px-4 fw-bold shadow-sm" data-toggle="modal" data-target="#bulkUpdateModal" style="border-radius: 20px;">
                    <i class="bi bi-collection-play"></i> Bulk Update
                </button>
                @endcan
                @can('create-leave')
                <button class="tw-btn-primary rounded-full px-6" data-toggle="modal" data-target="#addLeaveModal">
                    <i class="bi bi-plus-circle"></i> {{ auth()->user()->can('access-admin-panel') ? 'Manual Record' : 'Apply For Absence' }}
                </button>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- SaaS Analytics Row -->
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="tw-stat-card">
                <div class="icon-container !bg-amber-50 !text-amber-500">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="stat-label">Pending Deviation</div>
                    <div class="stat-value" id="stat-pending">{{ $summary['pending'] }}</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container">
                    <i class="bi bi-person-x"></i>
                </div>
                <div>
                    <div class="stat-label">Active Absence</div>
                    <div class="stat-value" id="stat-active">0</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container !bg-emerald-50 !text-emerald-500">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="stat-label">Approved Quota</div>
                    <div class="stat-value" id="stat-approved">{{ $summary['approved'] }}</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container !bg-slate-100 !text-slate-500">
                    <i class="bi bi-files"></i>
                </div>
                <div>
                    <div class="stat-label">Total Dataset</div>
                    <div class="stat-value" id="stat-total">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="tw-command-hub">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-3">
                    <label class="tw-filter-label">Personnel Identity</label>
                    <select id="filter-employee" class="tw-form-input">
                            <option value="">All Personnel</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                </div>
                <div class="md:col-span-2">
                    <label class="tw-filter-label">Policy Type</label>
                    <select id="filter-leave-type" class="tw-form-input">
                            <option value="">All Types</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->slug }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                </div>
                <div class="md:col-span-2">
                    <label class="tw-filter-label">Current Status</label>
                    <select id="filter-status" class="tw-form-input">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                </div>
                <div class="md:col-span-3">
                    <label class="tw-filter-label">Temporal Window (Date Range)</label>
                    <input type="text" id="filter-date-range" class="tw-form-input" placeholder="Select pattern...">
                </div>
                <div class="flex items-end md:col-span-2">
                    <button id="reset-filters" class="tw-btn-secondary h-12 w-full">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Hub
                    </button>
                </div>
            </div>
        </div>

        <div class="tw-directory-card overflow-hidden p-0">
            <div class="border-b border-slate-100 p-5">
                <h5 class="mb-0 font-display text-lg font-extrabold text-slate-900">Compliance Matrix</h5>
                <p class="mb-0 text-sm text-slate-500">Systemic audit of all absence records and approval signatures</p>
            </div>
            <div class="p-1">
                <div class="table-responsive">
                    <table class="table table-hover tw-admin-table w-100" id="leaves-table">
                        <thead>
                            <tr>
                                <th>Personnel Architecture</th>
                                <th>Departure Window</th>
                                <th>Policy Applied</th>
                                <th>Duration Net</th>
                                <th>Day Logic</th>
                                <th>Approval Signature</th>
                                <th>Live Status</th>
                                <th class="text-end">Command</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Apply For Absence Modal (Ultra-SaaS Design) -->
    <div class="modal fade" id="addLeaveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-bottom-0 p-4 pb-3" style="background: linear-gradient(to right, rgba(99, 102, 241, 0.05), transparent);">
                    <div>
                        <h5 class="modal-title fw-extrabold mb-1 d-flex align-items-center gap-2" style="font-family:'Outfit'; font-size: 1.4rem; color: #1e293b;">
                            <i class="bi bi-calendar2-minus text-primary"></i> 
                            {{ auth()->user()->hasRole('admin') ? 'Manual Absence Record' : 'Apply For Absence' }}
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Submit your formal deviation request for managerial approval.</p>
                    </div>
                    <button type="button" class="close shadow-none p-0 bg-transparent border-0" data-dismiss="modal" aria-label="Close" style="opacity: 0.4; font-size: 1.8rem; outline: none; display: flex;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addLeaveForm" class="text-start">
                    @csrf
                    <div class="modal-body p-4 pt-3">
                        @if(auth()->user()->can('access-admin-panel'))
                            <div class="form-group mb-4">
                                <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-person-badge mr-2"></i> Personnel Identity</label>
                                <select name="employee_id" class="saas-input" style="border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; height: 54px; font-weight: 500; text-align: left;" required>
                                    <option value="">Select Personnel...</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="employee_id" value="{{ auth()->user()->employee?->id }}">
                        @endif
                        
                        <div class="form-group mb-4">
                            <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-shield-check mr-2"></i> Policy Type</label>
                            <select name="leave_type" class="saas-input" style="border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; height: 54px; font-weight: 500; text-align: left;" required>
                                <option value="">Select Policy...</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->slug }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-6 pr-2">
                                <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-calendar-check mr-2"></i> Start window</label>
                                <input type="date" name="start_date" id="apply-start-date" class="saas-input" style="border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; height: 54px; font-weight: 500; padding: 10px 15px; text-align: left;" required>
                            </div>
                            <div class="col-6 pl-2">
                                <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-calendar-x mr-2"></i> End window</label>
                                <input type="date" name="end_date" id="apply-end-date" class="saas-input" style="border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; height: 54px; font-weight: 500; padding: 10px 15px; text-align: left;" required>
                            </div>
                            <div class="col-12 text-start" id="leave-days-calc"></div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-clock-history mr-2"></i> Duration Logic</label>
                            <select name="day_type" class="saas-input" style="border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; height: 54px; font-weight: 500; text-align: left;" required>
                                <option value="full_day">Standard (Full Day)</option>
                                <option value="first_half">First Half (Pre-Lunch Break)</option>
                                <option value="second_half">Second Half (Post-Lunch Break)</option>
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-chat-left-text mr-2"></i> Context / Rationale</label>
                            <textarea name="reason" class="saas-input" rows="5" style="border-radius: 16px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; padding: 16px; font-weight: 500; resize: vertical; min-height: 120px; text-align: left;" placeholder="Provide necessary context for your supervisor's review..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 py-4 d-flex justify-content-between align-items-center" style="border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; background: #f8fafc !important;">
                        <button type="button" class="btn fw-bold px-4" data-dismiss="modal" style="color: #64748b; font-size: 0.95rem; background: #e2e8f0; border-radius: 12px; height: 48px;">Dismiss Form</button>
                        <button type="submit" class="btn fw-bold px-5 shadow-sm d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #ffffff; border:none; border-radius: 12px; height: 48px; font-size: 0.95rem;">
                            <i class="bi bi-send-fill mr-2" style="transform: rotate(45deg); margin-top: -3px;"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="leaveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div id="leaveModalContent"></div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-bottom-0 p-4 pb-3" style="background: linear-gradient(to right, rgba(15, 23, 42, 0.05), transparent);">
                    <div>
                        <h5 class="modal-title fw-extrabold mb-1 d-flex align-items-center gap-2" style="font-family:'Outfit'; font-size: 1.4rem; color: #0f172a;">
                            <i class="bi bi-collection-play text-dark"></i> 
                            Bulk Attendance Update
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Update multiple pending leaves into attendance records at once.</p>
                    </div>
                    <button type="button" class="close shadow-none p-0 bg-transparent border-0" data-dismiss="modal" style="opacity: 0.4; font-size: 1.8rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="bulkUpdateForm" class="text-start">
                    @csrf
                    <div class="modal-body p-4 pt-3">
                        <div class="form-group mb-4">
                            <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-people mr-2"></i> Select Personnel (Multiple)</label>
                            <select name="employee_ids[]" id="bulk-employee-ids" class="saas-input w-100" style="border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; color: #1e293b; font-weight: 500; text-align: left; padding: 10px;" multiple required>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-6 pr-2">
                                <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-calendar-check mr-2"></i> From Date</label>
                                <input type="date" name="start_date" class="saas-input" style="border-radius: 12px; border: 1.5px solid #e2e8f0; height: 54px; padding: 10px 15px;" required>
                            </div>
                            <div class="col-6 pl-2">
                                <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-calendar-x mr-2"></i> To Date</label>
                                <input type="date" name="end_date" class="saas-input" style="border-radius: 12px; border: 1.5px solid #e2e8f0; height: 54px; padding: 10px 15px;" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold text-uppercase mb-2 d-block text-start" style="font-size: 0.75rem; letter-spacing: 0.8px; color: #64748b;"><i class="bi bi-clock-history mr-2"></i> Attendance Status</label>
                            <select name="attendance_status" class="saas-input" style="border-radius: 12px; border: 1.5px solid #e2e8f0; height: 54px; font-weight: 500;" required>
                                <option value="Present">Present</option>
                                <option value="Late">Late</option>
                                <option value="Half Day (First Half)">Half Day (Worked First Half)</option>
                                <option value="Half Day (Second Half)">Half Day (Worked Second Half)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 py-4 d-flex justify-content-between" style="border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; background: #f8fafc !important;">
                        <button type="button" class="btn fw-bold px-4" data-dismiss="modal" style="color: #64748b; background: #e2e8f0; border-radius: 12px; height: 48px;">Cancel</button>
                        <button type="submit" class="btn btn-dark fw-bold px-5 shadow-sm d-flex align-items-center" style="border-radius: 12px; height: 48px;">
                            <i class="bi bi-lightning-charge-fill mr-2"></i> Process Bulk Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            // Manual Record Submission
            $('#addLeaveForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.ajax({
                    url: '{{ route("admin.leaves.store") }}',
                    method: 'POST',
                    data: formData,
                    success: res => {
                        if (res.success) {
                            toastr.success(res.message);
                            $('#addLeaveModal').modal('hide');
                            $('#addLeaveForm')[0].reset();
                            $('#leave-days-calc').empty();
                            table.draw(false);
                        }
                    },
                    error: err => {
                        toastr.error(err.responseJSON?.message || 'Validation failed. Check your data.');
                    }
                });
            });

            // Bulk Update Submission
            $('#bulkUpdateForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                const $btn = $(this).find('button[type="submit"]');
                const originalHtml = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...').prop('disabled', true);

                $.ajax({
                    url: '{{ route("admin.leaves.bulk_update") }}',
                    method: 'POST',
                    data: formData,
                    success: res => {
                        $btn.html(originalHtml).prop('disabled', false);
                        if (res.success) {
                            $('#bulkUpdateModal').modal('hide');
                            $('#bulkUpdateForm')[0].reset();
                            
                            Swal.fire({
                                title: 'Bulk Update Complete',
                                html: `
                                    <div class="text-left mt-3">
                                        <div class="alert alert-info border-0" style="background: rgba(59, 130, 246, 0.1); color: #1d4ed8; border-radius: 12px;">
                                            <strong>${res.summary.found}</strong> Pending leaves found in range.
                                        </div>
                                        <div class="alert alert-success border-0" style="background: rgba(34, 197, 94, 0.1); color: #15803d; border-radius: 12px;">
                                            <strong>${res.summary.processed}</strong> Leaves successfully updated to attendance.
                                        </div>
                                        ${res.summary.skipped > 0 ? `<div class="alert alert-warning border-0" style="background: rgba(245, 158, 11, 0.1); color: #b45309; border-radius: 12px;"><strong>${res.summary.skipped}</strong> Leaves skipped (e.g. all off-days).</div>` : ''}
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonColor: '#0f172a',
                                confirmButtonText: 'Acknowledge',
                                customClass: { confirmButton: 'btn btn-dark rounded-pill px-5' },
                                buttonsStyling: false
                            });

                            let currentScroll = $(window).scrollTop();
                            table.one('draw', function() { setTimeout(() => $(window).scrollTop(currentScroll), 10); });
                            table.draw(false);
                        }
                    },
                    error: err => {
                        $btn.html(originalHtml).prop('disabled', false);
                        toastr.error(err.responseJSON?.message || 'Bulk update failed.');
                    }
                });
            });

            // Dynamic Day Calculation
            $('#apply-start-date, #apply-end-date, select[name="employee_id"]').on('change', function() {
                const start = $('#apply-start-date').val();
                const end = $('#apply-end-date').val();
                let empId = $('select[name="employee_id"]').length ? $('select[name="employee_id"]').val() : $('input[name="employee_id"]').val();
                
                if (start && end) {
                    if (new Date(end) < new Date(start)) {
                        $('#leave-days-calc').html('<div class="text-danger small"><i class="bi bi-exclamation-triangle"></i> End Date cannot be before Start Date.</div>');
                        return;
                    }
                    $('#leave-days-calc').html('<div class="small text-muted italic"><i class="fas fa-spinner fa-spin"></i> Calculating working days...</div>');
                    $.get('{{ route("admin.leaves.calculate") }}', { start_date: start, end_date: end, employee_id: empId }, function(res) {
                        $('#leave-days-calc').html(`
                            <div class="alert alert-info py-2 px-3 small border-0 mb-0 d-flex align-items-center gap-2 mt-2" style="border-radius: 12px; background: rgba(59, 130, 246, 0.1); color: #1d4ed8;">
                                <i class="bi bi-info-circle fw-bold"></i> 
                                <span>Net Duration: <strong>${res.days} working days</strong> 
                                ${res.holidays > 0 ? `<br><span class="text-muted" style="font-size: 0.7rem;">(Excluded ${res.holidays} global/team off-days)</span>` : ''}</span>
                            </div>
                        `);
                    }).fail(function() {
                        $('#leave-days-calc').empty();
                    });
                } else {
                    $('#leave-days-calc').empty();
                }
            });

            // Initialize Date Range Picker
            $('#filter-date-range').daterangepicker({
                autoUpdateInput: false,
                locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
            });

            $('#filter-date-range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.draw();
            });

            const table = $('#leaves-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("admin.leaves.data") }}',
                    data: d => {
                        d.employee_id = $('#filter-employee').val();
                        d.leave_type = $('#filter-leave-type').val();
                        d.status = $('#filter-status').val();
                        d.date_range = $('#filter-date-range').val();
                    },
                    dataSrc: json => {
                        $('#stat-pending').text(json.summary.pending);
                        $('#stat-approved').text(json.summary.approved);
                        $('#stat-total').text(json.summary.total);
                        $('#stat-active').text(json.summary.active_today);
                        return json.data;
                    }
                },
                columns: [
                    { 
                        data: 'employee_name', 
                        name: 'employees.name',
                        render: (data, type, row) => {
                            const initials = (data || '??').split(' ').map(n => n[0]).join('').toUpperCase();
                            const avatar = row.profile_pic_url ? `<img src="${row.profile_pic_url}" class="emp-avatar">` : `<div class="emp-avatar">${initials}</div>`;
                            return `
                                <div class="emp-identity">
                                    ${avatar}
                                    <div class="emp-info">
                                        <span class="name">${data}</span>
                                        <span class="id-badge small text-muted fw-bold">AST-${row.employee_id}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'date_range', 
                        name: 'leaves.start_date',
                        render: d => `<span class="date-range-pill"><i class="bi bi-calendar-event"></i> ${d}</span>`
                    },
                    { data: 'leave_type_name', name: 'leaves.leave_type', className: 'fw-extrabold text-indigo small uppercase' },
                    { data: 'duration', name: 'leaves.start_date', className: 'fw-bold text-dark' },
                    { data: 'day_type_label', name: 'leaves.day_type', className: 'small fw-bold text-muted uppercase' },
                    { data: 'approved_by_name', name: 'leaves.approved_by', className: 'small fw-bold' },
                    { data: 'status_badge', name: 'leaves.status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Query patterns...",
                    lengthMenu: "Show _MENU_ records",
                }
            });

            $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px');

            // Filter Change Listeners
            $('#filter-employee, #filter-leave-type, #filter-status').on('change', () => table.draw());

            $('#reset-filters').on('click', () => {
                $('#filter-employee, #filter-leave-type, #filter-status, #filter-date-range').val('');
                table.draw();
            });

            // Approve Leave
            $(document).on('click', '.leave-approve', function() {
                const id = $(this).data('id');
                const isSingleDay = $(this).data('single') == '1';
                
                let attendanceHtml = '';
                if (isSingleDay) {
                    attendanceHtml = `
                        <div class="mt-4 text-start">
                            <label class="fw-bold small text-muted mb-2">Update Attendance <span class="fw-normal">(Optional)</span></label>
                            <select id="approve-attendance" class="form-control form-control-lg bg-light border-0 shadow-sm" style="border-radius: 12px; font-size: 0.95rem;">
                                <option value="">Keep Existing Attendance</option>
                                <option value="Present">Present</option>
                                <option value="Late">Late</option>
                                <option value="Half Day (First Half)">Half Day (Worked First Half)</option>
                                <option value="Half Day (Second Half)">Half Day (Worked Second Half)</option>
                            </select>
                        </div>
                    `;
                }

                Swal.fire({
                    html: `
                        <div class="text-center mb-4">
                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-green-50 text-success" style="width: 56px; height: 56px; border-radius: 50%; color: #22c55e; background-color: #dcfce7;">
                                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">Authorize Leave</h4>
                            <p class="text-muted small">Provide a mandatory reason for this authorization.</p>
                        </div>
                        <div class="text-start">
                            <label class="fw-bold small text-muted mb-2">Authorization Note <span class="text-danger">*</span></label>
                            <textarea id="approve-reason" class="form-control bg-light border-0 shadow-sm" rows="3" placeholder="Enter reason here..." style="border-radius: 12px; font-size: 0.95rem; resize: none;"></textarea>
                        </div>
                        <div class="text-start mt-4">
                            <div class="d-flex align-items-center justify-content-between bg-light p-3 shadow-sm border-0" style="border-radius: 12px;">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem;">Deduct Balance</h6>
                                    <small class="text-muted">Subtract from remaining allocation</small>
                                </div>
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="checkbox" class="custom-control-input" id="deduct-balance" checked>
                                    <label class="custom-control-label" for="deduct-balance"></label>
                                </div>
                            </div>
                        </div>
                        ${attendanceHtml}
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle mr-2"></i> Confirm Authority',
                    cancelButtonText: 'Dismiss',
                    customClass: { 
                        popup: 'rounded-4 shadow-lg border-0',
                        confirmButton: 'btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm', 
                        cancelButton: 'btn btn-light border rounded-pill px-4 py-2 fw-bold text-muted shadow-sm ml-2' 
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        const reason = document.getElementById('approve-reason').value;
                        if (!reason) {
                            Swal.showValidationMessage('Reason is required');
                            return false;
                        }
                        return { 
                            reason: reason, 
                            deduct: document.getElementById('deduct-balance').checked,
                            attendance: isSingleDay ? document.getElementById('approve-attendance').value : ''
                        };
                    }
                }).then(r => {
                    if (r.isConfirmed) {
                        $.post(`/admin/leaves/${id}/approve`, {
                            _token: '{{ csrf_token() }}',
                            deduct_balance: r.value.deduct ? 1 : 0,
                            reason: r.value.reason,
                            attendance_status: r.value.attendance
                        }, res => {
                            toastr.success(res.message || 'Leave Authorized');
                            let currentScroll = $(window).scrollTop();
                            table.one('draw', function() { setTimeout(() => $(window).scrollTop(currentScroll), 10); });
                            table.draw(false);
                        }).fail(err => {
                            toastr.error(err.responseJSON?.message || 'Failed to authorize.');
                        });
                    }
                });
            });

            // Edit Decision
            $(document).on('click', '.leave-edit-decision', function() {
                const id = $(this).data('id');
                const isSingleDay = $(this).data('single') == '1';
                const currentReason = $(this).data('reason') || '';
                const currentAttendance = $(this).data('attendance') || '';
                const currentDeduct = $(this).data('deduct') == '1';
                
                let attendanceHtml = '';
                if (isSingleDay) {
                    attendanceHtml = `
                        <div class="mt-4 text-start">
                            <label class="fw-bold small text-muted mb-2">Update Attendance <span class="fw-normal">(Optional)</span></label>
                            <select id="edit-attendance" class="form-control form-control-lg bg-light border-0 shadow-sm" style="border-radius: 12px; font-size: 0.95rem;">
                                <option value="" ${currentAttendance === '' ? 'selected' : ''}>Keep Existing Attendance</option>
                                <option value="Present" ${currentAttendance === 'Present' ? 'selected' : ''}>Present</option>
                                <option value="Late" ${currentAttendance === 'Late' ? 'selected' : ''}>Late</option>
                                <option value="Half Day (First Half)" ${currentAttendance === 'Half Day (First Half)' || currentAttendance === 'Half Day' ? 'selected' : ''}>Half Day (Worked First Half)</option>
                                <option value="Half Day (Second Half)" ${currentAttendance === 'Half Day (Second Half)' ? 'selected' : ''}>Half Day (Worked Second Half)</option>
                            </select>
                        </div>
                    `;
                }

                Swal.fire({
                    html: `
                        <div class="text-center mb-4">
                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-indigo-50 text-indigo" style="width: 56px; height: 56px; border-radius: 50%; color: #6366f1; background-color: #eef2ff;">
                                <i class="fas fa-edit" style="font-size: 1.5rem;"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">Edit Authorization</h4>
                            <p class="text-muted small">Update the reason or attendance options for this processed request.</p>
                        </div>
                        <div class="text-start">
                            <label class="fw-bold small text-muted mb-2">Authorization Note <span class="text-danger">*</span></label>
                            <textarea id="edit-reason" class="form-control bg-light border-0 shadow-sm" rows="3" placeholder="Enter detailed reason here..." style="border-radius: 12px; font-size: 0.95rem; resize: none;">${currentReason}</textarea>
                        </div>
                        <div class="text-start mt-4">
                            <div class="d-flex align-items-center justify-content-between bg-light p-3 shadow-sm border-0" style="border-radius: 12px;">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem;">Deduct Balance</h6>
                                    <small class="text-muted">Subtract from remaining allocation</small>
                                </div>
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="checkbox" class="custom-control-input" id="edit-deduct-balance" ${currentDeduct ? 'checked' : ''}>
                                    <label class="custom-control-label" for="edit-deduct-balance"></label>
                                </div>
                            </div>
                        </div>
                        ${attendanceHtml}
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle mr-2"></i> Update Decision',
                    cancelButtonText: 'Dismiss',
                    customClass: { 
                        popup: 'rounded-4 shadow-lg border-0',
                        confirmButton: 'btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm', 
                        cancelButton: 'btn btn-light border rounded-pill px-4 py-2 fw-bold text-muted shadow-sm ml-2' 
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        const reason = document.getElementById('edit-reason').value;
                        if (!reason) {
                            Swal.showValidationMessage('Reason is required');
                            return false;
                        }
                        return { 
                            reason: reason, 
                            deduct: document.getElementById('edit-deduct-balance').checked,
                            attendance: isSingleDay ? document.getElementById('edit-attendance').value : ''
                        };
                    }
                }).then(r => {
                    if (r.isConfirmed) {
                        $.post(`/admin/leaves/${id}/update-decision`, {
                            _token: '{{ csrf_token() }}',
                            deduct_balance: r.value.deduct ? 1 : 0,
                            reason: r.value.reason,
                            attendance_status: r.value.attendance
                        }, res => {
                            toastr.success(res.message || 'Decision Updated');
                            let currentScroll = $(window).scrollTop();
                            table.one('draw', function() { setTimeout(() => $(window).scrollTop(currentScroll), 10); });
                            table.draw(false);
                        }).fail(err => {
                            toastr.error(err.responseJSON?.message || 'Failed to update decision.');
                        });
                    }
                });
            });

            // Reject Leave
            $(document).on('click', '.leave-reject', function() {
                const id = $(this).data('id');
                Swal.fire({
                    html: `
                        <div class="text-center mb-4">
                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-red-50 text-danger" style="width: 56px; height: 56px; border-radius: 50%; color: #ef4444; background-color: #fee2e2;">
                                <i class="fas fa-times-circle" style="font-size: 1.5rem;"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">Deny Request</h4>
                            <p class="text-muted small">Provide a mandatory reason for this denial.</p>
                        </div>
                        <div class="text-start">
                            <label class="fw-bold small text-muted mb-2">Rejection Note <span class="text-danger">*</span></label>
                            <textarea id="reject-reason" class="form-control bg-light border-0 shadow-sm" rows="3" placeholder="Enter rejection reason here..." style="border-radius: 12px; font-size: 0.95rem; resize: none;"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-times-circle mr-2"></i> Confirm Denial',
                    cancelButtonText: 'Dismiss',
                    customClass: { 
                        popup: 'rounded-4 shadow-lg border-0',
                        confirmButton: 'btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm', 
                        cancelButton: 'btn btn-light border rounded-pill px-4 py-2 fw-bold text-muted shadow-sm ml-2' 
                    },
                    buttonsStyling: false,
                    preConfirm: () => {
                        const reason = document.getElementById('reject-reason').value;
                        if (!reason) {
                            Swal.showValidationMessage('Reason is required for denial');
                            return false;
                        }
                        return reason;
                    }
                }).then(r => {
                    if (r.isConfirmed) {
                        $.post(`/admin/leaves/${id}/reject`, {
                            _token: '{{ csrf_token() }}',
                            reason: r.value
                        }, res => {
                            toastr.error(res.message || 'Request Denied');
                            let currentScroll = $(window).scrollTop();
                            table.one('draw', function() { setTimeout(() => $(window).scrollTop(currentScroll), 10); });
                            table.draw(false);
                        }).fail(err => {
                            toastr.error(err.responseJSON?.message || 'Failed to deny.');
                        });
                    }
                });
            });

            // Show Leave Details (Modal)
            $(document).on('click', '.leave-show', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i>');

                $.get(`/admin/leaves/${id}`, res => {
                    $btn.html(originalHtml);
                    const initials = (res.employee_name || '??').split(' ').map(n => n[0]).join('').toUpperCase();
                    
                    let html = `
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                            <!-- Header Area -->
                            <div class="position-relative p-4 border-bottom" style="background-color: #f8fafc; border-color: #f1f5f9;">
                                <button type="button" class="close position-absolute" data-dismiss="modal" aria-label="Close" style="top: 20px; right: 24px; color: #64748b; text-shadow: none; opacity: 1; outline: none;">
                                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                                </button>
                                
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; border-radius: 16px; background: #eef2ff; color: #6366f1; font-size: 1.5rem; font-weight: 700; border: 1px solid #e0e7ff; margin-right: 16px;">
                                        ${initials}
                                    </div>
                                    <div>
                                        <h4 class="mb-1 fw-bold text-dark" style="font-size: 1.25rem;">${res.employee_name}</h4>
                                        <div class="d-flex align-items-center">
                                            <span class="badge" style="background: rgba(99, 102, 241, 0.1); color: #4f46e5; border-radius: 6px; padding: 4px 8px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.05em;">AST-${res.id}</span>
                                            <span class="text-muted small ms-2 fw-medium" style="font-size: 0.8rem;">Personnel Record</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Body Area -->
                            <div class="modal-body p-4 p-md-5">
                                <!-- Top Cards Row -->
                                <div class="row g-3 mb-4 pb-2">
                                    <div class="col-md-4">
                                        <div class="p-3 rounded-3 border" style="background: #ffffff; border-color: #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                            <div class="text-uppercase fw-bold mb-1" style="color: #64748b; font-size: 0.65rem; letter-spacing: 0.05em;">Departure Type</div>
                                            <div class="fw-bold" style="color: #4f46e5; font-size: 1.1rem;">${res.leave_type_name}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded-3 border" style="background: #ffffff; border-color: #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                            <div class="text-uppercase fw-bold mb-1" style="color: #64748b; font-size: 0.65rem; letter-spacing: 0.05em;">Net Duration</div>
                                            <div class="fw-bold text-dark" style="font-size: 1.1rem;">${res.duration}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded-3 border" style="background: #ffffff; border-color: #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                            <div class="text-uppercase fw-bold mb-1" style="color: #64748b; font-size: 0.65rem; letter-spacing: 0.05em;">Shift Meta</div>
                                            <div class="fw-bold text-secondary" style="font-size: 1.1rem;">${res.shift_name || 'Default Context'}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Temporal Window Block -->
                                <div class="mb-4 pb-2">
                                    <label class="text-uppercase fw-bold d-block mb-2" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.05em;">Temporal Window (Scheduled Absence)</label>
                                    <div class="d-flex align-items-center p-3 rounded-3 border" style="background: #f8fafc; border-color: #f1f5f9;">
                                        <div class="d-flex align-items-center justify-content-center bg-white shadow-sm rounded-3 me-3" style="width: 48px; height: 48px; color: #4f46e5; border: 1px solid #e2e8f0;">
                                            <i class="far fa-calendar-alt" style="font-size: 1.25rem;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 1.1rem;">${res.date_range}</div>
                                            <div class="text-muted small">${res.day_type} Configuration</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Strategic Rationale -->
                                <div class="mb-4 pb-2">
                                    <label class="text-uppercase fw-bold d-block mb-2" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.05em;">Strategic Rationale / Narrative</label>
                                    <div class="p-3 rounded-3 border" style="background: #ffffff; border-color: #e2e8f0;">
                                        <p class="mb-0 text-dark" style="font-size: 0.95rem; line-height: 1.6; font-style: italic;">
                                            "${res.reason || 'No specific narrative provided for this absence deviation.'}"
                                        </p>
                                    </div>
                                </div>

                                <!-- Multi-Tier Approval Timeline -->
                                <div>
                                    <label class="text-uppercase fw-bold d-block mb-3" style="color: #94a3b8; font-size: 0.7rem; letter-spacing: 0.05em;">Approval Timeline</label>
                                    <div class="ps-3" style="border-left: 2px solid #e2e8f0; margin-left: 12px;">
                                        ${res.approvals && res.approvals.length > 0 ? res.approvals.map(a => `
                                            <div class="position-relative mb-4">
                                                <div class="position-absolute" style="left: -25px; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: ${a.action === 'Approved' ? '#22c55e' : '#ef4444'}; border: 3px solid #ffffff; box-shadow: 0 0 0 1px ${a.action === 'Approved' ? '#22c55e' : '#ef4444'};"></div>
                                                <div class="fw-bold text-dark d-flex align-items-center mb-1" style="font-size: 0.95rem;">
                                                    ${a.action} by ${a.approver_name} 
                                                    <span class="badge ms-2 ${a.stage === 'Lead' ? 'bg-indigo' : 'bg-dark'}" style="font-size: 0.65rem; padding: 3px 6px; border-radius: 4px;">${a.stage} Stage</span>
                                                </div>
                                                <div class="small text-muted mb-2"><i class="far fa-clock me-1"></i> ${a.date}</div>
                                                <div class="p-3 rounded-3 small" style="background: #f8fafc; border: 1px solid #f1f5f9; color: #475569; font-style: italic; font-size: 0.9rem;">
                                                    " ${a.reason} "
                                                </div>
                                            </div>
                                        `).join('') : '<div class="p-3 rounded-3 small border" style="background: #f8fafc; border-color: #e2e8f0; border-style: dashed; color: #64748b; font-style: italic;">Workflow has not been processed yet. Processing will start once a Lead or Admin acts on it.</div>'}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer Area -->
                            <div class="p-4" style="background-color: #ffffff; border-top: 1px solid #f1f5f9;">
                                <button type="button" class="btn btn-dark w-100 py-3 fw-bold shadow-sm" style="border-radius: 12px; font-size: 1rem; transition: background-color 0.2s;" data-dismiss="modal">
                                    Acknowledge & Close
                                </button>
                            </div>
                        </div>
                    `;
                    $('#leaveModalContent').html(html);
                    $('#leaveModal').modal('show');
                });
            });
        });
    </script>
@endpush