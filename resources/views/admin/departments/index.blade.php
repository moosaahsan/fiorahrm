@extends('admin.layouts.app')

@section('title', 'Department Management')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Department Management</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Home</a></li>
        <li class="breadcrumb-item active text-slate-500">Functional Departments</li>
    </ol>
</div>
@endsection

@section('button')
    @can('create-department')
        <button class="tw-btn-dark rounded-2xl px-7 py-3 shadow-lg shadow-slate-900/20" data-toggle="modal" data-target="#departmentModal" id="btn-add-dept">
            <i class="fas fa-building"></i> Create New Department
        </button>
    @endcan
@endsection

@section('content')
<div class="container-fluid">
    @include('includes.flash')

    <div class="tw-directory-card">
        <div class="table-responsive">
            <table class="table table-hover w-100 tw-admin-table" id="departments-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Branch</th>
                        <th>Manager</th>
                        <th>Description</th>
                        <th>Teams Count</th>
                        <th>Action Suite</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content tw-modal-shell">
            <div class="tw-modal-header-dark flex items-start justify-between">
                <h5 class="tw-modal-title" id="departmentModalLabel">Create Department</h5>
                <button type="button" class="close text-3xl leading-none text-white opacity-60 hover:opacity-100" data-dismiss="modal">&times;</button>
            </div>
            <form id="department-form">
                @csrf
                <input type="hidden" id="dept_id" name="dept_id">
                <div class="modal-body p-8">
                    <div class="mb-4">
                        <label class="tw-form-label uppercase tracking-wide text-slate-500">Department Name</label>
                        <input type="text" class="tw-form-input" id="name" name="name" required placeholder="e.g. Sales & Marketing">
                    </div>
                    <div class="mb-4">
                        <label class="tw-form-label uppercase tracking-wide text-slate-500">Department Head / Manager</label>
                        <select class="tw-form-input" id="manager_id" name="manager_id">
                            <option value="">-- No Manager Assigned --</option>
                            @foreach($managers as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                        <small class="mt-1 block text-slate-500">This user will have data access to all teams in this department.</small>
                    </div>
                    <div class="mb-4">
                        <label class="tw-form-label uppercase tracking-wide text-slate-500">Assigned Branch</label>
                        <select class="tw-form-input" id="branch_id" name="branch_id" required>
                            <option value="">-- Select Office Branch --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="tw-form-label uppercase tracking-wide text-slate-500">Description</label>
                        <textarea class="tw-form-input" id="description" name="description" rows="4" placeholder="Briefly define the department's function..."></textarea>
                    </div>
                </div>
                <div class="tw-modal-footer text-right">
                    <button type="button" class="tw-btn-secondary mr-3 rounded-full px-6" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="tw-btn-dark rounded-full px-10 py-2.5" id="save-dept-btn">Finalize Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#departments-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-4"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>',
        ajax: "{{ route('admin.departments.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, render: d => `<span class="text-muted font-weight-bold">#${d}</span>`},
            {data: 'name', name: 'name', render: d => `<span class="font-weight-bold text-dark" style="font-size: 1.1rem">${d}</span>`},
            {data: 'branch.name', name: 'branch.name', defaultContent: '<span class="text-muted">N/A</span>'},
            {data: 'manager', name: 'manager'},
            {data: 'description', name: 'description', render: d => d ? `<span class="text-muted small font-weight-600">${d}</span>` : '<span class="text-light italic">No description.</span>'},
            {data: 'teams_count', name: 'teams_count', render: d => `<span class="tw-badge-muted mt-1 px-3" style="font-size: 0.85rem; border-radius: 8px;">${d} Teams</span>`},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search departments...",
             lengthMenu: "Show _MENU_"
        }
    });

    $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

    $('#btn-add-dept').click(function() {
        $('#department-form')[0].reset();
        $('#dept_id').val('');
        $('#manager_id').val('');
        $('#branch_id').val('');
        $('#departmentModalLabel').text('Create Department');
    });

    $('#department-form').submit(function(e) {
        e.preventDefault();
        let deptId = $('#dept_id').val();
        let url = deptId ? `/admin/departments/${deptId}` : `{{ route('admin.departments.store') }}`;
        let method = deptId ? 'PUT' : 'POST';

        let $btn = $('#save-dept-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message);
                    $('#departmentModal').modal('hide');
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
            complete: function() { $btn.prop('disabled', false).html('Finalize Department'); }
        });
    });

    $(document).on('click', '.edit-dept', function() {
        let id = $(this).data('id');
        $.get(`/admin/departments/${id}/edit`, function(data) {
            $('#dept_id').val(data.id);
            $('#name').val(data.name);
            $('#manager_id').val(data.manager_id);
            $('#branch_id').val(data.branch_id);
            $('#description').val(data.description);
            $('#departmentModalLabel').text('Edit Department Architecture');
            $('#departmentModal').modal('show');
        });
    });

    $(document).on('click', '.delete-dept', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete Department?',
            text: 'This will remove the department group permanently. Teams will become unassigned.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Abort'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/departments/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if(response.success) {
                            toastr.success('Department removed successfully.');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function() {
                        toastr.error('Deletion failed due to an internal error.');
                    }
                });
            }
        });
    });
});
</script>
@endpush
