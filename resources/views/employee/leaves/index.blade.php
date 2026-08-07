@extends('employee.layouts.app')

@section('title', 'Absence Intelligence Matrix')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="page-title mb-1" style="font-size: 2rem;">Absence Intelligence Matrix</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">Governance</a></li>
                        <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1" aria-current="page">Leave Management</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                @can('create-leave')
                <button class="btn btn-indigo d-flex align-items-center gap-2" data-toggle="modal" data-target="#addLeaveModal" style="border-radius: 50px; padding: 10px 24px; font-weight: 700; background: var(--primary-gradient); color: #fff; border:none;">
                    <i class="bi bi-plus-circle"></i> {{ auth()->user()->can('access-admin-panel') ? 'Manual Record' : 'Apply For Absence' }}
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection

@section('content')
<div class="employee-portal-page">
    <div class="container-fluid">
        <!-- SaaS Analytics Row -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Deviation</div>
                        <div class="stat-value" id="stat-pending">{{ $summary['pending'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                        <i class="bi bi-person-x"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Active Absence</div>
                        <div class="stat-value" id="stat-active">0</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Approved Quota</div>
                        <div class="stat-value" id="stat-approved">{{ $summary['approved'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(100, 116, 139, 0.1); color: #64748b;">
                        <i class="bi bi-files"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Dataset</div>
                        <div class="stat-value" id="stat-total">{{ $summary['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Tactical Command Hub (Filters) -->
        <div class="command-hub">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="saas-filter-group">
                        <label class="saas-filter-label">Policy Type</label>
                        <select id="filter-leave-type" class="saas-filter-input form-select">
                            <option value="">All Types</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->slug }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="saas-filter-group">
                        <label class="saas-filter-label">Current Status</label>
                        <select id="filter-status" class="saas-filter-input form-select">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="saas-filter-group">
                        <label class="saas-filter-label">Temporal Window (Date Range)</label>
                        <input type="text" id="filter-date-range" class="saas-filter-input" placeholder="Select pattern...">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 d-flex align-items-end">
                    <div class="saas-filter-group w-100">
                        <button id="reset-filters" class="tw-btn-secondary w-100" style="border-radius:12px; height: 48px; font-weight: 700;">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Hub
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Matrix Grid -->
        <div class="premium-table-card">
            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-family: 'Outfit'; font-weight: 800;">Compliance Matrix</h5>
                    <p class="text-muted small mb-0">Systemic audit of all absence records and approval signatures</p>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="w-100">
                    <table class="table table-hover dt-responsive nowrap w-100" id="leaves-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Departure Window</th>
                                <th>Policy Applied</th>
                                <th>Duration Net</th>
                                <th>Day Logic</th>
                                <th>Live Status</th>
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
                            <input type="hidden" name="employee_id" value="{{ auth()->user()->employee?->id }}">
                        
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
                        <button type="submit" class="btn fw-bold px-5 shadow-sm d-flex align-items-center justify-content-center" style="background: var(--primary-gradient); color: #ffffff; border:none; border-radius: 12px; height: 48px; font-size: 0.95rem;">
                            <i class="bi bi-send-fill mr-2" style="transform: rotate(45deg); margin-top: -3px;"></i> Submit Request
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
                    url: '{{ route("employee.leave.store") }}',
                    method: 'POST',
                    data: formData,
                    success: res => {
                        if (res.success) {
                            toastr.success(res.message);
                            $('#addLeaveModal').modal('hide');
                            $('#addLeaveForm')[0].reset();
                            $('#leave-days-calc').empty();
                            table.draw();
                        }
                    },
                    error: err => {
                        toastr.error(err.responseJSON?.message || 'Validation failed. Check your data.');
                    }
                });
            });

            // Dynamic Day Calculation
            $('#apply-start-date, #apply-end-date').on('change', function() {
                const start = $('#apply-start-date').val();
                const end = $('#apply-end-date').val();
                
                if (start && end) {
                    if (new Date(end) < new Date(start)) {
                        $('#leave-days-calc').html('<div class="text-danger small"><i class="bi bi-exclamation-triangle"></i> End Date cannot be before Start Date.</div>');
                        return;
                    }
                    $('#leave-days-calc').html('<div class="small text-muted italic"><i class="bi bi-check-circle"></i> Window looks good. Duration will be automatically verified upon submission.</div>');
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
                    url: '{{ route("employee.leave.data") }}',
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
                        data: 'start_date', 
                        name: 'leaves.start_date',
                        render: d => `<span class="date-range-pill"><i class="bi bi-calendar-event"></i> ${d}</span>`
                    },
                    { data: 'leave_type_name', name: 'leaves.leave_type', className: 'fw-extrabold text-indigo small uppercase' },
                    { data: 'duration', name: 'leaves.start_date', className: 'fw-bold text-dark' },
                    { data: 'day_type', name: 'leaves.day_type', className: 'small fw-bold text-muted uppercase' },
                    { data: 'status', name: 'leaves.status' }
                ],
                order: [[0, 'desc']],
                dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Query patterns...",
                    lengthMenu: "Show _MENU_ records",
                }
            });

            $('.dataTables_filter input').addClass('saas-filter-input').css('width', '250px');

            $('#filter-leave-type, #filter-status').on('change', () => table.draw());

            $('#reset-filters').on('click', () => {
                $('#filter-leave-type, #filter-status, #filter-date-range').val('');
                table.draw();
            });


        });
    </script>
@endpush