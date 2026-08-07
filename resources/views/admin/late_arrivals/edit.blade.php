<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">
        <span>&times;</span>
    </button>
</div>

<h4 class="modal-title px-3"><b>Update Late Arrival</b></h4>

<div class="modal-body">
    <div class="card-body text-left">
        <form method="POST" id="update-checkin">
            @csrf
            <input type="hidden" name="late_id" value="{{ $lateArrival->id }}">

            <div class="form-group">
                <label>Employee ID</label>
                <input type="text" class="tw-form-input" value="{{ $lateArrival->employee->employee_id ?? '-' }}"
                    disabled>
            </div>

            <div class="form-group">
                <label>Name</label>
                <input type="text" class="tw-form-input" value="{{ $lateArrival->employee->name ?? '-' }}" disabled>
            </div>

            <div class="form-group">
                <label>Scheduled Start</label>
                <input type="time" class="tw-form-input" name="scheduled_start" required
                    value="{{ \Carbon\Carbon::parse($lateArrival->scheduled_start)->format('H:i') }}">
            </div>

            <div class="form-group">
                <label>Actual Check In</label>
                <input type="time" class="tw-form-input" name="actual_check_in" required
                    value="{{ \Carbon\Carbon::parse($lateArrival->actual_check_in)->format('H:i') }}">
            </div>

            <div class="form-group">
                <label>Late Reason</label>
                <textarea name="late_reason" class="tw-form-input" required>{{ $lateArrival->late_reason }}</textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="tw-btn-primary">Update</button>
                <button type="button" class="tw-btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    const lateTable = $('#late-table').DataTable();

    // ✅ Update Late Arrival - inside edit modal
    $(document).on('submit', '#update-checkin', function (event) {
        event.preventDefault();

        const id = $('input[name=late_id]').val();

        $.ajax({
            url: '/admin/late-arrivals/' + id,
            method: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            success: function (res) {
                if (res.success) {
                    toastr.success('Late record updated');
                    $('#editLateModal').modal('hide');
                    lateTable.ajax.reload(null, false); // ✅ refresh without page reset
                } else {
                    toastr.warning('No changes made.');
                }
            },
            error: function () {
                toastr.error('Failed to update late arrival');
            }
        });
    });
</script>