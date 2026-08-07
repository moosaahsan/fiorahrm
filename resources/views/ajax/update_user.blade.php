<div class="modal-header">
    <h4 class="modal-title"><b><span class="employee_id">Update User</span></b></h4>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>
</div>
@php
    $roles = get_roles();
    foreach ($data['roles'] as $role) {
        $r = $role;
    }
@endphp

<div class="modal-body text-left">
    <form method="POST" action="{{ route('users.update', $data['id']) }}" onsubmit="validateonsubmit(event, id, this)">
        @csrf
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" name="user_id" id="user_id" value="{{ $data['id'] }}">
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label for="name" class="col-sm-3 control-label no-padding">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $data['name'] }}"
                        required>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="schedule" class="col-sm-3 control-label no-padding">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="" selected>- Select -</option>
                        @foreach ($roles as $role)
                            <option {{ $r['name'] == $role->name ? ' selected ' : '' }} value="{{ $role->name }}">
                                {{ $role->name }}</option>
                            
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="email" class="col-sm-3 control-label no-padding">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ $data['email'] }}">
        </div>
        <div class="form-group">
            <label for="password" class="col-sm-3 control-label no-padding">Password</label>
            <input type="password" class="form-control" id="password"name="password">
        </div>


        <div class="modal-footer">
            <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i
                    class="fa fa-close"></i>
                Close</button>
            <button type="submit" class="btn btn-success btn-flat" name="edit"><i class="fa fa-check-square-o"></i>
                Update</button>
    </form>
</div>
</div>
<script>
    function validateonsubmit(e, id, form) {
        e.preventDefault();
        var isValid = true;
        console.log(form)
        if (isValid) {
            form.submit();
        }
    }
</script>
