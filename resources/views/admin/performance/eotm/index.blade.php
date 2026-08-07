@extends('admin.layouts.app')

@section('title', 'Employee of the Month')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title performance-header">Employee of the Month</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.performance.evaluations.index') }}">Performance</a></li>
        <li class="breadcrumb-item active">Employee of the Month</li>
    </ol>
</div>
@endsection

@section('content')
<div class="performance-eotm-page">
<div class="container-fluid">
    <div class="row">
        <!-- Declare Area -->
        <div class="col-lg-5">
            <div class="eotm-card">
                <h5 class="card-title-premium text-amber-500">
                    <i class="fas fa-trophy mr-3 text-amber-500"></i> Declare Winner
                </h5>
                <form id="eotm-form">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="saas-label">Target Period</label>
                        <div class="row">
                            <div class="col-6">
                                <select class="saas-input" name="month" id="winner-month" required>
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="saas-input" name="year" id="winner-year" required>
                                    @for($y = (date('Y') - 1); $y <= (date('Y') + 2); $y++)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="saas-label">Select Employee (Ranked by Total Score)</label>
                        <select class="saas-input" name="employee_id" id="candidate-select" required>
                            <option value="">-- Choose Candidate --</option>
                            @foreach($candidates as $c)
                                <option value="{{ $c->employee->id }}">
                                    {{ $c->employee->name }} (Score: {{ (double) $c->total_score }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted mt-1 d-block">Only evaluated employees appear. Rank calculated from automatic ratios and manual ratings.</small>
                    </div>

                    <div class="mb-4">
                        <label class="saas-label">Award Citation / Selection Reason</label>
                        <textarea class="saas-input" name="reason" rows="4" required placeholder="Describe highlights of employee contributions and achievements this month..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-premium-eotm w-100 font-weight-bold" id="declare-btn">
                        <i class="fas fa-crown mr-2"></i> Declare Employee of the Month
                    </button>
                </form>
            </div>
        </div>

        <!-- Leaderboard Winners List -->
        <div class="col-lg-7">
            <div class="eotm-card">
                <h5 class="card-title-premium text-slate-800">
                    <i class="fas fa-award mr-3 text-amber-500"></i> Historic Leaderboard
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="winners-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Winner</th>
                                <th>Team</th>
                                <th>Score (100)</th>
                                <th>Declared by</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit EOTM Modal -->
<div class="modal fade" id="editEotmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title font-weight-bold" style="color: #0f172a;">Edit Citation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="edit-eotm-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="eotm_id" id="edit-eotm-id">
                <div class="modal-body p-4">
                    <div class="form-group mb-0">
                        <label class="saas-label">Award Citation / Selection Reason</label>
                        <textarea class="form-control saas-input" name="reason" id="edit-reason" rows="4" style="width: 100%; border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn font-weight-bold" id="update-eotm-btn" style="background: #6366f1; color: white; border-radius: 10px;">Save Changes</button>
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
    var table = $('#winners-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: "{{ route('admin.performance.eotm.data') }}",
        columns: [
            {data: 'month_name', name: 'month_name', render: d => `<span class="font-weight-700 text-slate-800">${d}</span>`},
            {data: 'employee_name', name: 'employee_name', render: d => `<span class="font-weight-600 text-dark">${d}</span>`},
            {data: 'team', name: 'team'},
            {data: 'score', name: 'score', className: 'text-center', render: d => `<span class="badge-score">${d}</span>`},
            {data: 'selected_by', name: 'selected_by'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        language: {
             search: "",
             searchPlaceholder: "Search winners..."
        }
    });

    // Reload candidates when Month/Year in selection changes
    $('#winner-month, #winner-year').change(function() {
        let month = $('#winner-month').val();
        let year = $('#winner-year').val();
        
        let $select = $('#candidate-select');
        $select.prop('disabled', true).html('<option value="">Loading candidates...</option>');

        // Fetch candidate lists
        $.ajax({
            url: "{{ route('admin.performance.evaluations.index') }}",
            data: { month: month, year: year },
            success: function(html) {
                // Parse options from candidate list API or page
                // We'll write a quick ajax fetch for this. Since we need to update the dropdown on election month changes,
                // let's fetch candidate options from data table or evaluations data endpoint directly or write a small script.
                // Alternatively, we can query evaluations data endpoint and populate.
                $.ajax({
                    url: "{{ route('admin.performance.evaluations.data') }}",
                    data: { month: month, year: year },
                    success: function(json) {
                        $select.html('<option value="">-- Choose Candidate --</option>');
                        if (json.data && json.data.length > 0) {
                            // Sort by total score descending
                            json.data.sort((a, b) => b.total_score - a.total_score);
                            json.data.forEach(function(row) {
                                $select.append(`<option value="${row.id}">${row.name} (Score: ${row.total_score})</option>`);
                            });
                        } else {
                            $select.html('<option value="">-- No candidates evaluated --</option>');
                        }
                        $select.prop('disabled', false);
                    }
                });
            }
        });
    });

    // Handle Form Submit
    $('#eotm-form').submit(function(e) {
        e.preventDefault();
        let $btn = $('#declare-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Declaring...');

        $.ajax({
            url: "{{ route('admin.performance.eotm.select') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    $('#eotm-form')[0].reset();
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Declaring winner failed.');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-crown mr-2"></i> Declare Employee of the Month');
            }
        });
    });

    // Handle Edit Modal Open
    $('#winners-table').on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        let reason = $(this).data('reason');
        $('#edit-eotm-id').val(id);
        $('#edit-reason').val(reason);
        $('#editEotmModal').modal('show');
    });

    // Handle Edit Form Submit
    $('#edit-eotm-form').submit(function(e) {
        e.preventDefault();
        let id = $('#edit-eotm-id').val();
        let $btn = $('#update-eotm-btn');
        $btn.prop('disabled', true).html('Saving...');

        $.ajax({
            url: "{{ url('admin/performance/employee-of-the-month') }}/" + id,
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    $('#editEotmModal').modal('hide');
                    table.ajax.reload(null, false);
                }
            },
            error: function() {
                toastr.error('Failed to update record.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Save Changes');
            }
        });
    });

    // Handle Delete
    $('#winners-table').on('click', '.delete-btn', function() {
        if (!confirm('Are you sure you want to delete this Employee of the Month record?')) return;
        
        let id = $(this).data('id');
        let $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: "{{ url('admin/performance/employee-of-the-month') }}/" + id,
            method: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    table.ajax.reload(null, false);
                }
            },
            error: function() {
                toastr.error('Failed to delete record.');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
