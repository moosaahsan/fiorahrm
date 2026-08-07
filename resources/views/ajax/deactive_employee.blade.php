<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>

</div>
<h4 class="modal-title"><b>Deactive Account</b></h4>
<div class="modal-body">
    <p class="text-center">{{$data['name']}}</p>
    <div class="card-body text-left">
        <form method="POST" id="deactive-account" action="{{ route('employees.store') }}">
            @csrf
            <input type="hidden" name="emp_id" id="emp_id" value="{{$data['id']}}">
            <div class="form-group">
                <label for="purpose">Purpose</label>
                <select class="form-control" id="purpose" name="purpose" required>
                    <option value="2">Submit Resign</option>
                    <option value="0">Deactive Account</option>
                </select>
            </div>
            <div class="form-group resign_date">
                <label for="resign_date">Resignation Date</label>
                <div class="bootstrap-datepicker">
                    <input type="Date" class="form-control datepicker" id="resign_date" name="resign_date" required
                        value="{{ Date('Y-m-d') }}" required>
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
    </div>
</div>
<script>
    $('#purpose').on('change', function() {
        if($(this).val()==0){
            $('.resign_date').css('display', 'none');
        } else if($(this).val()==2){
            $('.resign_date').css('display', 'block');
        }
    })
    $('#deactive-account').submit(function(event) {
        event.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('deactive.employee') }}",
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
