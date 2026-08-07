<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Our Team | Careers</title>
    @vite(['resources/css/public-apply.css', 'resources/css/bs4-compat.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</head>
<body class="public-apply-page">

    <div class="apply-card">
        <div class="card-header-gradient">
            <h1>Join Our Journey</h1>
            <p>Fill out the form below to start your application</p>
        </div>

        <div class="form-body">
            <form id="apply-form" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control-saas" placeholder="John Doe" required>
                            <div class="invalid-feedback name-error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control-saas" placeholder="john@example.com" required>
                            <div class="invalid-feedback email-error"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control-saas" placeholder="+92 3XX XXXXXXX" required>
                            <div class="invalid-feedback phone-error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">CNIC Number</label>
                            <input type="text" name="cnic" class="form-control-saas" placeholder="42101-XXXXXXX-X" required>
                            <div class="invalid-feedback cnic-error"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Total Experience</label>
                            <input type="text" name="experience" class="form-control-saas" placeholder="e.g. 2 Years" required>
                            <div class="invalid-feedback experience-error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Reference (Optional)</label>
                            <input type="text" name="reference" class="form-control-saas" placeholder="Name of person who referred you">
                            <div class="invalid-feedback reference-error"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">How did you know about us?</label>
                    <select name="source" class="form-control-saas" required>
                        <option value="">Select an option</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="Facebook/Instagram">Facebook/Instagram</option>
                        <option value="Indeed">Indeed</option>
                        <option value="Referral">Referral</option>
                        <option value="Other">Other</option>
                    </select>
                    <div class="invalid-feedback source-error"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Address</label>
                    <textarea name="address" class="form-control-saas" rows="2" placeholder="Your current resident address..."></textarea>
                    <div class="invalid-feedback address-error"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Resume / CV (PDF/Doc)</label>
                    <div class="file-upload-wrapper" id="drop-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="file-name">Click or drag and drop your file here</span>
                        <input type="file" id="cv-input" name="cv" accept=".pdf,.doc,.docx" required>
                    </div>
                    <div class="invalid-feedback cv-error d-block"></div>
                    <small class="text-muted mt-1 d-block">Max file size: 5MB</small>
                </div>

                <button type="submit" class="btn btn-apply" id="submit-btn">
                    <span class="spinner-border spinner-border-sm d-none mr-2" role="status" aria-hidden="true"></span>
                    Submit Application
                </button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // File input name display
            $('#cv-input').change(function() {
                let fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    $('#file-name').text(fileName).addClass('text-primary');
                    $('#drop-zone').addClass('border-primary');
                } else {
                    $('#file-name').text('Click or drag and drop your file here').removeClass('text-primary');
                    $('#drop-zone').removeClass('border-primary');
                }
            });

            // Form submission
            $('#apply-form').on('submit', function(e) {
                e.preventDefault();
                
                let btn = $('#submit-btn');
                let spinner = btn.find('.spinner-border');
                
                // Reset errors
                $('.invalid-feedback').text('').removeClass('d-block');
                $('.form-control-saas').removeClass('is-invalid');

                // Prepare data
                let formData = new FormData(this);
                
                // UI State
                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: "{{ route('interviews.storePublic') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonColor: '#4f46e5',
                                confirmButtonText: 'Great!'
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $(`.${key}-error`).text(value[0]).addClass('d-block');
                                $(`[name="${key}"]`).addClass('is-invalid');
                            });
                        } else if (xhr.status === 429) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Too Many Attempts',
                                text: 'Please wait a minute before trying again.',
                                confirmButtonColor: '#4f46e5'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong. Please try again later.',
                                confirmButtonColor: '#4f46e5'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
