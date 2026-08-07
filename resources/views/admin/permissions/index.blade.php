@extends('admin.layouts.app')

@section('title', 'Permission Management')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Permission Management</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}" class="text-brand-600 hover:text-brand-700">Roles</a></li>
        <li class="breadcrumb-item active text-slate-500">Module Slugs</li>
    </ol>
</div>
@endsection

@section('button')
    <button class="tw-btn-primary rounded-2xl px-7 py-3 shadow-lg shadow-brand-500/30" data-toggle="modal" data-target="#permissionModal" id="btn-add-permission">
        <i class="fas fa-plus-circle"></i> Create New Permission
    </button>
@endsection

@section('content')
<div class="container-fluid">
    @include('includes.flash')

    <div class="tw-directory-card">
        <div class="table-responsive">
            <table class="table table-hover w-100 tw-admin-table" id="permissions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Module</th>
                        <th>Permission Slug</th>
                        <th>Guard</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="permissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content tw-modal-shell">
            <div class="tw-modal-header-indigo flex items-start justify-between">
                <h5 class="tw-modal-title" id="permissionModalLabel">Create Permission</h5>
                <button type="button" class="close text-3xl leading-none text-white opacity-60 hover:opacity-100" data-dismiss="modal">&times;</button>
            </div>
            <form id="permission-form">
                @csrf
                <input type="hidden" id="permission_id" name="permission_id">
                <div class="modal-body p-8">
                    <div class="mb-4">
                        <label class="tw-form-label">Module / Category Identity</label>
                        <input type="text" class="tw-form-input" id="module" name="module" required list="module-list" placeholder="e.g. Attendance or Custom Module">
                        <datalist id="module-list">
                            <option value="Attendance">
                            <option value="Breaks">
                            <option value="Leaves">
                            <option value="Holidays">
                            <option value="Employees">
                            <option value="Leads">
                            <option value="Deals">
                            <option value="Settings">
                            <option value="Organization">
                            <option value="Late Arrivals">
                            <option value="Half Days">
                            <option value="General">
                        </datalist>
                        <small class="mt-2 block text-slate-500">You can select an existing module or type a new target category name.</small>
                    </div>
                    <div>
                        <label class="tw-form-label">Permission Name (Slug Format)</label>
                        <input type="text" class="tw-form-input" id="name" name="name" required placeholder="e.g. edit-attendance-logs">
                        <small class="mt-2 block text-slate-500">Spaces will be automatically converted to hyphens.</small>
                    </div>
                </div>
                <div class="tw-modal-footer text-right">
                    <button type="button" class="tw-btn-secondary mr-2 rounded-full px-6" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="tw-btn-primary rounded-full px-8" id="save-btn">Save Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#permissions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.permissions.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'module', name: 'module', render: d => `<span class="tw-badge bg-indigo-100 text-indigo-800">${d}</span>`},
            {data: 'name', name: 'name', render: d => `<code class="rounded bg-slate-100 px-2 py-1 text-base font-bold text-brand-600">${d}</code>`},
            {data: 'guard_name', name: 'guard_name', render: d => `<span class="tw-badge bg-sky-100 text-sky-800">${d}</span>`},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

    $('#btn-add-permission').click(function() {
        $('#permission-form')[0].reset();
        $('#permission_id').val('');
        $('#module').val('Attendance');
        $('#permissionModalLabel').text('Create Permission');
    });

    $('#permission-form').submit(function(e) {
        e.preventDefault();
        let id = $('#permission_id').val();
        let url = id ? `/admin/permissions/${id}` : `{{ route('admin.permissions.store') }}`;
        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                toastr.success(response.message);
                $('#permissionModal').modal('hide');
                table.ajax.reload();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON.message || 'Operation failed');
            }
        });
    });

    $(document).on('click', '.edit-permission', function() {
        let id = $(this).data('id');
        $.get(`/admin/permissions/${id}/edit`, function(data) {
            $('#permission_id').val(data.id);
            $('#name').val(data.name);
            $('#module').val(data.module);
            $('#permissionModalLabel').text('Edit Permission');
            $('#permissionModal').modal('show');
        });
    });

    $(document).on('click', '.delete-permission', function() {
        let id = $(this).data('id');
        if(confirm('Are you sure you want to delete this permission?')) {
            $.ajax({
                url: `/admin/permissions/${id}`,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success(response.message);
                    table.ajax.reload();
                }
            });
        }
    });
});
</script>
@endpush
