@extends('admin.layouts.app')

@push('styles')
<link href="{{ URL::asset('assets/css/payroll-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('breadcrumb')
<div class="col-sm-12 px-0">
    <div class="payroll-header mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-white">
                <h2 class="fw-bold mb-1" style="color: #fff !important;">Financial Ledger</h2>
                <p class="text-white-50 mb-0">Manage and audit institutional payroll cycles across all branches.</p>
            </div>
            <div class="d-flex gap-3">
                <a href="{{ route('admin.salary_structures.index') }}" class="btn btn-outline-light rounded-pill px-4">
                    <i class="fas fa-layer-group me-2"></i> Structures
                </a>
                <a href="{{ route('admin.payroll.generate') }}" class="btn btn-white rounded-pill px-4 shadow-lg text-primary fw-bold" style="background: white !important;">
                    <i class="fas fa-plus-circle me-2"></i> Run Payroll
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="payroll-premium-suite">
    <!-- Financial Pulse Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card bg-indigo shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Total Net Displacement</div>
                <h2 class="fw-bold mb-0">Rs. {{ number_format(\App\Models\Payroll::accessible()->sum('total_net'), 2) }}</h2>
                <div class="mt-2 small"><i class="fas fa-chart-line me-1"></i> Cumulative Net Payable</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-slate shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Pending Approvals</div>
                <h2 class="fw-bold mb-0">{{ \App\Models\Payroll::accessible()->where('status', 'Draft')->count() }} Cycles</h2>
                <div class="mt-2 small"><i class="fas fa-clock me-1"></i> Awaiting Financial Audit</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-emerald shadow-sm">
                <div class="small opacity-75 text-uppercase fw-bold mb-1">Approved Ledger</div>
                <h2 class="fw-bold mb-0">{{ \App\Models\Payroll::accessible()->where('status', 'Approved')->count() }} Cycles</h2>
                <div class="mt-2 small"><i class="fas fa-check-double me-1"></i> Finalized Disbursements</div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="payroll-card border-0">
        <div class="payroll-card-header bg-white d-flex justify-content-between align-items-center py-4">
            <div>
                <h5 class="fw-bold mb-1">Payroll Records</h5>
                <p class="text-muted small mb-0">Historical audit trail of all generated payroll periods.</p>
            </div>
            <div class="d-flex gap-2 no-print">
                <button class="btn btn-light btn-sm rounded-pill px-3"><i class="fas fa-filter me-1"></i> Filter</button>
                <button class="btn btn-light btn-sm rounded-pill px-3"><i class="fas fa-download me-1"></i> Export</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="payrollTable" class="table audit-table align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Reference Period</th>
                            <th>Target Entity</th>
                            <th>Gross Total</th>
                            <th>Deductions</th>
                            <th>Net Disbursement</th>
                            <th>Audit Status</th>
                            <th class="text-center">Manage</th>
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
    $('#payrollTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.payroll.index') }}",
        columns: [
            {data: 'month', name: 'month', render: function(data, type, row) {
                return '<div class="d-flex align-items-center">' +
                       '<div class="rounded-circle bg-soft-indigo d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;background:#f5f3ff;color:#4f46e5">' +
                       '<i class="far fa-calendar-check"></i></div>' +
                       '<div><span class="fw-bold text-dark d-block">' + data + ' ' + row.year + '</span>' +
                       '<span class="text-muted small">Generated ' + row.created_at_human + '</span></div></div>';
            }},
            {data: 'branch_name', name: 'branch.name', render: (data) => `<span class="badge-soft badge-soft-indigo">${data || 'Organization-wide'}</span>`},
            {data: 'total_gross', name: 'total_gross', render: (d) => `<span class="currency-main">Rs. ${parseFloat(d).toLocaleString()}</span>`},
            {data: 'total_deductions', name: 'total_deductions', render: (d) => `<span class="text-danger fw-600">-${parseFloat(d).toLocaleString()}</span>`},
            {data: 'total_net', name: 'total_net', render: (d) => `<span class="currency-main text-indigo" style="color:#4f46e5">Rs. ${parseFloat(d).toLocaleString()}</span>`},
            {data: 'status', name: 'status', render: function(data) {
                let status = data.toLowerCase();
                let cls = status === 'draft' ? 'badge-soft-amber' : (status === 'approved' ? 'badge-soft-emerald' : 'badge-soft-indigo');
                let icon = status === 'draft' ? 'fa-pen-nib' : (status === 'approved' ? 'fa-check-circle' : 'fa-check-double');
                return `<span class="badge-soft ${cls}"><i class="fas ${icon} me-1"></i> ${status}</span>`;
            }},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        dom: '<"px-4 py-3 d-flex justify-content-between align-items-center"f>t<"px-4 py-3 d-flex justify-content-between align-items-center"ip>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search ledger references...",
            paginate: { previous: '<i class="fas fa-chevron-left"></i>', next: '<i class="fas fa-chevron-right"></i>' }
        }
    });

    // Custom secondary styling for DT search
    $('.dataTables_filter input').addClass('form-control shadow-none border-2 rounded-pill px-4').css('width', '300px');

    $(document).on('click', '.delete-payroll-btn', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this payroll cycle? This will delete all generated payslips for this period.')) {
            $.ajax({
                url: `/admin/payroll/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        $('#payrollTable').DataTable().ajax.reload(null, false);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to delete payroll cycle. Please try again.');
                }
            });
        }
    });
});
</script>
@endpush
