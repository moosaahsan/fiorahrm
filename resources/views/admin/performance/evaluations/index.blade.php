@extends('admin.layouts.app')

@section('title', 'Employee Performance Evaluations')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title performance-header">Employee Performance Evaluations</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item active">Performance Evaluations</li>
    </ol>
</div>
@endsection

@section('button')
    <a href="{{ route('admin.performance.settings.index') }}" class="tw-btn-secondary mr-2 px-4 rounded-pill font-bold" style="border-radius: 50px; font-size: 0.9rem; border-color: #6366f1; color: #6366f1;">
        <i class="fas fa-cog mr-2"></i> Settings
    </a>
    <a href="{{ route('admin.performance.eotm.index') }}" class="btn btn-premium-eotm px-4 rounded-pill font-weight-bold" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 50px; box-shadow: 0 10px 15px -3px rgba(217, 119, 6, 0.2);">
        <i class="fas fa-trophy mr-2"></i> Employee of the Month
    </a>
@endsection

@section('content')
<div class="performance-eval-page">
<div class="container-fluid">
    <!-- Filter and Bulk Action Panel -->
    <div class="filter-panel">
        <div class="row align-items-end">
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="saas-label">Target Evaluation Month</label>
                <select class="saas-input filter-field" id="select-month">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="saas-label">Target Year</label>
                <select class="saas-input filter-field" id="select-year">
                    @for($y = (date('Y') - 1); $y <= (date('Y') + 2); $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="col-md-6 text-md-right">
                <button class="btn btn-run-calc px-4" id="btn-calculate-bulk">
                    <i class="fas fa-calculator mr-2"></i> Compute Auto metrics (Bulk)
                </button>
            </div>
        </div>
    </div>

    <!-- Evaluations List Card -->
    <div class="eval-card">
        <div class="table-responsive">
            <table class="table table-hover w-100" id="evaluations-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center">Employee</th>
                        <th rowspan="2" class="text-center">Team</th>
                        <th rowspan="2" class="text-center">Branch</th>
                        <th colspan="4" class="text-center bg-light font-weight-700">Calculated Metrics (50%)</th>
                        <th colspan="3" class="text-center bg-light font-weight-700" style="background-color: rgba(16, 185, 129, 0.05) !important;">Manual Feedback (50%)</th>
                        <th rowspan="2" class="text-center">Total Score (100)</th>
                        <th rowspan="2" class="text-center">Actions</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-light" style="font-size: 0.7rem;">Att ({{ $settings['attendance_weight']->value ?? 15.0 }})</th>
                        <th class="text-center bg-light" style="font-size: 0.7rem;">Leaves ({{ $settings['leave_weight']->value ?? 15.0 }})</th>
                        <th class="text-center bg-light" style="font-size: 0.7rem;">Breaks ({{ $settings['break_weight']->value ?? 10.0 }})</th>
                        <th class="text-center bg-light" style="font-size: 0.7rem;">Lates ({{ $settings['late_weight']->value ?? 10.0 }})</th>
                        <th class="text-center" style="background-color: rgba(16, 185, 129, 0.03); font-size: 0.7rem;">Dress ({{ $settings['dress_code_weight']->value ?? 10.0 }})</th>
                        <th class="text-center" style="background-color: rgba(16, 185, 129, 0.03); font-size: 0.7rem;">Perf ({{ $settings['work_performance_weight']->value ?? 20.0 }})</th>
                        <th class="text-center" style="background-color: rgba(16, 185, 129, 0.03); font-size: 0.7rem;">Behavior ({{ $settings['behavior_weight']->value ?? 20.0 }})</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Manual Evaluation Modal -->
<div class="modal fade" id="evaluateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content saas-modal">
            <div class="saas-modal-header">
                <h5 class="saas-modal-title">Manual Feedbacks & Scoring</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.7;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="evaluation-form">
                @csrf
                <input type="hidden" name="employee_id" id="eval-employee-id">
                <input type="hidden" name="month" id="eval-month">
                <input type="hidden" name="year" id="eval-year">
                
                <div class="modal-body p-5">
                    <h5 class="font-weight-800 text-dark mb-4 text-center" id="eval-employee-name">Employee Name</h5>
                    
                    <div class="mb-4">
                        <label class="saas-label">Dress Code & Professional Grooming Rating (Max: {{ $settings['dress_code_weight']->value ?? 10.0 }})</label>
                        <input type="number" step="0.1" class="saas-input" name="dress_code_score" id="eval-dress" required min="0" max="{{ $settings['dress_code_weight']->value ?? 10.0 }}">
                    </div>

                    <div class="mb-4">
                        <label class="saas-label">Work Performance Rating (Max: {{ $settings['work_performance_weight']->value ?? 20.0 }})</label>
                        <input type="number" step="0.1" class="saas-input" name="work_performance_score" id="eval-work" required min="0" max="{{ $settings['work_performance_weight']->value ?? 20.0 }}">
                    </div>

                    <div class="mb-4">
                        <label class="saas-label">Teamwork & Behavior Rating (Max: {{ $settings['behavior_weight']->value ?? 20.0 }})</label>
                        <input type="number" step="0.1" class="saas-input" name="behavior_score" id="eval-behavior" required min="0" max="{{ $settings['behavior_weight']->value ?? 20.0 }}">
                    </div>

                    <div class="mb-4">
                        <label class="saas-label">Comments & Remarks</label>
                        <textarea class="saas-input" name="comments" id="eval-comments" rows="3" placeholder="Provide qualitative review feedback..."></textarea>
                    </div>
                </div>

                <div class="saas-sticky-footer text-right">
                    <button type="button" class="tw-btn-secondary px-4 rounded-pill font-weight-bold mr-3" data-dismiss="modal">Close</button>
                    <button type="submit" class="tw-btn-primary px-5 rounded-pill font-weight-bold" id="save-eval-btn">Save Evaluation</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#evaluations-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('admin.performance.evaluations.data') }}",
            data: function(d) {
                d.month = $('#select-month').val();
                d.year = $('#select-year').val();
            }
        },
        columns: [
            {data: 'name', name: 'name', render: d => `<span class="font-weight-700 text-slate-800">${d}</span>`},
            {data: 'team', name: 'team', className: 'text-center'},
            {data: 'branch', name: 'branch', className: 'text-center'},
            
            // Automatic
            {data: 'attendance_score', name: 'attendance_score', className: 'text-center', render: d => `<span class="score-badge score-badge-auto">${d}</span>`},
            {data: 'leave_score', name: 'leave_score', className: 'text-center', render: d => `<span class="score-badge score-badge-auto">${d}</span>`},
            {data: 'break_score', name: 'break_score', className: 'text-center', render: d => `<span class="score-badge score-badge-auto">${d}</span>`},
            {data: 'late_score', name: 'late_score', className: 'text-center', render: d => `<span class="score-badge score-badge-auto">${d}</span>`},
            
            // Manual
            {data: 'dress_code_score', name: 'dress_code_score', className: 'text-center', render: d => `<span class="score-badge score-badge-manual">${d}</span>`},
            {data: 'work_performance_score', name: 'work_performance_score', className: 'text-center', render: d => `<span class="score-badge score-badge-manual">${d}</span>`},
            {data: 'behavior_score', name: 'behavior_score', className: 'text-center', render: d => `<span class="score-badge score-badge-manual">${d}</span>`},
            
            // Total
            {data: 'total_score', name: 'total_score', className: 'text-center', render: d => `<span class="score-badge score-badge-total">${d}</span>`},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search employee evaluations...",
             lengthMenu: "Show _MENU_"
        }
    });

    // Redraw table on filter change
    $('.filter-field').change(function() {
        table.ajax.reload(null, false);
    });

    // Run Bulk Calc
    $('#btn-calculate-bulk').click(function() {
        let month = $('#select-month').val();
        let year = $('#select-year').val();
        let $btn = $(this);
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Calculating...');

        $.ajax({
            url: "{{ route('admin.performance.evaluations.calculate') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                month: month,
                year: year
            },
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    table.ajax.reload(null, false);
                }
            },
            error: function() {
                toastr.error('Calculating scores failed due to system exception.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-calculator mr-2"></i> Compute Auto metrics (Bulk)');
            }
        });
    });

    // Open Evaluate Modal
    $(document).on('click', '.edit-evaluation-btn', function() {
        let empId = $(this).data('id');
        let empName = $(this).data('name');
        let dress = $(this).data('dress');
        let work = $(this).data('work');
        let behavior = $(this).data('behavior');
        let comments = $(this).data('comments');

        $('#eval-employee-id').val(empId);
        $('#eval-employee-name').text(empName);
        $('#eval-dress').val(dress);
        $('#eval-work').val(work);
        $('#eval-behavior').val(behavior);
        $('#eval-comments').val(comments);

        $('#eval-month').val($('#select-month').val());
        $('#eval-year').val($('#select-year').val());

        $('#evaluateModal').modal('show');
    });

    // Handle Evaluation Form Submit
    $('#evaluation-form').submit(function(e) {
        e.preventDefault();
        let $btn = $('#save-eval-btn');
        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: "{{ route('admin.performance.evaluations.save_manual') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    $('#evaluateModal').modal('hide');
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Evaluation saving failed.');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save Evaluation');
            }
        });
    });
});
</script>
@endpush
