<div class="modal-header">
    <h4 class="modal-title"><b>Update Leave Request</b></h4>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>
</div>

<div class="modal-body">
    <div class="card-body text-left">
        <form method="POST" id="update-leaverequest">
            @csrf
            @if($data['type'] != 5)
            <p><b>{{$data['employee']['name']}}</b> Submit a {{ $data['type'] == 2 ? "Fullday leave" : "short leave" }} request Kindly Take Action</p>
            @endif
            <input type="hidden" name="leave_id" id="leave_id" value="{{ $data['id'] }}">
            <input type="hidden" name="emp_id" id="emp_id" value="{{$data['employee']['id']}}">
            <input type="hidden" name="user_id" id="user_id" value="{{$data['employee']['user_id']}}">

            <input type="hidden" name="action" id="action" value="approve">
            <div class="form-group ">
                <label for="leave_type" class="col-sm-3 control-label no-padding">Type</label>
                <select class="form-control" onchange="changeLeaveType(this)" id="leave_type" name="leave_type" required {{ $data['type'] == 3 || $data['type'] == 5 ? "" : "disabled" }}>
                    <option value="" selected>- Select Type -</option>
                    <option value="1" {{ $data['type'] == 1 ? "selected" : "" }}>Half Day</option>
                    <option value="2" {{ $data['type'] == 2 ? "selected" : "" }}>Leave</option>
                    <option value="3" {{ $data['type'] == 3 ? "selected" : "" }}>Absent</option>
                    <!-- <option value="5" {{ $data['type'] == 5 ? "selected" : "" }}>Present</option> -->

                </select>
            </div>
            @if($data['type'] == 1)
            <div class="form-group leave_date">
                <label for="leave_date">leave Date</label>
                <div class="bootstrap-datepicker">
                    <input type="Date" class="form-control datepicker" id="leave_date" name="leave_date" required value="{{ $data['leave_date'] }}" disabledd required>
                </div>
            </div>
            <div class="form-group check_out">
                <label for="check_out">CheckOut Time</label>
                <div class="bootstrap-timepicker">
                    <input type="time" class="form-control timepicker" id="check_out" name="check_out" required value="{{date('H:i', strtotime(Date('Y-m-d').' '.$data['leave_time']))}}" required>
                </div>
            </div>

            @elseif($data['type'] == 2 || $data['type'] == 3)

            <div class="form-group leave_date">
                <label for="leave_date">leave Date</label>
                <div class="bootstrap-datepicker">
                    <input type="Date" class="form-control datepicker" id="leave_date" name="leave_date" required value="{{ $data['leave_date'] }}" required>
                </div>
            </div>
            @endif
            <div class="form-group leave-container">
                <label for="leave_reason">leave Reason</label>
                <textarea class="with-border" placeholder="Submit Reason..." name="leave_reason" id="leave_reason" cols="7" required="">{{$data['leave_reason']}}</textarea>
            </div>
            <div class="form-group">
                <div>
                    <span class="leave-container">
                        <button type="submit" class="btn btn-primary waves-effect leave_approve_btn waves-light">
                            Approve!
                        </button>
                        <button type="reset" class="btn btn-secondary leave_disapprove_btn waves-effect m-l-5">
                            Disapprove!
                        </button>
                    </span>
                    <!-- <span class="mark-btn" style="display: none;">
                        <button type="button" class="btn btn-primary waves-effect waves-light present_btn">
                            Mark
                        </button>
                    </span> -->
                </div>
            </div>
        </form>
    </div>
</div>
<script>
   

    function changeLeaveType(e) {
        const {
            value,
            name
        } = e;
        console.log(value);
        console.log(name);
    }


    $('#update-leaverequest').submit(function(event) {
        $('#action').val('approve')
        event.preventDefault();
        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('leaveAction') }}",
            method: "POST",
            data: formData,
            success: function(resp) {
                if (resp == 'Approved') {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Approved',
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
    $('.leave_disapprove_btn').on('click', function() {
        $('#action').val('dissapprove')
        let formData = $('#update-leaverequest').serialize();
        $.ajax({
            url: "{{ route('leaveAction') }}",
            method: "POST",
            data: formData,
            success: function(resp) {
                if (resp == 'Dissapproved') {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'info',
                        title: 'Dissapproved',
                        showConfirmButton: false,
                        timer: 500
                    })
                    setTimeout(function() { // wait for 5 secs(2)
                        location.reload(); // then reload the page.(3)
                    }, 500)
                }
            }
        });
    })
</script>