<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>

</div>
<h4 class="modal-title"><b>Update Clock Time</b></h4>
<div class="modal-body">
    <div class="card-body text-left">
        <form method="POST" id="update_checkinout">
            @csrf
            <input type="hidden" id="attendance_id" name="attendance_id" value="{{$data['check']['attendance_id']}}">
            <div class="form-group">
                <label for="name" class="col-sm-3 control-label no-padding">Name</label>
                <div class="bootstrap-timepicker">
                    <input disabled type="text" class="form-control timepicker" id="name" name="name" value="{{$data['employee']['name']}}">
                </div>

            </div>
            <div class="form-group">
                <label for="edit_time_in" class="col-sm-3 control-label no-padding">Time In</label>
                <div class="bootstrap-timepicker">
                    <input type="time" class="form-control timepicker" id="edit_time_in" name="time_in" value="{{ \Carbon\Carbon::parse($data['check']['attendance_time'])->format('H:i:s') }}">
                </div>

            </div>
            <div class="form-group">
                <label for="leave_time" class="col-sm-3 control-label no-padding">Time out</label>
                <div class="bootstrap-timepicker">
                    <input type="time" class="form-control timepicker" id="time_out" name="time_out" value="{{ \Carbon\Carbon::parse($data['check']['leave_time'])->format('H:i:s') }}">
                </div>
            </div>

            <div class="form-group">



                <select class="form-control" id="status" name="status" required>
                    <option value="" selected>- Select Type -{{$data['employee']['status']}}</option>
                    <option {{ $data['attendance']['status'] == 0 ? 'selected' : '' }} value="0">Mark Late</option>
                    <option {{ $data['attendance']['status'] == 1 ? 'selected' : '' }} value="1">Mark On Time</option>
                    <option {{ $data['attendance']['status'] == 2 ? 'selected' : '' }} value="2">Half Day</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-flat pull-left" data-dismiss="modal"><i class="fa fa-close"></i>
                    Close</button>
                <button type="submit" class="btn btn-success btn-flat"><i class="fa fa-check-square-o"></i>
                    Update</button>

            </div>
        </form>
    </div>
</div>

<script>
    $('#update_checkinout').submit(function(event) {
        event.preventDefault();
        let formData = $(this).serialize();
        let id = $('#attendance_id').val();
        // alert(id);
        $.ajax({
            url: `{{ route('update-clock-in') }}`,
            method: "POST",
            data: formData,
            success: function(resp) {
                console.log(resp);
                if (JSON.parse(resp) == 'success') {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Updated',
                        showConfirmButton: false,
                        timer: 500
                    })
                    setTimeout(function() { // wait for 5 secs(2)
                        location.reload(); // then reload the page.(3)
                    }, 500)
                }
            }
        });
    });
</script>