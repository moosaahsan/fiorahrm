@extends('admin.layouts.app')

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Device Scans</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Admin</a></li>
        <li class="breadcrumb-item active text-slate-500">Device Scans</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <x-datatable id="device-punch-table" :columns="['Scan time (device)', 'Device', 'Device user ID', 'Name on device', 'HRM employee', 'HRM result', 'Source']">
        <x-slot name="filters">
            <div class="mb-4 grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                <div class="md:col-span-2">
                    <label for="filter-from" class="tw-filter-label">From</label>
                    <input type="date" id="filter-from" class="tw-form-input h-10 py-0 text-sm" value="{{ now()->toDateString() }}">
                </div>
                <div class="md:col-span-2">
                    <label for="filter-to" class="tw-filter-label">To</label>
                    <input type="date" id="filter-to" class="tw-form-input h-10 py-0 text-sm" value="{{ now()->toDateString() }}">
                </div>
                <div class="md:col-span-2">
                    <label for="filter-device" class="tw-filter-label">Device</label>
                    <select id="filter-device" class="tw-form-input h-10 py-0 text-sm">
                        <option value="">IN and OUT</option>
                        <option value="IN">IN</option>
                        <option value="OUT">OUT</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label for="filter-status" class="tw-filter-label">Result</label>
                    <select id="filter-status" class="tw-form-input h-10 py-0 text-sm">
                        <option value="">All</option>
                        <option value="recorded">Recorded</option>
                        <option value="rejected">Rejected (employee found)</option>
                        <option value="unmapped">Not mapped to an employee</option>
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-3">
                    <button class="tw-btn-primary h-10 px-4 text-sm" id="filter-button">Apply Filter</button>
                    <button class="tw-btn-secondary h-10 px-4 text-sm" id="reset-filters">Reset</button>
                </div>
            </div>
            <p class="mb-3 text-xs text-slate-500">Updates by itself every 10 seconds. "Already checked in" on a repeat scan is normal.</p>
        </x-slot>
    </x-datatable>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        if ($.fn.DataTable.isDataTable('#device-punch-table')) {
            $('#device-punch-table').DataTable().destroy();
        }
        const today = '{{ now()->toDateString() }}';
        let table = $('#device-punch-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.device_punches.index") }}',
                data: function (d) {
                    d.from = $('#filter-from').val();
                    d.to = $('#filter-to').val();
                    d.device = $('#filter-device').val();
                    d.status = $('#filter-status').val();
                }
            },
            columns: [
                { data: 'scan_time', name: 'scan_time' },
                { data: 'device', name: 'device' },
                { data: 'device_user_id', name: 'device_user_id' },
                { data: 'device_user_name', name: 'device_user_name' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'result', name: 'result' },
                { data: 'source', name: 'source' },
            ],
            order: [[0, 'desc']],
            pageLength: 50,
            language: {
                emptyTable: "No scans for this selection",
                processing: "Loading..."
            }
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

        $('#filter-button').on('click', function () { table.draw(); });

        $('#reset-filters').on('click', function () {
            $('#filter-from').val(today);
            $('#filter-to').val(today);
            $('#filter-device').val('');
            $('#filter-status').val('');
            table.draw();
        });

        // Keep the page live without moving the user off the page they are on.
        setInterval(function () { table.ajax.reload(null, false); }, 10000);
    });
</script>
@endpush
