<div class="col-md-6">
    <div class="container">
        <div class="permission-manager">
            <div class="card bg-light">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title">Manage Permissions</h2>
                    <p class="card-title-desc">Empower users with the right permissions</p>
                </div>
                <div class="card-body">
                    <div class="role-list">
                        <h3 class="role-list-title">Available Roles</h3>
                        <div class="row mb-3">
                            @foreach(get_roles() as $role)
                            <div class="col-md-6 mb-3">
                                <div class="role-card p-3 border rounded">
                                    <span class="role-name">{{$role->name}}</span>
                                    <div class="permissions-list mt-3">
                                        @foreach(get_permissions() as $permission)
                                        <label class="permission-checkbox">
                                            <input onclick="setPermission('<?= $permission->name ?>',<?= $role->id ?>,this)" id="<?= $permission->id ?>" type="checkbox" class="permission-input" {{ in_array($permission->name, explode(',', $role->permissions)) ? 'checked' : '' }}>
                                            <span class="checkmark"></span>
                                            {{ucwords(removeUndersquareCapitalize($permission->name))}}
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function setPermission(permission_name, role_id, _element) {
        $.ajax({
            url: "{{ route('update.permission') }}",
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                name: permission_name,
                id: role_id,
                isAssign: _element.checked
            },
            success: function(resp) {
                if (resp.status === 'success') {
                    $('.error-container').empty();
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: resp.status,
                        text: resp.msg,
                        showConfirmButton: false,
                        timer: 500
                    })
                    setTimeout(function() { // wait for 5 secs(2)
                        location.reload(); // then reload the page.(3)
                    }, 500)
                }
            }
        });
    }
</script>