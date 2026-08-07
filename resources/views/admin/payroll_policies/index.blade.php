@extends('admin.layouts.app')

@section('breadcrumb')
<div class="col-sm-6">
    <h4 class="page-title text-left">Payroll Policies</h4>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item active">Deduction Rules</li>
    </ol>
</div>
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Deduction Policies</h5>
        <button class="btn btn-indigo btn-sm" data-toggle="modal" data-target="#addPolicyModal">
            <i class="fa fa-plus me-1"></i> New Policy Rule
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="policyTable" class="table table-hover align-middle">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="border-0">Sr.#</th>
                        <th class="border-0">Level</th>
                        <th class="border-0">Target Name</th>
                        <th class="border-0">Status</th>
                        <th class="border-0 text-center">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addPolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.payroll_policies.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Create Deduction Policy</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Policy Level</label>
                        <select name="policy_type" class="form-select mb-2" id="policyType">
                            <option value="Global">Global (All)</option>
                            <option value="Team">Team Specific</option>
                            <option value="Individual">Employee Specific</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="targetContainer">
                        <label class="form-label">Select Target Team</label>
                        <select name="model_id" class="form-select">
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <hr>
                    <h6>Late Arrival Rules</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small text-muted">Threshold (Lates)</label>
                            <input type="number" name="late_policy[threshold]" value="3" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted">Deduction (Days)</label>
                            <input type="number" name="late_policy[deduction_value]" value="0.5" step="0.1" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-indigo px-4">Save Policy</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#policyTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.payroll_policies.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'policy_type', name: 'policy_type'},
            {data: 'target', name: 'target'},
            {data: 'status', name: 'status', defaultContent: '<span class="badge bg-success">Active</span>'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center', defaultContent: '<button class="btn btn-sm btn-link text-muted"><i class="fa fa-ellipsis-v"></i></button>'}
        ]
    });

    $('#policyType').change(function() {
        if ($(this).val() === 'Team') {
            $('#targetContainer').removeClass('d-none');
        } else {
            $('#targetContainer').addClass('d-none');
        }
    });
});
</script>
@endpush
