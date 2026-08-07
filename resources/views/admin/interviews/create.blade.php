@extends('admin.layouts.app')

@section('title', 'Record Walk-in Interview')

@section('breadcrumb')
    <div class="col-sm-6 text-left">
        <h4 class="page-title interviews-header">Record Walk-in Interview</h4>
        <ol class="breadcrumb saas-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.interviews.index') }}">Interviews</a></li>
            <li class="breadcrumb-item active">Walk-in</li>
        </ol>
    </div>
@endsection

@section('button')
    <a class="btn-premium-back" href="{{ route('admin.interviews.index') }}">
        <i class="fas fa-arrow-left mr-2"></i> Back to Directory
    </a>
@endsection

@section('content')

    <div class="container-fluid">
        <form id="record-interview-form" class="premium-form-container" enctype="multipart/form-data">
            @csrf

            <!-- Section 1: Candidate Identity -->
            <div class="saas-card">
                <div class="saas-section-header">
                    <div class="section-icon"><i class="fas fa-user-check"></i></div>
                    <h3 class="saas-section-title">Candidate Personal Identity</h3>
                </div>
                <div class="saas-form-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-signature"></i> Full Name</label>
                            <input type="text" class="saas-input" name="name" required
                                placeholder="Enter candidate's full name">
                        </div>
                        <div class="col-md-6 mb-4">
                            @if(auth()->user()->hasRole('receptionist'))
                                <input type="hidden" name="interview_type" value="Walk-in">
                                <label class="saas-label"><i class="fas fa-id-card"></i> CNIC / ID Number</label>
                                <input type="text" class="saas-input" id="cnic" name="cnic" required
                                    placeholder="Format: 37405-XXXXXXX-X">
                            @else
                                <label class="saas-label"><i class="fas fa-layer-group"></i> Interview Type</label>
                                <select class="saas-input" name="interview_type" required>
                                    <option value="Walk-in" selected>Walk-in</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Social Media">Social Media</option>
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="row" @if(!auth()->user()->hasRole('receptionist')) style="margin-top: -10px" @endif>
                        @if(!auth()->user()->hasRole('receptionist'))
                            <div class="col-md-12 mb-4">
                                <label class="saas-label"><i class="fas fa-id-card"></i> CNIC / ID Number</label>
                                <input type="text" class="saas-input" id="cnic" name="cnic" required
                                    placeholder="Format: 37405-XXXXXXX-X">
                            </div>
                        @endif

                        <div class="col-md-12">
                            <!-- Smart Alert for Duplicate CNIC (hidden until match) -->
                            <div class="history-card" id="cnic-history-alert" style="display: none;" aria-live="polite">
                                <div class="history-card-inner">
                                    <div class="history-card-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="history-card-body">
                                        <div class="history-card-title-row">
                                            <h4 class="history-card-title">Potential re-hire or duplicate alert</h4>
                                            <span class="history-card-source" id="h-source">Employee match</span>
                                        </div>
                                        <p class="history-card-desc">This CNIC already exists in the system. Review the profile before continuing.</p>
                                        <div class="history-card-grid">
                                            <div><span class="history-meta-label">Name</span><span id="h-name">---</span></div>
                                            <div><span class="history-meta-label">Position</span><span id="h-pos">---</span></div>
                                            <div><span class="history-meta-label">Joining / recorded</span><span id="h-join">---</span></div>
                                            <div><span class="history-meta-label">Status</span><span id="h-status" class="badge-saas badge-status-pending px-2">---</span></div>
                                            <div class="history-card-grid-full"><span class="history-meta-label">Team / branch</span><span id="h-team">---</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-phone-alt"></i> Phone Number</label>
                            <input type="text" class="saas-input" name="phone" required placeholder="e.g. 0300-1234567">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-at"></i> Email Address</label>
                            <input type="email" class="saas-input" name="email" placeholder="e.g. candidate@example.com">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-layer-group"></i> Job Category</label>
                            <select class="saas-input" name="category" id="edit-category">
                                <option value="BPO" selected>BPO</option>
                                <option value="Billing">Billing</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-search-dollar"></i> Job Vacancy (HRM
                                Database)</label>
                            <select class="saas-input" name="job_id" id="edit-job-id">
                                <option value="">No linked job vacancy</option>
                                @foreach($jobs as $job)
                                    <option value="{{ $job->id }}" data-category="{{ $job->category }}">
                                        {{ $job->title }} ({{ $job->category }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-4 manual-pos-wrapper">
                            <label class="saas-label"><i class="fas fa-id-card-alt"></i> Position Applied (Manual /
                                Fallback)</label>
                            <input type="text" class="saas-input" name="position_applied" placeholder="e.g. CSR">
                        </div>
                        <div class="col-md-12 mb-0">
                            <label class="saas-label"><i class="fas fa-map-marker-alt"></i> Current City</label>
                            <input type="text" class="saas-input" name="address"
                                placeholder="Enter city name (e.g. Rawalpindi)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Professional Details -->
            <div class="saas-card">
                <div class="saas-section-header">
                    <div class="section-icon" style="background:#f0f9ff; color:#0ea5e9"><i class="fas fa-briefcase"></i>
                    </div>
                    <h3 class="saas-section-title">Professional Background</h3>
                </div>
                <div class="saas-form-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-graduation-cap"></i> Highest Qualification</label>
                            <input type="text" class="saas-input" name="qualification" placeholder="e.g. BBA, MCS">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-history"></i> Total Experience</label>
                            <input type="text" class="saas-input" name="experience" placeholder="e.g. Fresh or 2 Years">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-user-friends"></i> Referral / Reference</label>
                            <input type="text" class="saas-input" name="reference"
                                placeholder="Enter reference name (optional)">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-bullhorn"></i> How did you know about us?</label>
                            <input type="text" class="saas-input" name="source" placeholder="e.g. Facebook, Indeed">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-comments"></i> Communication Skills</label>
                            <select class="saas-input" name="communication_skills">
                                <option value="">Select Level</option>
                                <option value="Poor">Poor</option>
                                <option value="Average">Average</option>
                                <option value="Good">Good</option>
                                <option value="Excellent">Excellent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="saas-label"><i class="fas fa-clock"></i> Interview Date & Time</label>
                            <input type="datetime-local" class="saas-input" name="interview_date">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Document Verification -->
            <div class="saas-card">
                <div class="saas-section-header">
                    <div class="section-icon" style="background:#fefce8; color:#eab308"><i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="saas-section-title">Enclosure & Verification</h3>
                </div>
                <div class="saas-form-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="saas-label">CV / Resume (PDF, Word)</label>
                            <div class="file-upload-wrapper" onclick="document.getElementById('cv-input').click()">
                                <div class="file-upload-icon"><i class="fas fa-file-pdf"></i></div>
                                <div class="font-weight-bold small text-dark">Click to Upload CV</div>
                                <div id="cv-name" class="file-name">No file chosen</div>
                                <input type="file" id="cv-input" name="cv" style="display:none" accept=".pdf,.doc,.docx"
                                    onchange="updateFileName(this, 'cv-name')">
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="saas-label">CNIC Front Side</label>
                            <div class="file-upload-wrapper" onclick="document.getElementById('front-input').click()">
                                <div class="file-upload-icon"><i class="fas fa-id-badge"></i></div>
                                <div class="font-weight-bold small text-dark">Upload Front Side</div>
                                <div id="front-name" class="file-name">No file chosen</div>
                                <input type="file" id="front-input" name="cnic_front" style="display:none"
                                    onchange="updateFileName(this, 'front-name')">
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="saas-label">CNIC Back Side</label>
                            <div class="file-upload-wrapper" onclick="document.getElementById('back-input').click()">
                                <div class="file-upload-icon"><i class="fas fa-id-badge"></i></div>
                                <div class="font-weight-bold small text-dark">Upload Back Side</div>
                                <div id="back-name" class="file-name">No file chosen</div>
                                <input type="file" id="back-input" name="cnic_back" style="display:none"
                                    onchange="updateFileName(this, 'back-name')">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Hub -->
            <div class="mt-5 text-center">
                <button type="submit" class="saas-btn-submit" id="submit-btn">
                    <i class="fas fa-rocket mr-2"></i> Register Walk-in Candidate
                </button>
                <p class="mt-4 text-muted small"><i class="fas fa-shield-alt mr-1"></i> Securely record candidate identity
                    into the HRM Intelligence Cloud.</p>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function updateFileName(input, targetId) {
            let name = input.files.length > 0 ? input.files[0].name : "No file chosen";
            document.getElementById(targetId).textContent = name;

            // Smooth transition for the wrapper
            if (input.files.length > 0) {
                $(input).closest('.file-upload-wrapper').css({
                    'border-color': 'var(--saas-success)',
                    'background': '#ecfdf5'
                });
            }
        }

        $(document).ready(function () {
            let cnicCheckTimer = null;
            let cnicCheckXhr = null;

            function statusBadgeClass(tone) {
                const map = {
                    hired: 'badge-status-hired',
                    rejected: 'badge-status-rejected',
                    onhold: 'badge-status-onhold',
                    pending: 'badge-status-pending'
                };
                return map[tone] || 'badge-status-pending';
            }

            function hideCnicAlert() {
                $('#cnic-history-alert').stop(true, true).fadeOut(150);
            }

            function showCnicAlert(res) {
                const emp = res.employee || {};
                $('#h-name').text(emp.name || '---');
                $('#h-pos').text(emp.position || '---');
                $('#h-join').text(emp.joining_date || '---');
                $('#h-team').text((emp.team || 'N/A') + ' / ' + (emp.branch || 'N/A'));
                $('#h-source').text(res.source === 'interview' ? 'Interview pipeline' : 'Employee directory');
                $('#h-status')
                    .text(emp.status || '---')
                    .attr('class', 'badge-saas px-2 ' + statusBadgeClass(emp.status_tone));
                $('#cnic-history-alert').stop(true, true).fadeIn(180);
            }

            function checkCnicDuplicate() {
                const val = ($('#cnic').val() || '').trim();
                const digits = val.replace(/\D+/g, '');

                if (digits.length < 5) {
                    hideCnicAlert();
                    return;
                }

                if (cnicCheckXhr) {
                    cnicCheckXhr.abort();
                }

                cnicCheckXhr = $.get("{{ route('admin.interviews.check-cnic') }}", { cnic: val })
                    .done(function (res) {
                        if (res && res.exists) {
                            showCnicAlert(res);
                        } else {
                            hideCnicAlert();
                        }
                    })
                    .fail(function (xhr) {
                        if (xhr.statusText !== 'abort') {
                            hideCnicAlert();
                        }
                    });
            }

            $('#cnic').on('input', function () {
                clearTimeout(cnicCheckTimer);
                cnicCheckTimer = setTimeout(checkCnicDuplicate, 350);
            }).on('blur', function () {
                clearTimeout(cnicCheckTimer);
                checkCnicDuplicate();
            });

            // Job Filtering & Manual Fallback Logic
            function filterJobs(category) {
                let hasJobs = false;
                $('#edit-job-id option').each(function () {
                    let jobCat = $(this).data('category');
                    if (jobCat === category) {
                        $(this).show();
                        hasJobs = true;
                    } else if (!jobCat) {
                        $(this).show(); // "No linked job vacancy" option
                    } else {
                        $(this).hide();
                    }
                });

                // If current selected job is filtered out, reset selection
                let selectedJob = $('#edit-job-id option:selected');
                if (selectedJob.val() !== "" && selectedJob.css('display') === 'none') {
                    $('#edit-job-id').val("");
                }

                toggleManualField();
            }

            function toggleManualField() {
                let jobId = $('#edit-job-id').val();
                if (!jobId) {
                    $('.manual-pos-wrapper').fadeIn();
                } else {
                    $('.manual-pos-wrapper').fadeOut();
                }
            }

            $('#edit-category').on('change', function () {
                filterJobs($(this).val());
            });

            $('#edit-job-id').on('change', function () {
                toggleManualField();
            });

            // Initial load
            filterJobs($('#edit-category').val());

            $('#record-interview-form').on('submit', function (e) {
                e.preventDefault();

                let formData = new FormData(this);
                let btn = $('#submit-btn');
                let originalContent = btn.html();

                btn.html('<i class="fas fa-circle-notch fa-spin"></i> Processing Entry...').attr('disabled', true);

                $.ajax({
                    url: "{{ route('admin.interviews.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.success) {
                            toastr.success(res.message);
                            setTimeout(() => {
                                window.location.href = "{{ route('admin.interviews.index') }}";
                            }, 1500);
                        }
                    },
                    error: function (err) {
                        btn.html(originalContent).attr('disabled', false);
                        if (err.status === 422) {
                            let errors = err.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error('An unexpected error occurred.');
                        }
                    }
                });
            });
        });
    </script>
@endpush