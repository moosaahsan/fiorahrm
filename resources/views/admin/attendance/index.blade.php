@extends('admin.layouts.app')

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Attendance Logs</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Admin</a></li>
            <li class="breadcrumb-item active">Attendance Logs</li>
        </ol>
    </div>
@endsection

@section('content')
    <x-datatable id="attendance-table" :columns="['Date', 'Employee', 'Shift', 'Check In', 'Check Out', 'Status']">
        <x-slot name="filters">
            <div class="row align-items-end mb-3">
                <div class="col-md-3">
                    <label>Employee</label>
                    <select id="filter-employee" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Shift</label>
                    <select id="filter-shift" class="form-control form-control-sm" disabled>
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Date Range</label>
                    <input type="text" id="filter-date" class="form-control form-control-sm"
                        placeholder="Select date range">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary me-2" id="filter-button">Apply Filter</button>
                    <button class="btn btn-sm btn-secondary" id="reset-filters">Reset</button>
                </div>
            </div>
        </x-slot>
    </x-datatable>
@endsection

@push('scripts')
    <script>
        const table = $('#attendance-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("admin.attendance.logs.data") }}',
                data: function (d) {
                    d.employee_id = $('#filter-employee').val();
                    d.shift_id = $('#filter-shift').val();
                    d.date_range = $('#filter-date').val();
                }
            },
            columns: [
                { data: 'shift_date', name: 'shift_date' },
                { data: 'employee_name', name: 'employees.name', orderable: true, searchable: true },
                { data: 'shift_name', name: 'shift_name', orderable: false, searchable: false },
                { data: 'check_in', name: 'check_in' },
                { data: 'check_out', name: 'check_out' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false }
            ]
        });

        $('#filter-button').on('click', function () {
            table.draw();
        });

        $('#reset-filters').on('click', function () {
            $('#filter-employee').val('');
            $('#filter-shift').html('<option value="">All</option>').prop('disabled', true);
            $('#filter-date').val('');
            table.draw();
        });

        $('#filter-employee').change(function () {
            const empId = $(this).val();
            $('#filter-shift').prop('disabled', true).html('<option value="">Loading...</option>');

            if (!empId) {
                $('#filter-shift').html('<option value="">All</option>').prop('disabled', true);
                return;
            }

            $.get(`/api/employee/${empId}/shifts`, function (shifts) {
                let options = '<option value="">All</option>';
                shifts.forEach(shift => {
                    options += `<option value="${shift.shift_id}">${shift.shift.shift_name}</option>`;
                });
                $('#filter-shift').html(options).prop('disabled', false);
            });
        });

        // Initialize date range picker
        $('#filter-date').daterangepicker({
            opens: 'right',
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('#filter-date').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#filter-date').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });
    </script>
@endpush