@php
$today = Date('Y-m-d')
@endphp
<!-- Add -->
<div class="modal fade" id="addnew">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><b>Leave Request</b></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>

            </div>
            
            <div class="modal-body">

                <div class="card-body text-left">
                    <form method="POST" id="add_leave" action="{{ route('employees.store') }}">
                        @csrf
                        <div class="form-group ">
                            <label for="emp_id" class="col-sm-4 control-label no-padding">Employee Name</label>
                              <select class="form-control" id="emp_id" name="emp_id" required>
                                <option value="" selected>- Select Employee -</option>
                                @foreach ($employees as $employee)
                                    <option value="{{$employee->id}}">{{$employee->name.'--> '.'AST-'.$employee->id}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group ">
                            <label for="leave_type" class="col-sm-3 control-label no-padding">leave Type</label>
                            <select class="form-control" id="leave_type" name="leave_type" required>
                                <option value="" selected>- Select Type -</option>
                                <option value="1">Half Day</option>
                                <option value="2">Full Leave</option>
                            </select>
                        </div>
                        <div class="form-group check_out" style="display:none">
                            <label for="check_out">CheckOut Time</label>
                            <div class="bootstrap-timepicker">
                                <input type="time" class="form-control timepicker" id="check_out" name="check_out" required value="{{ date('H:i', strtotime(Date('Y-m-d H:i:s'))) }}" required>
                            </div>
                        </div>
                        <div class="form-group leave_date">
                            <label for="leave_date">leave Date</label>
                            <div class="bootstrap-datepicker">
                                <input type="Date" class="form-control datepicker" id="leave_date" name="leave_date" required value="{{ Date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="leave_reason">leave Reason</label>
                            <textarea class="with-border" placeholder="Submit Reason..." name="leave_reason" id="leave_reason" cols="7" required=""></textarea>
                        </div>
                        <div class="form-group">
                            <div>
                                <button type="submit" class="btn btn-primary waves-effect waves-light">
                                    Submit
                                </button>
                                <button type="reset" class="btn btn-secondary waves-effect m-l-5" data-dismiss="modal">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    $('#leave_type').on('change', function() {
        if ($(this).val() == 1) {
            $('.check_out').css('display', 'block')
            // $('.leave_date').css('display', 'none')
        } else if ($(this).val() == 2) {
            $('.check_out').css('display', 'none')
            $('.leave_date').css('display', 'block')
        }
    })
    $('#add_leave').submit(function(event) {
        event.preventDefault();
        console.log('Form Submitted')
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('addleave') }}",
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
                    setTimeout(function() { // wait for 5 secs(2)
                        location.reload(); // then reload the page.(3)
                    }, 500)
                }
            }
        });
    });
</script>