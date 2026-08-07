<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>
</div>
<h4 class="modal-title"><b>Update Check In</b></h4>
<div class="modal-body">
    @foreach ($data as $item)
    @endforeach
    <div class="card-body text-left">
        <form method="POST" id="update-checkin">
            @csrf
            <input type="hidden" name="late_id" id="late_id" value="{{ $data['id'] }}">
            <div class="form-group">
                <label for="emp_id">Employee ID</label>
                <input type="text" class="form-control" placeholder="Enter Employee Id" id="emp_id" name="emp_id"
                    value="{{ $data['employee']['id'] }}" required  disabled/>
            </div>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" placeholder="Enter Employee Name" id="name"
                    name="name" value="{{ $data['employee']['name'] }}" required  disabled/>
            </div>
            <div class="form-group">
                <label for="check_in">Check In</label>
                <div class="bootstrap-timepicker">
                    <input type="time" class="form-control timepicker" id="check_in" name="check_in" required
                        value="{{date('H:i', strtotime(Date('Y-m-d').' '.$data['attendance']['attendance_time']))}}" required>
                </div>
            </div>
            <div class="form-group">
                <label for="reason">Reason</label>
                <textarea class="with-border" placeholder="Late Reason" name="reason" id="reason" cols="7"  required="">{{ $data['reason'] }}</textarea>

            </div>
            <div class="form-group">
                <div>
                    <button type="submit" class="btn btn-primary waves-effect waves-light">
                        Update
                    </button>
                    <button type="reset" class="btn btn-secondary waves-effect m-l-5" data-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $('#update-checkin').submit(function(event) {
        event.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('update_checkIn') }}",
            method: "POST",
            data: formData,
            success: function(resp) {
                if (resp == 'success') {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Updated',
                        showConfirmButton: false,
                        timer: 500
                    })
                    setTimeout(function(){// wait for 5 secs(2)
                               location.reload(); // then reload the page.(3)
                    }, 500)
                }
            }
        });
    });
</script>
