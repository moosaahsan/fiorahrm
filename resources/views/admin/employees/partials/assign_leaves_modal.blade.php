<div class="modal-header tw-modal-header-indigo">
    <h5 class="tw-modal-title text-lg">Assign Leave Balances for {{ $employee->name }} (2025)</h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body p-6">
    <form id="assign-leaves-form" action="{{ route('admin.employees.assignLeaveBalance', $employee->id) }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($leaveTypes as $type)
                <div>
                    <label for="leave_{{ $type->slug }}" class="tw-filter-label">{{ $type->name }}</label>
                    <input type="number" name="leaves[{{ $type->slug }}]" id="leave_{{ $type->slug }}"
                        class="tw-form-input" value="{{ $leaveBalances[$type->slug] ?? 0 }}" min="0" step="0.5">
                </div>
            @endforeach
        </div>
        <div class="mt-6 flex gap-2">
            <button type="submit" class="tw-btn-primary">Save Leave Balances</button>
            <button type="button" class="tw-btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
    </form>
</div>
<script>
    $(document).ready(function () {
        $('#assign-leaves-form').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            $button.prop('disabled', true).addClass('opacity-50');
            const $spinner = $('<span class="spinner-border spinner-border-sm ms-1"></span>');
            $button.after($spinner);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                success: function (response) {
                    toastr.success(response.message || 'Leave balances assigned successfully!');
                    $('#ajaxModal').modal('hide');
                    if ($.fn.DataTable.isDataTable('#employees-table')) {
                        $('#employees-table').DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to assign leave balances.');
                },
                complete: function () {
                    $spinner.remove();
                    $button.prop('disabled', false).removeClass('opacity-50');
                }
            });
        });
    });
</script>
