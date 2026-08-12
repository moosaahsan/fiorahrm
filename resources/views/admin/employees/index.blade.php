@extends('admin.layouts.app')

@section('breadcrumb')
    <div class="col-sm-6 text-left">
        <h4 class="page-title directory-header">Workforce Center</h4>
        <ol class="breadcrumb saas-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Employee Directory</li>
        </ol>
    </div>
@endsection

@section('button')
    @can('create-employee')
        <button type="button" class="btn-premium-back mr-2" id="btnBulkImportEmployees">
            <i class="fas fa-file-upload"></i>
            <span>Bulk Import</span>
        </button>
        <a href="{{ route('admin.employees.create') }}" class="btn-premium-add">
            <i class="fas fa-plus-circle"></i>
            <span>Onboard New Talent</span>
        </a>
    @endcan
@endsection

@section('content')

    <div class="container-fluid workforce-directory-page">
        @include('includes.flash')
        @include('includes.ajax_modal')

        <!-- Bulk Import Employees Modal -->
        <div class="modal fade" id="bulkImportEmployeesModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                    <div id="bulkImportEmployeesModalContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted small text-uppercase fw-bold">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workforce status toggle -->
        <div class="workforce-segment mb-4" id="employeeTabs" role="tablist" aria-label="Workforce status">
            <button type="button" class="workforce-tab active" data-status="active" aria-pressed="true">
                <i class="fas fa-user-check" aria-hidden="true"></i>
                <span>Active Workforce</span>
            </button>
            <button type="button" class="workforce-tab workforce-tab--resigned" data-status="resigned" aria-pressed="false">
                <i class="fas fa-user-slash" aria-hidden="true"></i>
                <span>Resigned Talent</span>
            </button>
        </div>

        <!-- Advanced Filter Bar -->
        <div class="filter-card">
            <div class="row align-items-end filter-row">
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label"><i class="fas fa-clock"></i> Active Shift</label>
                    <select id="filter-shift" class="tw-form-input">
                        <option value="">All Company Shifts</option>
                        @foreach ($schedules as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->shift_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="filter-label"><i class="fas fa-building"></i> Office Branch</label>
                    <select id="filter-branch" class="tw-form-input">
                        <option value="">All Regions</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="filter-label"><i class="fas fa-calendar-alt"></i> Onboarded From</label>
                    <input type="date" id="filter-joining-from" class="tw-form-input">
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="filter-label"><i class="fas fa-calendar-check"></i> Onboarded To</label>
                    <input type="date" id="filter-joining-to" class="tw-form-input">
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="d-flex filter-actions">
                        <button class="btn btn-apply-filters flex-grow-1" id="filter-button">
                            <i class="fas fa-search mr-2"></i> Find Match
                        </button>
                        <button class="tw-action-btn px-3 ml-2" id="reset-filters" title="Reset Filters">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Directory Table -->
        <div class="directory-table-container">
            <div class="table-responsive">
                <table class="table table-hover tw-admin-table w-100" id="employees-table">
                    <thead>
                        <tr>
                            <th>Identity</th>
                            <th>Branch</th>
                            <th>Job Role</th>
                            <th>Connectivity</th>
                            <th>Team</th>
                            <th>Allocated Shift</th>
                            <th>Joining Date</th>
                            <th>Resigned Date</th>
                            <th>Actions</th>
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
            $('#btnBulkImportEmployees').on('click', function () {
                $('#bulkImportEmployeesModal').modal('show');
                $('#bulkImportEmployeesModalContent').load("{{ route('admin.employees.import.modal') }}");
            });

            // Destroy existing for clean init
            if ($.fn.DataTable.isDataTable('#employees-table')) { $('#employees-table').DataTable().destroy(); }

            const table = $('#employees-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                scrollX: true,
                autoWidth: false,
                dom: '<"directory-table-toolbar d-flex justify-content-between align-items-center"lf>rt<"directory-table-footer d-flex justify-content-between align-items-center"ip>',
                ajax: {
                    url: '{{ route("admin.employees.index") }}',
                    data: d => {
                        d.status = $('#employeeTabs .workforce-tab.active').data('status');
                        d.shift_id = $('#filter-shift').val();
                        d.branch_id = $('#filter-branch').val();
                        d.joining_from = $('#filter-joining-from').val();
                        d.joining_to = $('#filter-joining-to').val();
                    }
                },
                columns: [
                    {
                        data: 'name', name: 'name', render: (data, type, row) => {
                            return `
                            <div class="emp-identity">
                                <img src="${row.profile_pic_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data) + '&color=4f46e5&background=f0f0ff'}" class="emp-avatar border">
                                <div class="emp-info">
                                    <span class="emp-name">${data}</span>
                                    <span class="emp-code">EMP-${row.id}</span>
                                </div>
                            </div>
                        `;
                        }
                    },
                    { data: 'branch.name', name: 'branch.name', defaultContent: '<span class="text-muted small">N/A</span>' },
                    { data: 'position_status', name: 'position' },
                    { data: 'email', name: 'email', render: (data) => `<span class="workforce-email">${data}</span>` },
                    { data: 'team_dropdown', name: 'team_dropdown', orderable: false, searchable: false },
                    { data: 'shift_dropdown', name: 'shift_dropdown', orderable: false, searchable: false },
                    { data: 'joining_date', name: 'joining_date' },
                    { data: 'resign_date', name: 'resign_date', visible: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                columnDefs: [
                    { orderable: false, targets: [4, 5, 8] },
                    { className: 'workforce-col-identity', targets: 0 },
                    { className: 'workforce-col-team', targets: 4 },
                    { className: 'workforce-col-shift', targets: 5 },
                    { className: 'workforce-col-actions', targets: 8 },
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search talent...",
                    lengthMenu: "Display _MENU_",
                    processing: '<div class="spinner-border text-primary" role="status"></div>'
                },
                drawCallback: function () {
                    $('#employees-table .assign-shift, #employees-table .assign-team')
                        .addClass('workforce-inline-select');
                }
            });

            // Search Input Styling Post-init
            $('.dataTables_filter input').addClass('tw-form-input directory-search-input');

            // Modal Trigger Handler
            $(document).on('click', '.trigger_ajax_modal', function () {
                const id = $(this).data('id');
                const action = $(this).data('action');
                let route = '';

                switch (action) {
                    case 'view': route = "{{ route('ajax_modal_contents', 'view_employee') }}?id=" + id; break;
                    case 'edit': route = "{{ route('ajax_modal_contents', 'edit_employee') }}?id=" + id; break;
                    case 'manage_leaves': route = "{{ route('ajax_modal_contents', 'manage_leaves') }}?id=" + id; break;
                    case 'deactivate': route = "{{ route('ajax_modal_contents', 'deactive_employee') }}"; break;
                }

                $.ajax({
                    url: '/admin/employee_data/' + id,
                    type: "GET",
                    dataType: 'json',
                    beforeSend: function () {
                        $('#shift_update_ajax_modal .modal-dialog').html('<div class="modal-content saas-ultra" style="min-height: 400px; display: flex; align-items: center; justify-content: center;"><div class="text-center"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div><h6 class="mt-3 text-muted fw-bold">Syncing Data...</h6></div></div>');
                        $('#shift_update_ajax_modal').modal('show');
                    },
                    success: function (data) { ajax_modal(data, route); },
                    error: function () { toastr.error('Sync failed.'); }
                });
            });

            // Filter Logic
            $('#filter-button').on('click', () => table.draw());
            $('#reset-filters').on('click', () => { $('#filter-shift, #filter-branch, #filter-joining-from, #filter-joining-to').val(''); table.draw(); });

            // Assignment Handlers
            let prevVal = null;
            $(document)
                .on('focus', '.assign-shift', function () { prevVal = $(this).val(); })
                .on('change', '.assign-shift', function () {
                    const $dd = $(this);
                    const empId = $dd.data('emp');
                    const shiftId = $dd.val();
                    if (!shiftId) { $dd.val(prevVal); return; }

                    Swal.fire({
                        title: 'Update Work Schedule?',
                        text: 'Assigning a new shift will adjust attendance tracking for this talent.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        confirmButtonText: 'Confirm Update',
                        cancelButtonText: 'Discard'
                    }).then((result) => {
                        if (!result.isConfirmed) { $dd.val(prevVal); return; }
                        $dd.prop('disabled', true).css('opacity', '0.5');
                        $.post('{{ route("admin.employees.assignShift") }}', { _token: '{{ csrf_token() }}', emp_id: empId, shift_id: shiftId })
                            .done(() => { toastr.success('Schedule updated.'); table.ajax.reload(null, false); })
                            .fail(() => { toastr.error('Sync error.'); $dd.val(prevVal); })
                            .always(() => $dd.prop('disabled', false).css('opacity', '1'));
                    });
                });

            $(document).on('change', '.assign-team', function () {
                const $dd = $(this);
                const empId = $dd.data('emp');
                const teamId = $dd.val();
                $dd.prop('disabled', true).css('opacity', '0.5');
                $.post('{{ route("admin.employees.assignTeam") }}', { _token: '{{ csrf_token() }}', emp_id: empId, team_id: teamId })
                    .done(() => { toastr.success('Team reassigned.'); table.ajax.reload(null, false); })
                    .fail(() => toastr.error('Sync error.'))
                    .always(() => $dd.prop('disabled', false).css('opacity', '1'));
            });

            // Tab Switching Logic
            $('#employeeTabs .workforce-tab').on('click', function () {
                $('#employeeTabs .workforce-tab').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');

                const status = $(this).data('status');
                if (status === 'resigned') {
                    table.column(7).visible(true);
                    table.order([7, 'desc']);
                } else {
                    table.column(7).visible(false);
                    table.order([0, 'asc']);
                }
                setTimeout(() => {
                    table.draw();
                    table.columns.adjust();
                }, 100);
            });

            // Resign Handler
            $(document).on('click', '.resign-employee', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name');
                const today = new Date().toISOString().slice(0, 10);

                Swal.fire({
                    title: 'Offboard Employee',
                    html: `
                        <p class="mb-3">Are you sure you want to offboard <strong>${name}</strong>?</p>
                        <div class="form-group text-left mb-3">
                            <label class="font-weight-bold text-dark small d-block mb-1">Exit Type</label>
                            <select id="swal-exit-type" class="form-control w-100">
                                <option value="resigned">Resigned</option>
                                <option value="terminated">Terminated</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div class="form-group text-left mb-3" id="swal-served-notice-wrap">
                            <label class="font-weight-bold text-dark small d-block mb-1">Served Notice Period?</label>
                            <select id="swal-served-notice" class="form-control w-100">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div id="swal-suspended-dates-wrap" style="display:none;">
                            <div class="form-group text-left mb-3">
                                <label class="font-weight-bold text-dark small d-block mb-1">Suspension Start Date</label>
                                <input type="date" id="swal-suspended-start" class="form-control w-100" value="${today}">
                            </div>
                            <div class="form-group text-left mb-3">
                                <label class="font-weight-bold text-dark small d-block mb-1">Suspension End Date</label>
                                <input type="date" id="swal-suspended-end" class="form-control w-100">
                            </div>
                        </div>
                        <div class="form-group text-left">
                            <label id="swal-exit-reason-label" class="font-weight-bold text-dark small d-block mb-1">Reason / Notes (Optional)</label>
                            <textarea id="swal-exit-reason" class="form-control w-100" rows="3" placeholder="Type reason or notes here..."></textarea>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    confirmButtonText: 'Confirm Offboard',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        const toggleExitFields = () => {
                            const isSuspended = $('#swal-exit-type').val() === 'suspended';
                            $('#swal-served-notice-wrap').toggle(!isSuspended);
                            $('#swal-suspended-dates-wrap').toggle(isSuspended);
                            $('#swal-exit-reason-label').text(
                                isSuspended ? 'Reason (Required)' : 'Reason / Notes (Optional)'
                            );
                        };
                        $('#swal-exit-type').on('change', toggleExitFields);
                        toggleExitFields();
                    },
                    preConfirm: () => {
                        const exitType = $('#swal-exit-type').val();
                        const reason = $('#swal-exit-reason').val().trim();

                        if (exitType === 'suspended') {
                            const start = $('#swal-suspended-start').val();
                            const end = $('#swal-suspended-end').val();
                            if (!start || !end) {
                                Swal.showValidationMessage('Start and end dates are required for suspension.');
                                return false;
                            }
                            if (end < start) {
                                Swal.showValidationMessage('End date must be on or after start date.');
                                return false;
                            }
                            if (!reason) {
                                Swal.showValidationMessage('Reason is required for suspension.');
                                return false;
                            }
                            return {
                                exit_type: exitType,
                                resign_reason: reason,
                                suspended_start_date: start,
                                suspended_end_date: end,
                            };
                        }

                        return {
                            exit_type: exitType,
                            resign_reason: reason,
                            served_notice: $('#swal-served-notice').val()
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const data = result.value;
                        $.post(`/admin/employees/${id}/resign`, {
                            _token: '{{ csrf_token() }}',
                            ...data
                        })
                            .done(resp => {
                                toastr.warning(resp.message || 'Status updated.');
                                table.draw();
                            })
                            .fail(xhr => {
                                const msg = xhr.responseJSON?.message || xhr.responseJSON?.errors
                                    ? Object.values(xhr.responseJSON.errors || {}).flat().join(' ')
                                    : 'Action failed.';
                                toastr.error(msg);
                            });
                    }
                });
            });

            // Rejoin / Recall Handler
            $(document).on('click', '.rejoin-employee', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name');
                const oldJoiningRaw = $(this).data('joining'); // YYYY-MM-DD
                
                Swal.fire({
                    title: 'Recall Employee',
                    html: `
                        <p class="mb-3">Do you want to re-onboard <strong>${name}</strong>? This will clear their resignation record and move it to history.</p>
                        <div class="form-group text-left">
                            <label class="font-weight-bold text-dark small d-block mb-1">Set Re-joining Date</label>
                            <input type="date" id="swal-joining-date" class="form-control w-100" value="${oldJoiningRaw}">
                            <small class="text-muted">By default, their original joining date is selected.</small>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    confirmButtonText: 'Confirm & Reactivate',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        return { joining_date: $('#swal-joining-date').val() }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`/admin/employees/${id}/rejoin`, {
                            _token: '{{ csrf_token() }}',
                            joining_date: result.value.joining_date
                        })
                        .done(resp => {
                            if (resp.success) {
                                toastr.success(resp.message || 'Employee reactivated!');
                                table.draw();
                            } else {
                                toastr.error(resp.message || 'Action failed.');
                            }
                        })
                        .fail(() => toastr.error('Network error during re-onboarding.'));
                    }
                });
            });

            // Delete Handler
            $(document).on('click', '.delete-employee', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Permanently?',
                    text: `This will remove ${name} and all their history from the system. This action is irreversible!`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, Delete Permanent'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/employees/${id}`,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function () {
                                toastr.success('Employee deleted.');
                                table.draw();
                            },
                            error: function () { toastr.error('Delete failed.'); }
                        });
                    }
                });
            });
        });
    </script>
@endpush