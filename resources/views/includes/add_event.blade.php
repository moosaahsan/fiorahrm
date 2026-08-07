<!-- Add -->
<div class="modal fade " id="addevent">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><b>Add Event</b></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="card-body text-left">
                    <form method="POST" id="add-event" action="{{ route('upcoming-events.store') }}">
                        @csrf
                        <ul class="error-container">
                        </ul>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter Event Title" id="title" name="title" required />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="event_type">Event Type</label>
                                    <select class="form-control" id="event_type" name="event_type" required>
                                        <option value="1" selected>Holiday</option>
                                        <option value="0">Celebration</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="event_held_on">Select Event Date</label>
                                    <div class="bootstrap-datepicker">
                                        <input type="Date" class="form-control datepicker" min="{{ Date('Y-m-d') }}" id="event_held_on" name="event_held_on" required value="{{ Date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="emp_id">Select Employee</label>
                                    <select class="form-control" id="emp_id" name="emp_id">
                                        <option value="" selected>--Select--</option>
                                        @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="descrption">Description</label>
                                    <textarea class="with-border" placeholder="Submit Description..." name="descrption" id="descrption" cols="7" required=""></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="reset" class="btn btn-secondary waves-effect m-l-5" data-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                Submit
                            </button>
                        </div>
                    </form>

                </div>
            </div>


        </div>

    </div>
</div>


<script>
    $('#add-event').submit(function(event) {
        event.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('upcoming-events.store') }}",
            method: "POST",
            data: formData,
            success: function(resp) {
                const obj = JSON.parse(resp);
                if (obj.status == 'success') {
                    $('.error-container').empty();
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: obj.status,
                        text: obj.msg,
                        showConfirmButton: false,
                        timer: 500
                    })
                    setTimeout(function() { // wait for 5 secs(2)
                        location.reload(); // then reload the page.(3)
                    }, 500)
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 422) {
                    var errorData = xhr.responseJSON;
                    // Process the error data
                    console.log(errorData);
                    // Display error messages to the user
                    var errorMessages = errorData.errors;
                    for (var field in errorMessages) {
                        if (errorMessages.hasOwnProperty(field)) {
                            var errorMessage = errorMessages[field];
                            var $errorItem = $('<li>' + field + ': ' + errorMessage + '</li>');
                            $('.error-container').append($errorItem);
                            // You can display the error message to the user using your preferred UI approach
                        }
                    }
                } else {
                    // Handle other error scenarios
                    console.log(error);
                    var $errorItem = $('<li>' + error + '</li>');
                    $('.error-container').append($errorItem);
                }
            }
        });
    });
</script>