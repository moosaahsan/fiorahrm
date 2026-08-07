@extends('admin.layouts.app')

@section('title', 'Team Configuration')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Team Configuration</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Home</a></li>
        <li class="breadcrumb-item active text-slate-500">Workforce Groups</li>
    </ol>
</div>
@endsection

@section('button')
    @can('create-team')
        <button class="tw-btn-dark rounded-2xl px-7 py-3 shadow-lg shadow-slate-900/20" data-toggle="modal" data-target="#teamModal" id="btn-add-team">
            <i class="fas fa-users-cog"></i> Create New Team
        </button>
    @endcan
@endsection

@section('content')
<div class="container-fluid">
    @include('includes.flash')

    <div class="tw-directory-card">
        <div class="table-responsive">
            <table class="table table-hover w-100 tw-admin-table" id="teams-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Workforce Cluster</th>
                        <th>Branch</th>
                        <th>Personnel Lead</th>
                        <th>Department Cluster</th>
                        <th>Operational Context</th>
                        <th>Composition State</th>
                        <th>Action Suite</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="manageMembersModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content tw-modal-shell">
            <div class="tw-modal-header-dark flex items-start justify-between">
                <h5 class="tw-modal-title">
                    <i class="fas fa-layer-group mr-2 text-brand-400"></i>
                    Composition: <span id="managed-team-name" class="text-white opacity-90"></span>
                </h5>
                <button type="button" class="close text-3xl leading-none text-white opacity-60 hover:opacity-100" data-dismiss="modal">&times;</button>
            </div>

            <div class="border-b border-slate-100 bg-slate-50 p-4">
                <input type="text" class="tw-form-input shadow-none" id="memberSearch" placeholder="Find talent within workforce repository...">
            </div>

            <div class="tw-member-scroller" id="employeesList">
                <div class="p-8 text-center text-slate-500">
                    <div class="spinner-border text-brand-600 mb-3"></div>
                    <p class="font-bold">Syncing composition data...</p>
                </div>
            </div>

            <div class="tw-modal-footer flex items-center justify-between">
                <span class="tw-badge bg-brand-600 px-4 py-2 text-white shadow-sm">
                    Active Assignments: <span id="selected-count" class="font-bold">0</span>
                </span>
                <div class="flex gap-3">
                    <button type="button" class="tw-btn-secondary rounded-full px-6" data-dismiss="modal">Discard</button>
                    <button type="button" class="tw-btn-dark rounded-full px-6 py-2.5" id="save-members-btn">Commit Composition</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content tw-modal-shell">
            <div class="tw-modal-header-indigo flex items-start justify-between">
                <h5 class="tw-modal-title" id="teamModalLabel">Create Workforce Center</h5>
                <button type="button" class="close text-3xl leading-none text-white opacity-60 hover:opacity-100" data-dismiss="modal">&times;</button>
            </div>
            <form id="team-form">
                @csrf
                <input type="hidden" id="team_id" name="team_id">
                <div class="modal-body p-8">
                    <div class="mb-4">
                        <label class="tw-form-label uppercase tracking-wide text-brand-600">Identity Signature</label>
                        <input type="text" class="tw-form-input" id="name" name="name" required placeholder="e.g. Engineering Alpha">
                    </div>
                    <div class="mb-4">
                        <label class="tw-form-label uppercase tracking-wide text-brand-600">Team Leader / Supervisor</label>
                        <select class="tw-form-input" id="leader_id" name="leader_id">
                            <option value="">-- No Leader Assigned --</option>
                            @foreach($leads as $l)
                                <option value="{{ $l->id }}">{{ $l->name }}</option>
                            @endforeach
                        </select>
                        <small class="mt-1 block text-slate-500">This user will have data access to all employees assigned to this team.</small>
                    </div>
                    <div class="mb-4">
                        <label class="tw-form-label uppercase tracking-wide text-brand-600">Department Cluster</label>
                        <select class="tw-form-input" id="department_id" name="department_id" required>
                            <option value="">Select a Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="tw-form-label uppercase tracking-wide text-brand-600">Operational Scope</label>
                        <textarea class="tw-form-input" id="description" name="description" rows="4" placeholder="Briefly define the team's mission..."></textarea>
                    </div>
                </div>
                <div class="tw-modal-footer text-right">
                    <button type="button" class="tw-btn-secondary mr-3 rounded-full px-6" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="tw-btn-dark rounded-full px-10 py-2.5" id="save-team-btn">Finalize Team</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#teams-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-4"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>',
        ajax: "{{ route('admin.teams.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, render: d => `<span class="text-muted font-weight-bold">#${d}</span>`},
            {data: 'name', name: 'name', render: d => `<span class="font-weight-bold text-dark" style="font-size: 1.1rem">${d}</span>`},
            {data: 'branch.name', name: 'branch.name', defaultContent: '<span class="text-muted text-sm">N/A</span>'},
            {data: 'leader', name: 'leader'},
            {data: 'department', name: 'department'},
            {data: 'description', name: 'description', render: d => d ? `<span class="text-muted small font-weight-600">${d}</span>` : '<span class="text-light italic">No context defined.</span>'},
            {data: 'employees_count', name: 'employees_count', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search workforce clusters...",
             lengthMenu: "Show _MENU_"
        }
    });

    $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

    $('#btn-add-team').click(function() {
        $('#team-form')[0].reset();
        $('#team_id').val('');
        $('#leader_id').val('');
        $('#teamModalLabel').text('Create Workforce Center');
    });

    $('#team-form').submit(function(e) {
        e.preventDefault();
        let teamId = $('#team_id').val();
        let url = teamId ? `/admin/teams/${teamId}` : `{{ route('admin.teams.store') }}`;
        let method = teamId ? 'PUT' : 'POST';

        let $btn = $('#save-team-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message);
                    $('#teamModal').modal('hide');
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    Object.values(errors).forEach(err => toastr.error(err[0]));
                } else {
                    toastr.error('A system interruption occurred.');
                }
            },
            complete: function() { $btn.prop('disabled', false).html('Finalize Team'); }
        });
    });

    $(document).on('click', '.edit-team', function() {
        let id = $(this).data('id');
        $.get(`/admin/teams/${id}/edit`, function(data) {
            $('#team_id').val(data.id);
            $('#name').val(data.name);
            $('#leader_id').val(data.leader_id);
            $('#department_id').val(data.department_id);
            $('#description').val(data.description);
            $('#teamModalLabel').text('Edit Cluster Architecture');
            $('#teamModal').modal('show');
        });
    });

    $(document).on('click', '.delete-team', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Decommission Cluster?',
            text: 'This will unassign all talent and remove the workforce group permanentally.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Decommission',
            cancelButtonText: 'Abort'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/teams/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if(response.success) {
                            toastr.success('Group decommissioned successfully.');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function() {
                        toastr.error('Decommissioning failed due to an internal error.');
                    }
                });
            }
        });
    });

    $(document).on('click', '.manage-members', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#managed-team-name').text(name);
        $('#save-members-btn').data('id', id);
        $('#employeesList').html('<div class="p-8 text-center text-slate-500"><div class="spinner-border text-brand-600 mb-3"></div><p>Syncing talent architecture...</p></div>');
        $('#manageMembersModal').modal('show');

        $.get(`/admin/teams/${id}/members`, function(response) {
            let html = '';
            if(response.employees.length > 0) {
                response.employees.forEach(emp => {
                    let isChecked = (emp.team_id == id) ? 'checked' : '';
                    let activeClass = (emp.team_id == id) ? 'active' : '';
                    let badge = (emp.team_id == id) ? '<span class="tw-badge-success ml-2">Primary Contact</span>' : '';

                    html += `
                        <div class="member-identity-card ${activeClass}" onclick="$(this).find('input').click()">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input member-checkbox custom-check-premium" id="emp-${emp.id}" value="${emp.id}" ${isChecked} onclick="event.stopPropagation()">
                                <label class="custom-control-label" for="emp-${emp.id}"></label>
                            </div>
                            <div class="member-avatar">
                                ${emp.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="member-info">
                                <h6>${emp.name}</h6>
                                <small>${emp.position || 'Operations Personnel'} ${badge}</small>
                            </div>
                        </div>
                    `;
                });
            } else {
                html = '<div class="p-8 text-center text-slate-500"><p>No talent available for assignment in this matrix.</p></div>';
            }
            $('#employeesList').html(html);
            updateSelectedCount();
        });
    });

    $('#memberSearch').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('.member-identity-card').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $(document).on('change', '.member-checkbox', function() {
        updateSelectedCount();
        if($(this).is(':checked')) {
            $(this).closest('.member-identity-card').addClass('active');
        } else {
            $(this).closest('.member-identity-card').removeClass('active');
        }
    });

    function updateSelectedCount() {
        $('#selected-count').text($('.member-checkbox:checked').length);
    }

    $('#save-members-btn').click(function() {
        let teamId = $(this).data('id');
        let selectedIds = [];
        $('.member-checkbox:checked').each(function() { selectedIds.push($(this).val()); });

        $(this).html('<i class="fas fa-spinner fa-spin mr-2"></i> Commit Process...').prop('disabled', true);

        $.ajax({
            url: `/admin/teams/${teamId}/members`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', employee_ids: selectedIds },
            success: function(response) {
                if(response.success) {
                    toastr.success('Matrix updated successfully.');
                    $('#manageMembersModal').modal('hide');
                    table.ajax.reload(null, false);
                }
            },
            error: function() { toastr.error('Matrix update failed.'); },
            complete: function() { $('#save-members-btn').html('Commit Composition').prop('disabled', false); }
        });
    });
});
</script>
@endpush
