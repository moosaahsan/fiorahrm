@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Roles & Permissions</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Home</a></li>
        <li class="breadcrumb-item active text-slate-500">Access Control Matrix</li>
    </ol>
</div>
@endsection

@section('button')
    <button class="tw-btn-primary rounded-2xl px-7 py-3 shadow-lg shadow-brand-500/30" data-toggle="modal" data-target="#roleModal" id="btn-add-role">
        <i class="fas fa-user-lock"></i> Create New Role
    </button>
@endsection

@section('content')
<div class="container-fluid">
    @include('includes.flash')

    <div class="tw-directory-card">
        <div class="table-responsive">
            <table class="table table-hover w-100 tw-admin-table" id="roles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role Identity</th>
                        <th>Capability Count</th>
                        <th>Action Suite</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content tw-modal-shell">
            <div class="tw-modal-header-indigo flex items-start justify-between">
                <h5 class="tw-modal-title" id="roleModalLabel">Define Access Layer</h5>
                <button type="button" class="close text-3xl leading-none text-white opacity-60 hover:opacity-100" data-dismiss="modal">&times;</button>
            </div>
            <form id="role-form">
                @csrf
                <input type="hidden" id="role_id" name="role_id">
                <div class="modal-body p-8">
                    <label class="tw-form-label uppercase tracking-wide text-slate-500">Role Identity Name</label>
                    <input type="text" class="tw-form-input" id="name" name="name" required placeholder="e.g. Finance Manager">
                    <small class="mt-2 block text-slate-500">After defining the identity name, you will be redirected to the permissions matrix hub.</small>
                </div>
                <div class="tw-modal-footer text-right">
                    <button type="button" class="tw-btn-secondary mr-3 rounded-full px-6" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="tw-btn-primary rounded-full px-10 py-2.5" id="save-role-btn">Commit Access Layer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#roles-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-4"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>',
        ajax: "{{ route('admin.roles.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, render: d => `<span class="text-muted font-weight-bold">#${d}</span>`},
            {data: 'name', name: 'name', render: d => `<span class="font-weight-bold text-dark" style="font-size: 1.1rem">${d}</span>`},
            {data: 'permissions_count', name: 'permissions_count', render: d => `<span class="badge border bg-light text-dark px-3 mt-1" style="font-size: 0.85rem; border-radius: 8px;">${d} Capabilities</span>`},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search access layers...",
             lengthMenu: "Show _MENU_"
        }
    });

    $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

    $('#btn-add-role').click(function() {
        $('#role-form')[0].reset();
        $('#role_id').val('');
        $('#roleModalLabel').text('Define Access Layer');
        $('input[name="permissions[]"]').prop('checked', false);
    });

    $('#role-form').submit(function(e) {
        e.preventDefault();
        let roleId = $('#role_id').val();
        let url = roleId ? `/admin/roles/${roleId}` : `{{ route('admin.roles.store') }}`;
        let method = roleId ? 'PUT' : 'POST';

        let $btn = $('#save-role-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message);
                    $('#roleModal').modal('hide');
                    table.ajax.reload(null, false);

                    if (!roleId && response.role_id) {
                        setTimeout(() => {
                            window.location.href = `/admin/roles/${response.role_id}/permissions`;
                        }, 800);
                    }
                }
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    Object.values(errors).forEach(err => toastr.error(err[0]));
                } else {
                    toastr.error('A security protocol interruption occurred.');
                }
            },
            complete: function() { $btn.prop('disabled', false).html('Commit Access Layer'); }
        });
    });

    $(document).on('click', '.edit-role-modal', function() {
        let id = $(this).data('id');

        $.get(`/admin/roles/${id}/edit`, function(data) {
            $('#role_id').val(data.id);
            $('#name').val(data.name);
            $('#roleModalLabel').text('Rename Access Layer: ' + data.name);
            $('#roleModal').modal('show');
        });
    });

    $(document).on('click', '.delete-role', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Terminate Access Layer?',
            text: 'This will remove the role and all associated capability assignments permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Terminate',
            cancelButtonText: 'Abort'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/roles/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if(response.success) {
                            toastr.success('Access layer terminated.');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message || 'Termination failed.');
                    }
                });
            }
        });
    });
});
</script>
@endpush
