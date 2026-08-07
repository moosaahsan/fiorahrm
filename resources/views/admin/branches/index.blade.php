@extends('admin.layouts.app')

@section('title', 'Global Branch Orchestration')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Global Branch Orchestration</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Home</a></li>
        <li class="breadcrumb-item active text-slate-500">Office Network</li>
    </ol>
</div>
@endsection

@section('button')
    <button class="tw-btn-primary rounded-2xl px-7 py-3 shadow-lg shadow-brand-500/30" data-toggle="modal" data-target="#branchModal" id="btn-add-branch">
        <i class="fas fa-map-marked-alt"></i> Register New Branch
    </button>
@endsection

@section('content')
<div class="container-fluid">
    @include('includes.flash')

    <div class="tw-directory-card">
        <div class="table-responsive">
            <table class="table table-hover w-100 tw-admin-table" id="branches-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Office Branch</th>
                        <th>Code</th>
                        <th>Timezone</th>
                        <th>Status</th>
                        <th>Network Metrics</th>
                        <th>Action Suite</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content tw-modal-shell">
            <div class="tw-modal-header-dark flex items-start justify-between">
                <h5 class="tw-modal-title" id="branchModalLabel">Register New Office</h5>
                <button type="button" class="close text-3xl leading-none text-white opacity-60 hover:opacity-100" data-dismiss="modal">&times;</button>
            </div>
            <form id="branch-form">
                @csrf
                <input type="hidden" id="branch_id" name="branch_id">
                <div class="modal-body p-8">
                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-12">
                        <div class="md:col-span-8">
                            <label class="tw-form-label uppercase tracking-wide text-slate-500">Branch Name</label>
                            <input type="text" class="tw-form-input" id="name" name="name" required placeholder="e.g. Star Billing (Main Office)">
                        </div>
                        <div class="md:col-span-4">
                            <label class="tw-form-label uppercase tracking-wide text-slate-500">Entity Code</label>
                            <input type="text" class="tw-form-input" id="code" name="code" required placeholder="e.g. SBS-HO">
                        </div>
                    </div>

                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="tw-form-label uppercase tracking-wide text-slate-500">Timezone</label>
                            <select class="tw-form-input" id="timezone" name="timezone" required>
                                <option value="Asia/Karachi">Asia/Karachi (PKT)</option>
                                <option value="UTC">UTC (Universal)</option>
                                <option value="America/New_York">America/New_York (EST)</option>
                                <option value="Europe/London">Europe/London (GMT)</option>
                                <option value="Asia/Dubai">Asia/Dubai (GST)</option>
                            </select>
                        </div>
                        <div>
                            <label class="tw-form-label uppercase tracking-wide text-slate-500">Operational Status</label>
                            <select class="tw-form-input" id="is_active" name="is_active">
                                <option value="1">Active / Operational</option>
                                <option value="0">Inactive / Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="tw-form-label uppercase tracking-wide text-slate-500">Office Address</label>
                        <textarea class="tw-form-input" id="address" name="address" rows="3" placeholder="Point of contact / Physical address..."></textarea>
                    </div>
                </div>
                <div class="tw-modal-footer text-right">
                    <button type="button" class="tw-btn-secondary mr-3 rounded-full px-6" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="tw-btn-primary rounded-full px-10 py-2.5" id="save-branch-btn">Finalize Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#branches-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"d-flex justify-content-between align-items-center mb-4"lf>rt<"d-flex justify-content-between align-items-center mt-4"ip>',
        ajax: "{{ route('admin.branches.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, render: d => `<span class="text-muted font-weight-bold">#${d}</span>`},
            {data: 'name', name: 'name', render: d => `<span class="font-weight-bold text-dark" style="font-size: 1.1rem">${d}</span>`},
            {data: 'code', name: 'code', render: d => `<code class="bg-light px-2 rounded font-weight-bold">${d}</code>`},
            {data: 'timezone', name: 'timezone'},
            {data: 'status', name: 'status'},
            {data: 'metrics', name: 'metrics'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search branches...",
             lengthMenu: "Show _MENU_"
        }
    });

    $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

    $('#btn-add-branch').click(function() {
        $('#branch-form')[0].reset();
        $('#branch_id').val('');
        $('#branchModalLabel').text('Register New Office');
    });

    $('#branch-form').submit(function(e) {
        e.preventDefault();
        let branchId = $('#branch_id').val();
        let url = branchId ? `/admin/branches/${branchId}` : `{{ route('admin.branches.store') }}`;
        let method = branchId ? 'PUT' : 'POST';

        let $btn = $('#save-branch-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message);
                    $('#branchModal').modal('hide');
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
            complete: function() { $btn.prop('disabled', false).html('Finalize Registration'); }
        });
    });

    $(document).on('click', '.edit-branch', function() {
        let id = $(this).data('id');
        $.get(`/admin/branches/${id}/edit`, function(data) {
            $('#branch_id').val(data.id);
            $('#name').val(data.name);
            $('#code').val(data.code);
            $('#timezone').val(data.timezone);
            $('#is_active').val(data.is_active);
            $('#address').val(data.address);
            $('#branchModalLabel').text('Modify Office Parameters');
            $('#branchModal').modal('show');
        });
    });

    $(document).on('click', '.delete-branch', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Decommission Office?',
            text: 'Are you sure you want to remove this branch? This action cannot be undone if no employees are linked.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Decommission',
            cancelButtonText: 'Abort'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/branches/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if(response.success) {
                            toastr.success('Office branch removed successfully.');
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message || 'Deletion failed.');
                    }
                });
            }
        });
    });
});
</script>
@endpush
