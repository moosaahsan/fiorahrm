<div class="modal-header bg-light border-bottom-0 pb-3">
    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="importEmployeesModalLabel">
        <i class="bi bi-people-fill text-primary fs-4"></i> Bulk Import Employees
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
</div>

<form id="importEmployeesForm" enctype="multipart/form-data">
    @csrf
    <div class="modal-body py-4">

        <div class="alert alert-info border-0 rounded-3 d-flex align-items-start mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-3 fs-5 mt-1"></i>
            <div>
                <strong>How to import?</strong><br>
                1. Download the template file below.<br>
                2. Fill in one row per employee (Shift Name must match an existing shift, e.g. Morning).<br>
                3. Save the file as a <code>.csv</code> and upload it here.<br>
                Passwords are generated automatically for every account and shown to you once the import finishes — save them to share with each employee.
            </div>
        </div>

        <div class="mb-4 text-center">
            <a href="{{ route('admin.employees.import.template') }}" class="btn btn-outline-primary rounded-pill px-4 fw-medium">
                <i class="bi bi-download me-2"></i> Download CSV Template
            </a>
        </div>

        <div class="mb-3">
            <label for="import_file" class="form-label fw-bold">Upload CSV File <span class="text-danger">*</span></label>
            <input type="file" class="form-control form-control-lg bg-light" id="import_file" name="import_file" accept=".csv" required>
            <div class="form-text mt-2 text-muted">Only CSV format is supported. Max file size: 5MB.</div>
        </div>

        <div id="importEmployeesErrorsContainer" class="d-none mt-4">
            <div class="alert alert-danger mb-0 rounded-3 border-0">
                <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i> Import Errors</div>
                <ul id="importEmployeesErrorList" class="mb-0 ps-3 small text-danger" style="max-height: 150px; overflow-y: auto;">
                </ul>
            </div>
        </div>

        <div id="importEmployeesCreatedContainer" class="d-none mt-4">
            <div class="alert alert-success border-0 rounded-3">
                <div class="fw-bold mb-2"><i class="bi bi-check-circle-fill me-2"></i> Accounts Created — save these credentials now, they won't be shown again</div>
                <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                    <table class="table table-sm table-bordered bg-white mb-0">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Temp Password</th></tr>
                        </thead>
                        <tbody id="importEmployeesCreatedList"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium" id="btnImportEmployeesSubmit">
            <i class="bi bi-cloud-arrow-up me-1"></i> Start Import
        </button>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('#importEmployeesForm').off('submit').on('submit', function (e) {
            e.preventDefault();
            const btn = $('#btnImportEmployeesSubmit');
            const originalText = btn.html();
            const formData = new FormData(this);

            btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing...').prop('disabled', true);
            $('#importEmployeesErrorsContainer').addClass('d-none');
            $('#importEmployeesErrorList').empty();
            $('#importEmployeesCreatedContainer').addClass('d-none');
            $('#importEmployeesCreatedList').empty();

            $.ajax({
                url: "{{ route('admin.employees.import.store') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    if (res.created && res.created.length > 0) {
                        $('#importEmployeesCreatedContainer').removeClass('d-none');
                        res.created.forEach(function (c) {
                            $('#importEmployeesCreatedList').append(
                                '<tr><td>' + c.name + '</td><td>' + c.email + '</td><td><code>' + c.password + '</code></td></tr>'
                            );
                        });
                    }

                    if (res.success) {
                        toastr.success(res.message, 'Import Successful');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload(null, false);
                        }
                        $('#importEmployeesForm')[0].reset();
                    } else {
                        toastr.error(res.message || 'Import completed with some errors.', 'Partial/Failed Import');
                        if (res.errors && res.errors.length > 0) {
                            $('#importEmployeesErrorsContainer').removeClass('d-none');
                            res.errors.forEach(function (err) {
                                $('#importEmployeesErrorList').append('<li>' + err + '</li>');
                            });
                        }
                    }
                },
                error: function (xhr) {
                    toastr.error('A server error occurred during import.', 'Error');
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        $('#importEmployeesErrorsContainer').removeClass('d-none');
                        $('#importEmployeesErrorList').append('<li>' + xhr.responseJSON.message + '</li>');
                    }
                },
                complete: function () {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
