<div class="modal-header bg-light border-bottom-0 pb-3">
    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="importModalLabel">
        <i class="bi bi-file-earmark-arrow-up text-primary fs-4"></i> Bulk Import Attendance
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<form id="importAttendanceForm" enctype="multipart/form-data">
    @csrf
    <div class="modal-body py-4">
        
        <div class="alert alert-info border-0 rounded-3 d-flex align-items-start mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-3 fs-5 mt-1"></i>
            <div>
                <strong>How to import?</strong><br>
                1. Download the template file below.<br>
                2. Fill in the required columns (Employee Name, Date, Check-in Time, Check-out Time, Work Status).<br>
                3. Save the file as a <code>.csv</code> and upload it here.
            </div>
        </div>

        <div class="mb-4 text-center">
            <a href="{{ route('admin.attendance.import.template') }}" class="btn btn-outline-primary rounded-pill px-4 fw-medium">
                <i class="bi bi-download me-2"></i> Download CSV Template
            </a>
        </div>

        <div class="mb-3">
            <label for="import_file" class="form-label fw-bold">Upload CSV File <span class="text-danger">*</span></label>
            <input type="file" class="form-control form-control-lg bg-light" id="import_file" name="import_file" accept=".csv" required>
            <div class="form-text mt-2 text-muted">Only CSV format is supported. Max file size: 5MB.</div>
        </div>
        
        <div id="importErrorsContainer" class="d-none mt-4">
            <div class="alert alert-danger mb-0 rounded-3 border-0">
                <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i> Import Errors</div>
                <ul id="importErrorList" class="mb-0 ps-3 small text-danger" style="max-height: 150px; overflow-y: auto;">
                    <!-- Errors injected via JS -->
                </ul>
            </div>
        </div>

    </div>

    <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium" id="btnImportSubmit">
            <i class="bi bi-cloud-arrow-up me-1"></i> Start Import
        </button>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('#importAttendanceForm').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const btn = $('#btnImportSubmit');
            const originalText = btn.html();
            const formData = new FormData(this);

            btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importing...').prop('disabled', true);
            $('#importErrorsContainer').addClass('d-none');
            $('#importErrorList').empty();

            $.ajax({
                url: "{{ route('admin.attendance.import.store') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message, 'Import Successful');
                        $('#ajaxModal').modal('hide');
                        if (typeof table !== 'undefined') {
                            table.ajax.reload(null, false);
                        }
                    } else {
                        const title = res.message ? res.message : 'Partial/Failed Import';
                        toastr.error(res.message || 'Import completed with some errors.', title);
                        
                        // Show errors
                        if (res.errors && res.errors.length > 0) {
                            $('#importErrorsContainer').removeClass('d-none');
                            res.errors.forEach(function(err) {
                                $('#importErrorList').append('<li>' + err + '</li>');
                            });
                        }
                    }
                },
                error: function (xhr) {
                    toastr.error('A server error occurred during import.', 'Error');
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        $('#importErrorsContainer').removeClass('d-none');
                        $('#importErrorList').append('<li>' + xhr.responseJSON.message + '</li>');
                    }
                },
                complete: function () {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
