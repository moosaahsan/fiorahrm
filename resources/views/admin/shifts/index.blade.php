@extends('admin.layouts.app')

@section('breadcrumb')
    <div class="mb-6 pt-5">
        <h2 class="tw-page-title text-2xl">Work Shift Configuration</h2>
        <p class="mt-1 text-sm font-medium text-slate-500">Orchestrate your operational hours and shift visibility</p>
    </div>
@endsection

@section('button')
@endsection

@section('content')
    <div class="container-fluid">
        @include('includes.flash')

        <div class="tw-directory-card mt-5">
            <div class="mb-6 flex flex-wrap items-end gap-4 rounded-2xl bg-slate-100 p-5">
                <div class="min-w-[200px] flex-1 max-w-xs">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-brand-700">Availability Status</label>
                    <select id="filter-status" class="tw-form-input h-[46px] py-0">
                        <option value="">All Shifts (Global View)</option>
                        <option value="1">Active Only</option>
                        <option value="0">Disabled/Inactive</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="tw-btn-dark h-[46px] rounded-xl px-5" id="filter-button">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button class="tw-btn-secondary h-[46px] rounded-xl px-5" id="reset-filters">
                        <i class="fas fa-redo text-sm"></i> Reset
                    </button>
                </div>
                <div class="ml-auto">
                    @can('create-shift')
                    <a href="#addnew" data-toggle="modal" class="tw-btn-primary h-[46px] rounded-xl px-6 shadow-lg shadow-brand-500/20">
                        <i class="fas fa-plus-circle"></i> Create New Shift
                    </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <x-datatable id="shift-table" :columns="['Shift Name', 'Branch', 'Start', 'End', 'Midnight', 'Status', 'Action']" />
            </div>
        </div>
    </div>

    <div class="modal" id="shift_update_ajax_modal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999 !important;">
        <div class="modal-dialog modal-dialog-centered" role="document"></div>
    </div>

    <div class="modal" id="shift_delete_modal" tabindex="-1" role="dialog" style="z-index: 9999 !important;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form id="deleteForm" method="POST" class="w-100">
                @csrf
                @method('DELETE')
                <div class="modal-content tw-modal-shell">
                    <div class="border-0 bg-rose-500 p-5">
                        <h5 class="mb-0 text-lg font-extrabold text-white">Confirm Removal</h5>
                        <button type="button" class="close absolute right-5 top-5 text-white opacity-75 hover:opacity-100" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="p-6 text-center">
                        <div class="mb-3">
                            <i class="fas fa-trash-alt text-5xl text-rose-500 opacity-25"></i>
                        </div>
                        <p class="font-semibold text-slate-700">Are you sure you want to permanently remove this shift configuration? This action cannot be undone.</p>
                    </div>
                    <div class="flex justify-end gap-2 border-0 p-5 pt-0">
                        <button type="button" class="tw-btn-secondary rounded-xl px-5" data-dismiss="modal">Keep It</button>
                        <button type="submit" class="tw-btn-danger rounded-xl px-5 shadow-sm">Delete Now</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('includes.add_shift')
@endsection

@push('scripts')
    <script>
        function ajax_modal(data, url) {
            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                success: function (html) {
                    $('#ajax_modal_body').html(html);
                    $('#ajax_update_modal').modal('show');
                },
                error: function () {
                    toastr.error('Content could not be loaded.');
                }
            });
        }

        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#shift-table')) {
                $('#shift-table').DataTable().destroy();
            }

            let table = $('#shift-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                ajax: {
                    url: "{{ route('admin.shifts.index') }}",
                    data: function (d) {
                        d.status = $('#filter-status').val();
                    }
                },
                columns: [
                    { data: 'shift_name', name: 'shift_name' },
                    { data: 'branch_name', name: 'branch_name', orderable: false, searchable: false },
                    { data: 'start_time', name: 'start_time' },
                    { data: 'end_time', name: 'end_time' },
                    { data: 'midnight', name: 'crosses_midnight', orderable: false, searchable: false },
                    { data: 'status', name: 'is_active', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search shifts...",
                    lengthMenu: "Show _MENU_ shifts",
                }
            });

            $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

            $('#filter-button').on('click', function () { table.draw(); });
            $('#reset-filters').on('click', function () {
                $('#filter-status').val('');
                table.draw();
            });

            $(document).on('click', '.edit_shift', function (e) {
                e.preventDefault();
                let id = $(this).data('id');

                var $modal = $('#shift_update_ajax_modal');
                var $dialog = $modal.find('.modal-dialog');
                $dialog.html('<div class="modal-content tw-modal-shell"><div class="modal-body p-8 text-center"><div class="text-4xl"><i class="fas fa-spinner fa-spin text-brand-600"></i></div><p class="mt-3 mb-0 font-semibold text-slate-500">Retrieving shift data...</p></div></div>');
                $modal.modal('show');

                $.ajax({
                    url: `/admin/shift_data/${id}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        $.ajax({
                            url: "{{ route('ajax_modal_contents', 'update_shift') }}",
                            type: 'GET',
                            data: { data: response },
                            success: function (html) {
                                $dialog.html(html);
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.delete_shift', function (e) {
                e.preventDefault();
                let shiftId = $(this).data('id');
                $('#deleteForm').attr('action', `/admin/shift/${shiftId}`);
                $('#shift_delete_modal').modal('show');
            });

            $('#deleteForm').submit(function (e) {
                e.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                    success: function () {
                        $('#shift_delete_modal').modal('hide');
                        toastr.success('Shift record removed');
                        table.ajax.reload(null, false);
                    },
                    error: function () {
                        toastr.error('Deletion failed.');
                    }
                });
            });

            $(document).on('change', '.status-toggle', function () {
                let shiftId = $(this).data('id');
                let isActive = $(this).prop('checked') ? 1 : 0;

                $.post(`/admin/shift/${shiftId}/toggle-status`, {
                    _token: '{{ csrf_token() }}',
                    is_active: isActive
                })
                    .done(() => toastr.success('Visibility updated.'))
                    .fail(() => toastr.error('Failed to update status.'));
            });
        });
    </script>
@endpush
