@extends('admin.layouts.app')

@section('title', 'Leave Policy Audit Trail')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Leave Policy Audit</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Admin</a></li>
        <li class="breadcrumb-item active text-slate-500">Leave Adjustments</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-8 flex items-center gap-4">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-brand-600">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <h2 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">Leave Policy Audit Trail</h2>
            <p class="mt-1 text-sm font-medium text-slate-500">Track all system-initiated balance adjustments for compliance and governance</p>
        </div>
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
                <label class="tw-filter-label"><i class="bi bi-calendar-month mr-1"></i> Month</label>
                <select id="filter-month" class="tw-form-input h-12 py-0">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}">{{ Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="tw-filter-label"><i class="bi bi-calendar-range mr-1"></i> Year</label>
                <select id="filter-year" class="tw-form-input h-12 py-0">
                    <option value="">All Years</option>
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button class="tw-btn-primary h-12 px-6" id="filter-button">
                    <i class="bi bi-funnel-fill"></i> Search Audit
                </button>
                <button class="tw-btn-secondary h-12 px-5" id="reset-filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <div class="tw-directory-card overflow-hidden p-0">
        <table id="adjustment-table" class="table table-hover mb-0 w-100 tw-admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Policy</th>
                    <th>Adjustment</th>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Applied At</th>
                    <th>Audit Remarks</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#filter-employee').select2({ width: '100%' });

        const table = $('#adjustment-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.leave_adjustments.data") }}',
                data: function (d) {
                    d.employee_id = $('#filter-employee').val();
                    d.month = $('#filter-month').val();
                    d.year = $('#filter-year').val();
                }
            },
            columns: [
                { data: 'employee_name', name: 'employee.name' },
                { data: 'policy_name', name: 'policy_name' },
                { data: 'adjustment_amount', name: 'adjustment_amount' },
                { data: 'month', name: 'month' },
                { data: 'year', name: 'year' },
                { data: 'applied_at', name: 'applied_at' },
                { data: 'notes', name: 'notes' },
            ],
            order: [[5, 'desc']],
            dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
            language: {
                search: "",
                searchPlaceholder: "Search audit records...",
                emptyTable: `<div class="py-8 text-center"><i class="bi bi-shield-check text-4xl text-slate-300"></i><p class="mb-0 mt-3 font-bold text-slate-500">No audit records found for this filter criteria.</p></div>`,
                processing: `<div class="flex items-center justify-center gap-2 text-brand-600"><i class="bi bi-arrow-repeat fa-spin"></i> Loading Audit Trail...</div>`,
                lengthMenu: "Show _MENU_ records"
            }
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

        $('#filter-button').on('click', function () {
            table.draw();
        });

        $('#reset-filters').on('click', function () {
            $('#filter-employee').val('').trigger('change');
            $('#filter-month').val('');
            $('#filter-year').val('{{ now()->year }}');
            table.draw();
        });
    });
</script>
@endpush
