<div class="col-md-6">
    <div class="container">
        <div class="permission-manager">
            <div class="card bg-light">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title">Add Permissions</h2>
                    <p class="card-title-desc">Empower users with the right permissions</p>
                </div>
                <div class="card-body">
                    <div class="role-list">
                        <h3 class="role-list-title">Available Permissions</h3>
                        <div class="row mb-3">

                            <div class="col-md-6 mb-3">
                                <ul>
                                    @foreach(get_permissions() as $permission)
                                    <li><span class="role-name">{{ucwords(removeUndersquareCapitalize($permission->name))}} <button class="btn btn-danger btn-sm rounded-1" onclick="deletePerm(<?= $permission->id ?>)">Delete</button></span> </li>
                                    @endforeach
                                </ul>
                            </div>

                        </div>
                    </div>
                    <div class="add-permission-form mt-4">
                        <h4>Add New Permission</h4>
                        <form id="permission_form" action="/add_permission" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <input id="permission_name" type="text" name="name" class="form-control mb-2" placeholder="Permission Name">
                                </div>
                                <div class="col-md-12">
                                    <span style="color: red;" id="permission_error"></span>
                                    <span style="color: green;" id="permission_success"></span>
                                </div>
                                <div class="col-md-6">
                                    <button onclick="addPermission()" type="button" class="btn btn-primary">Add Permission</button>
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
    function deletePerm(_id) {
        $.ajax({
            url: "{{ route('delete-perm') }}",
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                id: _id,
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

    function addPermission() {
        const permissionForm = document.getElementById("permission_form");
        const formData = new FormData(permissionForm);
        const permission_name = $("#permission_name").val();
        if (permission_name != '') {
            $("#permission_error").text("");
            $("#permission_success").text("");
            fetch("store.permission", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Handle the response, e.g., show success or error message
                    if (data.status === 200) {
                        $("#permission_success").text(data.msg);
                        $("#permission_name").val("");
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    $("#permission_error").text(data.error);
                });
        } else {
            $("#permission_error").text("Please enter value");
        }
    }
</script>