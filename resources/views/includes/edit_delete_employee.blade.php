<!-- Shared Edit Modal -->
<div class="modal fade" id="editEmployeeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="edit-employee-form" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PUT" />
                <div class="modal-header">
                    <h4 class="modal-title"><b>Edit Employee</b></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="edit-modal-body">
                    <!-- Fields will be populated via JS -->
                    <p class="text-muted">Loading...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Delete -->
<div class="modal fade" id="delete{{ $employee->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header " style="align-items: center">

                <h4 class="modal-title "><span class="employee_id">Delete Employee</span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal delete-employee-form" id="delete-employee-{{ $employee->id }}"
                    method="POST" action="{{ route('employees.destroy', $employee->id) }}">
                    @csrf
                    {{ method_field('DELETE') }}
                    <div class="text-center">
                        <h6>Are you sure you want to delete:</h6>
                        <h2 class="bold del_employee_name">{{$employee->name}}</h2>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i
                        class="fa fa-close"></i> Close</button>
                <button type="submit" class="btn btn-danger btn-flat delete-employee-btn" data-id="{{ $employee->id }}">
                    <i class="fa fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).on('click', '.trigger_ajax_modal[data-action="edit"]', function () {
        const id = $(this).data('id');
        const form = $('#edit-employee-form');
        const modal = $('#editEmployeeModal');

        $.ajax({
            url: '/admin/employee_data/' + id,
            type: 'GET',
            dataType: 'json',
            success: function (employee) {
                // Dynamically build the form content
                let html = `
                <input type="hidden" name="employee_id" value="${employee.id}">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="${employee.name}" required>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" class="form-control" value="${employee.position}" required>
                </div>
                <div class="form-group">
                    <label>Date of Joining</label>
                    <input type="date" name="joining_date" class="form-control" value="${employee.joining_date}" required>
                </div>
                <div class="form-group">
                    <label>Contact No</label>
                    <input type="tel" name="contact_no" class="form-control" value="${employee.contact_no}" required>
                </div>
                <div class="form-group">
                    <label>Emergency Contact No</label>
                    <input type="tel" name="emergency_no" class="form-control" value="${employee.emergency_no}" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="${employee.email}" required>
                </div>
                <div class="form-group">
                    <label>Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>
            `;

                $('#edit-modal-body').html(html);
                form.attr('action', `/admin/employees/${employee.id}`);
                modal.modal('show');
            },
            error: function () {
                alert('Failed to load employee data.');
            }
        });
    });

    $('#edit-employee-form').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);
        let actionUrl = form.attr('action');

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: form.serialize(),
            success: function () {
                $('#editEmployeeModal').modal('hide');
                Swal.fire('Updated!', 'Employee info updated.', 'success');
                $('#employees-table').DataTable().ajax.reload();
            },
            error: function () {
                alert('Failed to update employee.');
            }
        });
    });

</script>