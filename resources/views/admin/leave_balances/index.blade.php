@extends('admin.layouts.app')

@section('title', 'Leave Balance Sheet')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        #balance-table thead th { white-space: nowrap; }
        #balance-table td { white-space: nowrap; }
    </style>
@endpush

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Leave Balance Sheet</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Admin</a></li>
        <li class="breadcrumb-item active text-slate-500">Leave Balance Sheet</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-brand-600">
                <i class="bi bi-clipboard2-data"></i>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">Leave Balance Sheet</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Every employee's allocated, used and remaining days across all leave types, for the selected year.</p>
            </div>
        </div>
        <button id="btnExportExcel" type="button" class="tw-btn-primary h-12 px-6" style="background: #10b981; border-color: #10b981;">
            <i class="bi bi-file-earmark-excel"></i> Download Excel
        </button>
    </div>

    <div class="tw-command-hub">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
            <div class="md:col-span-4">
                <label class="tw-filter-label"><i class="bi bi-person-badge mr-1"></i> Employee</label>
                <select id="filter-employee" class="select2 tw-form-input h-12 py-0">
                    <option value="">All Personnel</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="tw-filter-label"><i class="bi bi-calendar-range mr-1"></i> Leave Year</label>
                <select id="filter-year" class="tw-form-input h-12 py-0">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button class="tw-btn-primary h-12 px-6" id="filter-button"><i class="bi bi-funnel-fill"></i> Search</button>
                <button class="tw-btn-secondary h-12 px-5" id="reset-filters"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>
    </div>

    <div class="tw-directory-card overflow-hidden p-0">
        <div class="table-responsive">
            <table id="balance-table" class="table table-hover mb-0 w-100 tw-admin-table">
                <thead id="balance-table-head"></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#filter-employee').select2({ width: '100%' });

        const leaveTypes = @json($leaveTypes->map(fn ($t) => ['slug' => $t->slug, 'name' => $t->name])->values());

        function buildColumns() {
            const $thead = $('#balance-table-head').empty();
            const $tr = $('<tr></tr>');

            const columns = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'name', name: 'employees.name' },
                { data: 'position', name: 'employees.position' },
            ];
            $tr.append(
                '<th class="text-center" style="width:50px">Sr#</th>' +
                '<th>Employee</th>' +
                '<th>Position</th>'
            );

            leaveTypes.forEach(function (type) {
                ['allocated', 'used', 'remaining'].forEach(function (metric) {
                    const key = type.slug + '_' + metric;
                    columns.push({ data: key, name: key, orderable: false, searchable: false, className: 'text-center' });
                    const label = metric.charAt(0).toUpperCase() + metric.slice(1);
                    $tr.append(`<th class="text-center" title="${type.name}">${type.name}<br><small class="text-muted">${label}</small></th>`);
                });
            });

            columns.push({ data: 'total_remaining', name: 'total_remaining', orderable: false, searchable: false, className: 'text-center fw-bold' });
            $tr.append('<th class="text-center">Total Remaining</th>');

            $thead.append($tr);
            return columns;
        }

        let table = null;

        function initTable() {
            if ($.fn.DataTable.isDataTable('#balance-table')) {
                $('#balance-table').DataTable().destroy();
            }
            $('#balance-table tbody').empty();

            const columns = buildColumns();

            table = $('#balance-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                ordering: false,
                ajax: {
                    url: '{{ route("admin.leave_balances.data") }}',
                    data: function (d) {
                        d.employee_id = $('#filter-employee').val();
                        d.year = $('#filter-year').val();
                    }
                },
                columns: columns,
                dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Search name or position...",
                    emptyTable: `<div class="py-8 text-center"><i class="bi bi-clipboard2-data text-4xl text-slate-300"></i><p class="mb-0 mt-3 font-bold text-slate-500">No employees found.</p></div>`,
                    processing: `<div class="flex items-center justify-center gap-2 text-brand-600"><i class="bi bi-arrow-repeat fa-spin"></i> Loading...</div>`,
                    lengthMenu: "Show _MENU_ records"
                }
            });

            $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');
        }

        initTable();

        $('#filter-button').on('click', () => table.draw());
        $('#filter-employee').on('change', () => table.draw());

        $('#reset-filters').on('click', function () {
            $('#filter-employee').val('').trigger('change');
            $('#filter-year').val('{{ now()->year }}');
            table.draw();
        });

        $('#btnExportExcel').on('click', function () {
            const params = new URLSearchParams({ year: $('#filter-year').val() });
            const employeeId = $('#filter-employee').val();
            if (employeeId) params.set('employee_id', employeeId);
            window.location = "{{ route('admin.leave_balances.export') }}?" + params.toString();
        });
    });
</script>
@endpush
