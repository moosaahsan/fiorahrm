@extends('admin.layouts.app')

@push('styles')
<link href="{{ URL::asset('assets/css/payroll-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('breadcrumb')
<div class="col-sm-12 px-0">
    <div class="payroll-header mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-white">
                <h2 class="fw-bold mb-1" style="color: #fff !important;">Payroll Review</h2>
                <p class="text-white-50 mb-0">Auditing Reference: <strong>{{ date('F Y', mktime(0, 0, 0, $payroll->month, 1, $payroll->year)) }}</strong> | Branch: <strong>{{ $payroll->branch->name ?? 'Global' }}</strong></p>
            </div>
            <div class="d-flex gap-3 no-print">
                <a href="{{ route('admin.payroll.index') }}" class="btn btn-outline-light rounded-pill px-4">
                    <i class="fas fa-arrow-left me-2"></i> Back to Ledger
                </a>
                @if($payroll->status === 'Draft')
                    <form action="{{ route('admin.payroll.approve', $payroll->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-white rounded-pill px-4 shadow-lg text-emerald fw-bold" style="background: white !important; color: #10b981 !important;" onclick="return confirm('Finalize this payroll? Once approved, the record will be locked for disbursement.')">
                            <i class="fas fa-check-circle me-2"></i> Finalize & Lock
                        </button>
                    </form>
                @endif
                <button class="btn btn-white rounded-pill px-4 shadow-lg text-primary fw-bold" style="background: white !important;" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Print Summary
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="payroll-premium-suite">
    <!-- Audit Verification Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card bg-slate shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Total Gross Asset</div>
                <h3 class="fw-bold mb-0">Rs. {{ number_format($payroll->total_gross, 2) }}</h3>
                <div class="mt-2 small">Base Salaries + Allowances</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-rose shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Total Deductions</div>
                <h3 class="fw-bold mb-0">Rs. -{{ number_format($payroll->total_deductions, 2) }}</h3>
                <div class="mt-2 small">Attendance & Policy Fines</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-emerald shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Net Funding Required</div>
                <h3 class="fw-bold mb-0">Rs. {{ number_format($payroll->total_net, 2) }}</h3>
                <div class="mt-2 small">Total Disbursement Value</div>
            </div>
        </div>
        <div class="col-md-3">
            @php
                $status = strtolower($payroll->status);
                $bgClass = $status === 'draft' ? 'bg-indigo' : 'bg-emerald';
            @endphp
            <div class="stat-card {{ $bgClass }} shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Ledger Status</div>
                <h3 class="fw-bold mb-0 text-uppercase">{{ $status }}</h3>
                <div class="mt-2 small"><i class="fas {{ $status === 'draft' ? 'fa-pen-nib' : 'fa-lock' }} me-1"></i> {{ $status === 'draft' ? 'Editable Audit' : 'Immutable Record' }}</div>
            </div>
        </div>
    </div>

    <!-- Employee Breakdown Workspace -->
    <div class="payroll-card overflow-hidden border-0 shadow-lg mt-4">
        <div class="payroll-card-header bg-white py-4 px-4">
            <h5 class="fw-bold mb-1">Individual Disbursements</h5>
            <p class="text-muted small mb-0">Detailed breakdown of earnings and statutory deductions per employee.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="payrollItemsTable" class="table audit-table align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Team Member</th>
                            <th>Gross Base</th>
                            <th>Fines & Deductions</th>
                            <th>Net Payable</th>
                            <th class="text-center">Operations</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#payrollItemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.payroll.show', $payroll->id) }}",
        columns: [
            {data: 'employee.name', name: 'employee.name', render: (data, t, row) => `
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-soft-slate d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                        <i class="far fa-user"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark d-block">${data}</span>
                        <span class="text-muted small">${row.employee.id}</span>
                    </div>
                </div>
            `},
            {data: 'gross_salary', name: 'gross_salary', render: (d) => `<span class="fw-bold text-slate-800">Rs. ${parseFloat(d).toLocaleString()}</span>`},
            {data: 'total_deductions', name: 'total_deductions', render: (d) => `<span class="text-rose-danger fw-600">-${parseFloat(d).toLocaleString()}</span>`},
            {data: 'net_salary', name: 'net_salary', render: (d) => `<span class="currency-main text-indigo">Rs. ${parseFloat(d).toLocaleString()}</span>`},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        dom: '<"px-4 py-3 d-flex justify-content-between align-items-center"f>t<"px-4 py-3 d-flex justify-content-between align-items-center"ip>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search personnel...",
        }
    });
    $('.dataTables_filter input').addClass('form-control shadow-none border-2 rounded-pill px-4').css('width', '250px');
});
</script>
@endpush
