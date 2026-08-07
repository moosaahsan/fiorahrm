@extends('admin.layouts.app')

@section('title', 'Interview Management')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title interviews-header">Interview Intelligence Directory</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Interviews</li>
    </ol>
</div>
@endsection

@section('button')
    @can('create-interview')
        <a class="btn-premium-interview" href="{{ route('admin.interviews.create') }}">
            <i class="fas fa-user-tie mr-2"></i> Record Walk-in Interview
        </a>
    @endcan
@endsection

@section('content')
<div class="container-fluid">
    @include('includes.flash')

    <div class="interview-directory-card">
        <div class="interview-filters-panel mb-5">
            <div class="filter-group-label">Directory filters</div>
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="tw-filter-label" for="filter-date-range">Date range <span class="text-rose-500">*</span></label>
                    <input type="text" id="filter-date-range" class="saas-filter-input" placeholder="Select date range" readonly required>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="tw-filter-label" for="filter-date-field">Date basis</label>
                    <select id="filter-date-field" class="saas-filter-input">
                        <option value="created_at" selected>Recorded date</option>
                        <option value="interview_date">Interview date</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="tw-filter-label" for="filter-interview-type">Interview type</label>
                    <select id="filter-interview-type" class="saas-filter-input">
                        <option value="">All types</option>
                        @foreach ($interviewTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="tw-filter-label" for="filter-category">Category</label>
                    <select id="filter-category" class="saas-filter-input">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="tw-filter-label" for="filter-created-by">Created by</label>
                    <select id="filter-created-by" class="saas-filter-input">
                        <option value="">All creators</option>
                        @foreach ($creators as $creator)
                            <option value="{{ $creator->id }}">{{ $creator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="tw-filter-label" for="filter-source">Source</label>
                    <select id="filter-source" class="saas-filter-input">
                        <option value="">All sources</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source }}">{{ $source }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-9 col-md-6">
                    <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-lg-end">
                        <button type="button" id="apply-interview-filters" class="btn-saas-generate">
                            <i class="fas fa-filter mr-1"></i> Apply filters
                        </button>
                        <button type="button" id="reset-interview-filters" class="btn-saas-reset" title="Reset filters">
                            <i class="fas fa-undo-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <p class="interview-filters-hint mb-0 mt-3">
                Date range is required. Default: last 30 days. Switch “Date basis” to filter by scheduled interview date instead of when the record was created.
            </p>
        </div>

        <div class="mb-5 flex flex-col items-start justify-between gap-3 border-b border-slate-100 pb-4 xl:flex-row xl:items-center">
            <div class="pipeline-scroll mb-2 w-full overflow-x-auto xl:mb-0">
                <ul class="nav nav-pills flex-nowrap" id="pipeline-tabs" role="tablist" style="width: max-content; padding-bottom: 5px; margin: 0;">
                    <li class="nav-item">
                        <a class="nav-link active d-flex align-items-center" data-status="applied">
                            <i class="fas fa-file-alt mr-2"></i> Applications
                            <span class="tw-badge-muted ml-2">{{ $appliedCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-status="scheduled">
                            <i class="far fa-calendar-alt mr-2"></i> Scheduled
                            <span class="tw-badge-muted ml-2">{{ $scheduledCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-status="in_progress">
                            <i class="fas fa-spinner mr-2"></i> In Progress
                            <span class="tw-badge-muted ml-2">{{ $inProgressCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-status="training">
                            <i class="fas fa-graduation-cap mr-2"></i> Training
                            <span class="tw-badge-muted ml-2">{{ $trainingCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-status="on_hold">
                            <i class="fas fa-pause-circle mr-2"></i> On Hold
                            <span class="tw-badge-muted ml-2">{{ $onHoldCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-status="finalized">
                            <i class="fas fa-check-double mr-2"></i> Finalized
                            <span class="tw-badge-muted ml-2">{{ $finalizedCount }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div id="finalized-sub-filter-container" class="mt-2 w-full xl:mt-0 xl:w-auto" style="display: none;">
                <div class="flex w-full items-center justify-start gap-2 xl:justify-end">
                    <label for="finalized-sub-filter" class="mb-0 whitespace-nowrap text-sm font-bold text-slate-500">
                        <i class="fas fa-filter mr-1"></i> Filter Status:
                    </label>
                    <select id="finalized-sub-filter" class="tw-form-input w-full min-w-[160px] max-w-[250px] py-2 text-brand-700 xl:w-auto">
                        <option value="all">All Finalized</option>
                        <option value="Onboarded">Onboarded</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>
            
            <div id="training-team-lead-filter-container" class="mt-2 w-full xl:mt-0 xl:w-auto" style="display: none;">
                <div class="flex w-full items-center justify-start gap-2 xl:justify-end">
                    <label for="training-team-lead-filter" class="mb-0 whitespace-nowrap text-sm font-bold text-slate-500">
                        <i class="fas fa-user-tie mr-1"></i> Team Lead:
                    </label>
                    <select id="training-team-lead-filter" class="tw-form-input w-full min-w-[160px] max-w-[250px] py-2 text-brand-700 xl:w-auto">
                        <option value="all">All Team Leads</option>
                        @foreach($teamLeads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover tw-admin-table w-100" id="interviews-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Candidate Name</th>
                        <th>CNIC</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Position Applied</th>
                        <th>Type/Category</th>
                        <th>Handler / Creator</th>
                        <th>Interview Date</th>
                        <th>Assigned Team Lead</th>
                        <th>Action Suite</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Move / Reassign Candidate Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" role="dialog" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-2xl">
            <div class="modal-header bg-gradient-to-r from-brand-600 to-indigo-600 text-white rounded-t-2xl py-3 px-4">
                <h5 class="modal-title font-bold text-white text-base" id="reassignModalLabel">
                    <i class="fas fa-exchange-alt mr-2"></i> Move Candidate / Reassign Owner
                </h5>
                <button type="button" class="close text-white opacity-80 hover:opacity-100" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="reassignForm">
                <div class="modal-body p-4">
                    <input type="hidden" id="reassign_candidate_id">
                    <div class="mb-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Candidate Name</div>
                        <div class="font-extrabold text-slate-800 text-base" id="reassign_candidate_name">-</div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="reassign_user_id" class="font-bold text-slate-700 text-sm mb-2">
                            Select New Handler / Owner <span class="text-rose-500">*</span>
                        </label>
                        <select id="reassign_user_id" class="tw-form-input w-full rounded-xl" required>
                            <option value="">-- Choose User --</option>
                            @foreach($allUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle mr-1"></i> Moving this candidate updates the current handler while retaining the original creator record for history.
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-slate-50 rounded-b-2xl py-3 px-4">
                    <button type="button" class="btn btn-secondary rounded-xl font-bold px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-xl font-bold px-4 bg-brand-600 border-brand-600">
                        <i class="fas fa-check-circle mr-1"></i> Move Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab');
    var currentStatus = initialTab || 'applied';

    const defaultEnd = moment();
    const defaultStart = moment().subtract(29, 'days');

    function getDateRangeParts() {
        const raw = ($('#filter-date-range').val() || '').trim();
        if (!raw || !raw.includes(' - ')) {
            return null;
        }
        const parts = raw.split(' - ');
        if (parts.length !== 2 || !moment(parts[0], 'YYYY-MM-DD', true).isValid() || !moment(parts[1], 'YYYY-MM-DD', true).isValid()) {
            return null;
        }
        return { from: parts[0], to: parts[1] };
    }

    function ensureDateRange() {
        const range = getDateRangeParts();
        if (range) {
            return range;
        }
        const fallback = {
            from: defaultStart.format('YYYY-MM-DD'),
            to: defaultEnd.format('YYYY-MM-DD')
        };
        $('#filter-date-range').val(fallback.from + ' - ' + fallback.to);
        toastr.warning('Date range is required. Applied last 30 days.');
        return fallback;
    }

    $('#filter-date-range').daterangepicker({
        startDate: defaultStart,
        endDate: defaultEnd,
        autoUpdateInput: true,
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Clear'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#filter-date-range').val(defaultStart.format('YYYY-MM-DD') + ' - ' + defaultEnd.format('YYYY-MM-DD'));

    $('#filter-date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('#filter-date-range').on('cancel.daterangepicker', function(ev, picker) {
        // Keep required: reset to last 30 days instead of emptying
        picker.setStartDate(defaultStart);
        picker.setEndDate(defaultEnd);
        $(this).val(defaultStart.format('YYYY-MM-DD') + ' - ' + defaultEnd.format('YYYY-MM-DD'));
        toastr.info('Date range is required — reset to last 30 days.');
    });

    if (initialTab) {
        $(`#pipeline-tabs .nav-link`).removeClass('active');
        $(`#pipeline-tabs .nav-link[data-status="${initialTab}"]`).addClass('active');
    }

    if (currentStatus === 'finalized') {
        $('#finalized-sub-filter-container').show();
    }
    
    if (currentStatus === 'training') {
        $('#training-team-lead-filter-container').show();
    }

    var table = $('#interviews-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3"lf>rt<"d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mt-4 gap-3"ip>',
        ajax: {
            url: "{{ route('admin.interviews.data') }}",
            data: function (d) {
                const range = ensureDateRange();
                d.status_filter = currentStatus;
                d.finalized_sub_filter = $('#finalized-sub-filter').val();
                d.training_team_lead = $('#training-team-lead-filter').val();
                d.date_from = range.from;
                d.date_to = range.to;
                d.date_field = $('#filter-date-field').val() || 'created_at';
                d.interview_type = $('#filter-interview-type').val();
                d.category = $('#filter-category').val();
                d.created_by = $('#filter-created-by').val();
                d.source = $('#filter-source').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, render: d => `<span class="text-muted font-weight-bold">#${d}</span>`},
            {data: 'name', name: 'name', render: (d, t, r) => `<span class="font-weight-800 text-dark" style="font-size: 1rem">${d}</span>`},
            {data: 'cnic', name: 'cnic', render: d => `<span class="font-weight-600 text-slate-600">${d}</span>`},
            {data: 'phone', name: 'phone'},
            {
                data: 'status',
                name: 'status',
                render: (d, t, r) => {
                    let badgeClass = 'badge-status-pending';
                    if (d === 'Applied') badgeClass = 'badge-status-pending';
                    if (d === 'Scheduled') badgeClass = 'badge-status-round1';
                    if (d === 'In Progress') badgeClass = 'badge-status-round2';
                    if (d === 'Training') badgeClass = 'badge-status-training';
                    if (d === 'On Hold') badgeClass = 'badge-status-onhold';
                    if (d === 'Rejected') badgeClass = 'badge-status-rejected';
                    if (d === 'Onboarded' || d === 'Hired') badgeClass = 'badge-status-hired';
                    if (d === 'No Show') badgeClass = 'badge-status-noshow';

                    let output = `<span class="badge-saas ${badgeClass}">${d}</span>`;
                    
                    if (d === 'Rejected' && r.training_status === 'Left') {
                        output += ` <span class="badge badge-danger shadow-sm ml-1" style="font-size:0.7rem; vertical-align: middle;">Left</span>`;
                    }
                    if (d === 'Training' && r.training_status === 'On Floor') {
                        output += ` <span class="badge badge-info shadow-sm ml-1" style="font-size:0.7rem; vertical-align: middle;">On Floor</span>`;
                    }
                    if (d === 'Onboarded' && r.training_status === 'Appointed') {
                        output += ` <span class="badge badge-success shadow-sm ml-1" style="font-size:0.7rem; vertical-align: middle; background-color: #059669; border-color: #059669;">Appointed</span>`;
                    }
                    
                    return output;
                }
            },
            {data: 'job_position', name: 'job_position', render: d => `<span class="font-weight-700 text-info" style="font-size:0.85rem">${d}</span>`},
            {data: 'type', name: 'type'},
            {data: 'created_by_name', name: 'created_by_name', render: d => `<span class="small font-weight-700 text-indigo-600"><i class="fas fa-user-edit mr-1"></i> ${d}</span>`},
            {
                data: 'interview_date',
                name: 'interview_date',
                render: (d, t, r) => {
                    if (currentStatus === 'on_hold') {
                        return r.on_hold_date ? moment(r.on_hold_date).format('DD MMM, YYYY') : '<span class="text-muted italic small">Not Set</span>';
                    }
                    return d ? moment(d).format('DD MMM, YYYY') : '<span class="text-muted italic small">Not Scheduled</span>';
                }
            },
            {
                data: 'assigned_team_lead',
                name: 'assigned_team_lead',
                render: d => {
                    if (d === 'Not Assigned') {
                        return `<span class="badge badge-warning text-dark"><i class="fas fa-exclamation-circle mr-1"></i> Not Assigned</span>`;
                    }
                    if (d === '-') {
                        return `<span class="text-muted text-center d-block">-</span>`;
                    }
                    return `<span class="font-weight-700 text-teal-700"><i class="fas fa-user-tag mr-1"></i> ${d}</span>`;
                }
            },
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search Intelligence Directory...",
             lengthMenu: "Show _MENU_ Entries"
        }
    });

    $('.dataTables_filter input').addClass('saas-input');

    $('#apply-interview-filters').on('click', function() {
        ensureDateRange();
        table.ajax.reload();
    });

    $('#reset-interview-filters').on('click', function() {
        const picker = $('#filter-date-range').data('daterangepicker');
        if (picker) {
            picker.setStartDate(defaultStart);
            picker.setEndDate(defaultEnd);
        }
        $('#filter-date-range').val(defaultStart.format('YYYY-MM-DD') + ' - ' + defaultEnd.format('YYYY-MM-DD'));
        $('#filter-date-field').val('created_at');
        $('#filter-interview-type').val('');
        $('#filter-category').val('');
        $('#filter-created-by').val('');
        $('#filter-source').val('');
        table.ajax.reload();
    });

    $(document).on('click', '.delete-interview', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Purge Interview Record?',
            text: 'This operation is irreversible. All candidate intelligence will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Purge Record',
            cancelButtonText: 'Abort'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/interviews/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if(response.success) {
                            toastr.success('Record purged successfully.');
                            table.ajax.reload(null, false);
                        }
                    }
                });
            }
        });
    });

    $(document).on('click', '.reassign-candidate', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const assigned = $(this).data('assigned');

        $('#reassign_candidate_id').val(id);
        $('#reassign_candidate_name').text(name);
        $('#reassign_user_id').val(assigned || '');

        $('#reassignModal').modal('show');
    });

    $('#reassignForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#reassign_candidate_id').val();
        const assignedTo = $('#reassign_user_id').val();

        if (!assignedTo) {
            toastr.error('Please select a new handler/owner.');
            return;
        }

        $.ajax({
            url: `{{ url('/admin/interviews') }}/${id}/reassign`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                assigned_to: assignedTo
            },
            success: function(response) {
                if (response.success) {
                    $('#reassignModal').modal('hide');
                    toastr.success(response.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(response.message || 'Operation failed.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to move candidate.');
            }
        });
    });

    $('#pipeline-tabs .nav-link').click(function(e) {
        e.preventDefault();
        $('#pipeline-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');

        if (currentStatus === 'finalized') {
            $('#finalized-sub-filter-container').fadeIn();
        } else {
            $('#finalized-sub-filter-container').fadeOut();
        }
        
        if (currentStatus === 'training') {
            $('#training-team-lead-filter-container').fadeIn();
        } else {
            $('#training-team-lead-filter-container').fadeOut();
        }

        table.ajax.reload();
    });

    $('#finalized-sub-filter').change(function() {
        table.ajax.reload();
    });
    
    $('#training-team-lead-filter').change(function() {
        table.ajax.reload();
    });
});
</script>
@endpush
