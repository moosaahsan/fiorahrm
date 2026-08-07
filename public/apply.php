<?php
/**
 * AllStar BPO - Career Portal Standalone Form
 * 
 * Instructions:
 * 1. Upload this file to your cPanel (e.g., allstarbpo.com/apply.php)
 * 2. Update the $HRM_URL variable below with your actual HRM domain.
 */

// --- CONFIGURATION ---
$HRM_URL = "https://hrm.writersplanet.net"; // CHANGE THIS to your HRM URL (e.g. https://hrm.writersplanet.net)
// ---------------------

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join AllStar BPO | Careers</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: radial-gradient(circle at top right, rgba(79, 70, 229, 0.05), transparent),
                        radial-gradient(circle at bottom left, rgba(79, 70, 229, 0.05), transparent);
        }

        .apply-card {
            background: var(--card-bg);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .card-header-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
        }

        .card-header-gradient h1 {
            font-weight: 800;
            font-size: 2.2rem;
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }

        .card-header-gradient p {
            opacity: 0.8;
            font-weight: 500;
            font-size: 1rem;
        }

        .form-body {
            padding: 3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-main);
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control-saas {
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            width: 100%;
            color: var(--text-main);
        }

        .form-control-saas:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .file-upload-wrapper {
            position: relative;
            width: 100%;
            height: 120px;
            border: 2px dashed var(--border);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            background: #fcfdfe;
        }

        .file-upload-wrapper:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.02);
        }

        .btn-apply {
            background: var(--primary);
            color: white;
            font-weight: 800;
            width: 100%;
            padding: 16px;
            border-radius: 18px;
            border: none;
            font-size: 1.1rem;
            margin-top: 1rem;
            transition: 0.3s;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .btn-apply:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .invalid-feedback {
            font-weight: 600;
            font-size: 0.75rem;
            margin-top: 5px;
            color: #ef4444;
        }
    </style>
</head>
<body>

    <div class="apply-card">
        <div class="card-header-gradient">
            <h1>Join AllStar BPO</h1>
            <p>Ready to start your professional journey with us?</p>
        </div>

        <div class="form-body">
            <form id="apply-form" enctype="multipart/form-data">
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
                            <input type="text" name="reference" class="form-control-saas" placeholder="Name of person">
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
                    <textarea name="address" class="form-control-saas" rows="2" placeholder="Your residential address..."></textarea>
                    <div class="invalid-feedback address-error"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Resume / CV (PDF/Doc)</label>
                    <div class="file-upload-wrapper" id="drop-zone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="file-name">Click or drag and drop your file here</span>
                        <input type="file" id="cv-input" name="cv" accept=".pdf,.doc,.docx" required style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                    </div>
                    <div class="invalid-feedback cv-error d-block"></div>
                </div>

                <button type="submit" class="btn btn-apply" id="submit-btn">
                    Submit Application
                </button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const HRM_API = "https://hrm.writersplanet.net/api/external/apply";

            $('#cv-input').change(function() {
                let fileName = $(this).val().split('\\').pop();
                $('#file-name').text(fileName ? fileName : 'Click or drag and drop your file here');
            });

            $('#apply-form').on('submit', function(e) {
                e.preventDefault();
                let btn = $('#submit-btn');
                
                $('.invalid-feedback').text('').removeClass('d-block');
                btn.prop('disabled', true).text('Processing...');

                let formData = new FormData(this);

                $.ajax({
                    url: HRM_API,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: response.message,
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Submit Application');

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $(`.${key}-error`).text(value[0]).addClass('d-block');
                            });
                        } else if (xhr.status === 429) {
                            Swal.fire({ icon: 'error', title: 'Too many requests', text: 'Please wait a minute.' });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not connect to HRM server.' });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
