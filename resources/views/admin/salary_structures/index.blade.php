@extends('admin.layouts.app')

@push('styles')
<link href="{{ URL::asset('assets/css/payroll-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('breadcrumb')
<div class="col-sm-12">
    <div class="payroll-header mb-4">
        <h2 class="text-white mb-1">Standard Structures</h2>
        <p class="text-white-50 mb-0">Define and manage institutional payroll configuration packages.</p>
    </div>
</div>
@endsection

@section('content')
<div class="payroll-premium-suite">
    <div class="row">
        <div class="col-12">
            <div class="payroll-card border-0">
                <div class="payroll-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-slate-800">Available Packages</h5>
                    <button class="btn btn-indigo rounded-pill px-4 shadow-sm" data-toggle="modal" data-target="#addStructureModal">
                        <i class="fa fa-plus me-1"></i> New Structure
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="structureTable" class="table audit-table align-middle">
                            <thead>
                                <tr>
                                    <th>Sr.#</th>
                                    <th>Package Name</th>
                                    <th>Basic Salary</th>
                                    <th>Office Scope</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CONFIGURATION WIZARD MODAL --}}
    <div class="modal fade" id="addStructureModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <form action="{{ route('admin.salary_structures.store') }}" method="POST" id="salaryStructureForm">
                    @csrf
                    <div class="modal-header border-0 pt-4 px-4 pb-3">
                        <div>
                            <h4 class="modal-title fw-bold text-white mb-0">Configure Payroll Package</h4>
                            <p class="text-white-50 small mb-0">Define institutional rules for earnings and deductions.</p>
                        </div>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    
                    <div class="modal-body px-4 pt-4">
                        {{-- STEP INDICATORS --}}
                        <div class="d-flex align-items-center mb-5 px-5">
                            <div class="step-indicator active" data-step="1">1</div>
                            <div class="step-line" id="line-1"></div>
                            <div class="step-indicator" data-step="2">2</div>
                            <div class="step-line" id="line-2"></div>
                            <div class="step-indicator" data-step="3">3</div>
                        </div>

                        {{-- STEP 1: IDENTITY --}}
                        <div class="wizard-step active" id="step-1">
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <label class="fw-bold text-slate-600 small mb-2">PACKAGE NAME</label>
                                    <input type="text" name="name" class="form-control pr-select shadow-none" placeholder="e.g. Senior Software Engineer" required>
                                    <small class="text-muted">A descriptive name for HR reference.</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="fw-bold text-slate-600 small mb-2">ASSIGNED OFFICE</label>
                                    <select name="branch_id" class="form-control pr-select shadow-none" required>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="fw-bold text-slate-600 small mb-2">EFFECTIVE DATE</label>
                                    <input type="date" name="effective_date" class="form-control pr-select shadow-none" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="fw-bold text-slate-600 small mb-2">EMPLOYEE TYPE SCOPE</label>
                                    <select name="employee_type" class="form-control pr-select shadow-none">
                                        <option value="full_time">Full Time / Salaried</option>
                                        <option value="contract">Fixed Contract</option>
                                        <option value="intern">Internship</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 2: COMPENSATION --}}
                        <div class="wizard-step" id="step-2">
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="bg-soft-indigo p-4 rounded-3 mb-4 border-1 border-indigo-soft">
                                        <label class="fw-bold text-indigo mb-2 small text-uppercase">CORE MONTHLY BASE SALARY</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-0 bg-white fw-bold text-slate-400">RS</span>
                                            <input type="number" name="basic_salary" class="form-control border-0 font-24 fw-bold px-3 shadow-none bg-white" placeholder="0.00" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-bold text-slate-600 small mb-0">MONTHLY ALLOWANCES</label>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-indigo fw-bold" id="addEarnings"><i class="fa fa-plus-circle me-1"></i> Add Entry</button>
                                    </div>
                                    <div id="earningsContainer"></div>
                                </div>

                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-bold text-slate-600 small mb-0">STANDARD DEDUCTIONS</label>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-rose-danger fw-bold" id="addDeductions"><i class="fa fa-plus-circle me-1"></i> Add Entry</button>
                                    </div>
                                    <div id="deductionsContainer"></div>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 3: RULES & PREVIEW --}}
                        <div class="wizard-step" id="step-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="payroll-card p-4 bg-light mb-3 border-0">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-clock text-indigo me-2"></i> Overtime Multipliers</h6>
                                        <div class="mb-3">
                                            <label class="small text-muted mb-1">Standard Workdays (e.g. 1.5x)</label>
                                            <input type="number" name="config[ot_factor_standard]" class="form-control shadow-none" step="0.1" value="1.5">
                                        </div>
                                        <div class="mb-3">
                                            <label class="small text-muted mb-1">Gazetted Holidays (e.g. 2.0x)</label>
                                            <input type="number" name="config[ot_factor_holiday]" class="form-control shadow-none" step="0.1" value="2.0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card bg-slate shadow-indigo p-4">
                                        <h6 class="text-white-50 small mb-2 text-uppercase font-600">Configuration Summary</h6>
                                        <h3 class="text-white mb-3">Package Validated</h3>
                                        <p class="text-white-50 small mb-4">This configuration set will be available for all assigned office personnel in future payroll cycles.</p>
                                        <div class="d-flex align-items-center">
                                            <div class="badge-soft-emerald me-2">AUDIT PASSED</div>
                                            <div class="badge-soft-indigo">READY</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4 mt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" id="prevStep" style="display:none;">Back</button>
                        <button type="button" class="btn btn-indigo rounded-pill px-5 fw-bold" id="nextStep">Continue Setup <i class="fas fa-arrow-right ms-2"></i></button>
                        <button type="submit" class="btn btn-indigo rounded-pill px-5 fw-bold" id="submitBtn" style="display:none;">Finalize & Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // DataTable Init
    $('#structureTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.salary_structures.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name', className: 'fw-bold'},
            {data: 'basic_salary', name: 'basic_salary', className: 'text-indigo fw-bold'},
            {data: 'branch.name', name: 'branch.name'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ]
    });

    // Wizard Logic
    var currentStep = 1;
    function updateWizard() {
        $('.wizard-step').removeClass('active');
        $('#step-' + currentStep).addClass('active');
        $('.step-indicator').removeClass('active');
        $('.step-indicator[data-step="' + currentStep + '"]').addClass('active');
        
        // Button Logic
        if (currentStep === 1) {
            $('#prevStep').hide();
            $('#nextStep').show().text('Continue Setup');
            $('#submitBtn').hide();
        } else if (currentStep === 2) {
            $('#prevStep').show();
            $('#nextStep').show().text('Review Configuration');
            $('#submitBtn').hide();
        } else {
            $('#prevStep').show();
            $('#nextStep').hide();
            $('#submitBtn').show();
        }
    }

    $('#nextStep').on('click', function() { if(currentStep < 3) { currentStep++; updateWizard(); } });
    $('#prevStep').on('click', function() { if(currentStep > 1) { currentStep--; updateWizard(); } });

    // Dynamic Rows Logic
    function addRow(containerId, inputNameBase, placeholder) {
        var html = `
            <div class="dynamic-row">
                <div class="row g-2">
                    <div class="col-7">
                        <input type="text" name="${inputNameBase}[][name]" class="form-control form-control-sm border-0 border-bottom bg-transparent fw-600" placeholder="${placeholder}">
                    </div>
                    <div class="col-4">
                        <input type="number" name="${inputNameBase}[][amount]" class="form-control form-control-sm border-0 border-bottom bg-transparent text-end fw-bold" placeholder="0.00">
                    </div>
                    <div class="col-1">
                        <button type="button" class="btn btn-link btn-sm p-0 text-danger remove-row"><i class="fa fa-times"></i></button>
                    </div>
                </div>
            </div>`;
        $('#' + containerId).append(html);
    }

    $('#addEarnings').on('click', function() { addRow('earningsContainer', 'earnings', 'Allowance Name'); });
    $('#addDeductions').on('click', function() { addRow('deductionsContainer', 'deductions', 'Deduction Name'); });
    $(document).on('click', '.remove-row', function() { $(this).closest('.dynamic-row').remove(); });

    // Initial default rows
    addRow('earningsContainer', 'earnings', 'Medical Allowance');
    addRow('deductionsContainer', 'deductions', 'Insurrance');
});
</script>
@endpush
