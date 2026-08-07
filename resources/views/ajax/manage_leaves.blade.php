<script>
    $('#shift_update_ajax_modal .modal-dialog').addClass('modal-lg modal-dialog-centered').css('max-width', '800px');
    $('#shift_update_ajax_modal').on('hidden.bs.modal', function () {
        $(this).find('.modal-dialog').removeClass('modal-lg').css('max-width', '');
    });
</script>

<div class="modal-content leave-modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; text-align: left;">
<div class="modal-header">
    <h4 class="modal-title font-weight-bold text-white mb-0"><i class="fas fa-calendar-check me-2"></i> Leave Management - {{ $data->name }}</h4>
    <button type="button" class="close text-white" style="opacity: 0.8; font-size: 1.5rem;" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body bg-light">
    <!-- Allocation Form -->
    <div class="card leave-card mb-4">
        <div class="card-body p-4">
            <h6 class="text-uppercase font-weight-bold text-success mb-3" style="font-size: 0.85rem; letter-spacing: 0.05em;">Assign New Balance</h6>
            <form id="leaveBalanceForm">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $data->id }}">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold mb-1">Year</label>
                        <select name="year" class="form-control form-control-saas form-control-sm" required>
                            @php $currentYear = date('Y'); @endphp
                            @for($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="small text-muted font-weight-bold">Leave Type</label>
                        <select name="leave_type" class="form-control form-control-sm" required>
                            <option value="">-- Type --</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->slug }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted font-weight-bold mb-1">Allocated Days</label>
                        <input type="number" step="0.5" name="allocated" class="form-control form-control-saas form-control-sm" placeholder="Ex: 10" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-save-leave btn-sm w-100 shadow-sm mt-3 mt-md-0">
                            <i class="fa fa-plus-circle"></i> Add
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Balances Table -->
    <div class="table-responsive bg-white rounded-lg shadow-sm border border-light">
        <table class="table leave-table table-hover mb-0">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Leave Type</th>
                    <th class="text-center">Total Assigned</th>
                    <th class="text-center">Already Used</th>
                    <th class="text-center">Remaining</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data->leaveBalances->sortByDesc('year') as $balance)
                    <tr data-id="{{ $balance->id }}">
                        <td class="align-middle">{{ $balance->year }}</td>
                        <td class="align-middle text-capitalize">{{ str_replace('_', ' ', $balance->leave_type) }}</td>
                        <td class="align-middle text-center p-1">
                            <input type="number" step="0.5" class="form-control form-control-sm text-center font-weight-bold text-primary balance-allocated" value="{{ $balance->allocated + 0 }}" style="width: 70px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                        </td>
                        <td class="align-middle text-center p-1">
                            <input type="number" step="0.5" class="form-control form-control-sm text-center text-warning balance-used" value="{{ $balance->used + 0 }}" style="width: 70px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                        </td>
                        <td class="align-middle text-center p-1">
                            <input type="number" step="0.5" class="form-control form-control-sm text-center font-weight-bold text-success balance-remaining" value="{{ $balance->remaining + 0 }}" style="width: 70px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                        </td>
                        <td class="align-middle text-center p-2">
                            <div class="btn-group shadow-sm border rounded">
                                <button class="btn btn-light btn-sm update-balance text-primary" title="Update Sync">
                                    <i class="fa fa-sync-alt"></i>
                                </button>
                                <button class="btn btn-light btn-sm delete-balance text-danger border-left" data-id="{{ $balance->id }}" title="Delete Permanently">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-2x mb-3 text-light"></i><br>
                            No leave balances assigned yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

<script>
    // Handle Form Submit (New Balance)
    $('#leaveBalanceForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.post("{{ route('admin.employees.leave_balance.store') }}", $(this).serialize())
        .done(function(res) {
            toastr.success(res.message);
            // Reload modal to see changes
            $('.trigger_ajax_modal[data-id="{{ $data->id }}"][data-action="manage_leaves"]').trigger('click');
        })
        .fail(function(xhr) {
             const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error saving balance';
             toastr.error(msg);
             $btn.prop('disabled', false).html(originalHtml);
        });
    });

    // Handle Inline Update
    $('.update-balance').off('click').on('click', function() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();

        const data = {
            _token: "{{ csrf_token() }}",
            allocated: $row.find('.balance-allocated').val(),
            used: $row.find('.balance-used').val(),
            remaining: $row.find('.balance-remaining').val()
        };

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: "/admin/employees/leave-balance/" + id,
            type: 'PUT',
            data: data,
            success: function(res) {
                toastr.success(res.message);
                $btn.prop('disabled', false).html(originalHtml);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update.';
                toastr.error(msg);
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Handle Delete
    $('.delete-balance').off('click').on('click', function() {
        if(!confirm('Are you sure you want to delete this leave balance?')) return;
        
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: "/admin/employees/leave-balance/" + id,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function(res) {
                 toastr.success(res.message);
                 $('.trigger_ajax_modal[data-id="{{ $data->id }}"][data-action="manage_leaves"]').trigger('click');
            },
            error: function(xhr) {
                toastr.error('Failed to delete.');
                $btn.prop('disabled', false);
            }
        });
    });
</script>
