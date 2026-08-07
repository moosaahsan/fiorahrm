@extends('admin.layouts.app')

@section('title', 'Job Postings')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title interviews-header">Job Vacancy Management</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Job Postings</li>
    </ol>
</div>
@endsection

@section('button')
    @can('manage-job-postings')
        <a class="btn btn-premium-interview" href="{{ route('admin.job-postings.create') }}">
            <i class="fas fa-plus-circle mr-2"></i> Create New Vacancy
        </a>
    @endcan
@endsection

@section('content')
<div class="container-fluid">
    <div class="job-card">
        <div class="table-responsive">
            <table class="table table-hover tw-admin-table w-100" id="jobs-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Job Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Shift</th>
                        <th>Status</th>
                        <th>Created At</th>
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
$(document).ready(function() {
    $('#jobs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.job-postings.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'title', name: 'title', render: d => `<span class="font-weight-bold text-dark">${d}</span>`},
            {data: 'category_badge', name: 'category'},
            {data: 'type', name: 'type'},
            {data: 'shift', name: 'shift'},
            {data: 'status_badge', name: 'status'},
            {data: 'created_at', name: 'created_at', render: d => moment(d).format('DD MMM, YYYY')},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $(document).on('click', '.delete-job', function() {
        let id = $(this).data('id');
        if(confirm('Are you sure you want to delete this vacancy?')) {
            $.ajax({
                url: `/admin/job-postings/${id}`,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    $('#jobs-table').DataTable().ajax.reload();
                    toastr.success(response.message);
                }
            });
        }
    });
});
</script>
@endpush
