@extends('admin.layouts.app')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Activity Logs</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Admin</a></li>
        <li class="breadcrumb-item active text-slate-500">Activity Logs</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <x-datatable id="activity-logs-table" :columns="['ID', 'Performed By', 'Action', 'Module', 'Description', 'IP', 'User Agent', 'Time']">
        <x-slot name="filters">
            <div class="mb-4 grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                <div class="md:col-span-3">
                    <label for="filter-action" class="tw-filter-label">Action</label>
                    <select id="filter-action" class="tw-form-input h-10 py-0 text-sm">
                        <option value="">All Actions</option>
                        <option value="view">View</option>
                        <option value="create">Create</option>
                        <option value="edit">Edit</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="restore">Restore</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label for="filter-module" class="tw-filter-label">Module</label>
                    <input type="text" id="filter-module" class="tw-form-input h-10 py-0 text-sm" placeholder="Enter Module Name">
                </div>
                <div class="flex gap-2 md:col-span-6">
                    <button class="tw-btn-primary h-10 px-4 text-sm" id="filter-button">Apply Filter</button>
                    <button class="tw-btn-secondary h-10 px-4 text-sm" id="reset-filters">Reset</button>
                </div>
            </div>
        </x-slot>
    </x-datatable>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        if ($.fn.DataTable.isDataTable('#activity-logs-table')) {
            $('#activity-logs-table').DataTable().destroy();
        }
        let table = $('#activity-logs-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.activity_logs.index") }}',
                data: function (d) {
                    d.action = $('#filter-action').val();
                    d.module = $('#filter-module').val();
                }
            },
            columns: [
                { data: 'id', name: 'activity_logs.id' },
                { data: 'performed_by', name: 'performed_by', orderable: false, searchable: false },
                { data: 'action_badge', name: 'activity_logs.action', orderable: false, searchable: false },
                { data: 'module', name: 'activity_logs.module' },
                { data: 'description', name: 'activity_logs.description' },
                { data: 'ip_address', name: 'activity_logs.ip_address' },
                { data: 'user_agent', name: 'activity_logs.user_agent', orderable: false, searchable: false },
                { data: 'created_at', name: 'activity_logs.created_at' },
            ],
            order: [[0, 'desc']],
            language: {
                emptyTable: "No activity logs available",
                processing: "Loading..."
            }
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

        $('#filter-button').on('click', function () {
            table.draw();
        });

        $('#reset-filters').on('click', function () {
            $('#filter-action').val('');
            $('#filter-module').val('');
            table.draw();
        });
    });
</script>
@endpush
