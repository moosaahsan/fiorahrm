@extends('admin.layouts.app')

@section('title', 'Refine Talent Profile')

@section('breadcrumb')
    <div class="col-sm-6 text-left">
        <h4 class="page-title directory-header">Refine Talent Profile</h4>
        <ol class="breadcrumb saas-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Workforce</a></li>
            <li class="breadcrumb-item active">Refine Profile</li>
        </ol>
    </div>
@endsection

@section('button')
    <a class="btn btn-premium-back" href="{{ route('admin.employees.index') }}">
        <i class="fas fa-arrow-left mr-2"></i> Back to Workforce
    </a>
@endsection

@section('content')

    <div class="container-fluid">
        <div class="premium-form-container">
            <div class="error-card" id="form-errors">
                <h5 class="text-danger mb-2">Refinement needed:</h5>
                <ul></ul>
            </div>

            <form id="refine-form" action="{{ route('admin.employees.update', $employee->id) }}" method="POST">
                @csrf
                @method('PUT')

                <input type="hidden" name="cropped_profile" id="cropped_profile">

                <!-- Profile Photo & Identity -->
                <div class="row">
                    <div class="col-lg-4">
                        <div class="saas-card profile-photo-card">
                            <div class="section-tag"><i class="fas fa-camera"></i> Profile Photo</div>
                            <div class="profile-preview-container">
                                <img src="{{ $employee->profile_pic_url }}" class="profile-preview" id="preview-img">
                                <label for="profile_input" class="photo-upload-btn">
                                    <i class="fas fa-pen"></i>
                                </label>
                                <input type="file" id="profile_input" style="display: none;" accept="image/*">
                            </div>
                            <h5 class="font-weight-bold mb-1">{{ $employee->name }}</h5>
                            <p class="text-muted small">Employee ID: EMP-{{ $employee->id }}</p>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="saas-card">
                            <div class="section-tag"><i class="fas fa-id-card"></i> Core Identity</div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Full Legal Name</label>
                                        <input type="text" class="saas-input" value="{{ $employee->name }}" name="name"
                                            required />
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Company Email</label>
                                        <input type="email" class="saas-input" value="{{ $employee->email }}" name="email"
                                            required />
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Job Designation</label>
                                        <input type="text" class="saas-input" value="{{ $employee->position }}"
                                            name="position" required />
                                    </div>
                                </div>
                            </div>

                            <hr style="border-top: 1px dashed #e2e8f0; margin-bottom: 2rem;">

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="form-group position-relative">
                                        <label>CNIC Front Side</label>
                                        @if($employee->cnic_front_path)
                                            <div class="upload-label-badge">
                                                <span class="badge badge-success px-2 py-1" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                                    <i class="fas fa-check-circle mr-1"></i> Uploaded
                                                </span>
                                            </div>
                                        @endif
                                        <div class="file-upload-wrapper" onclick="document.getElementById('cnic_front_input').click()">
                                            <div class="file-upload-icon"><i class="fas fa-id-badge"></i></div>
                                            <div class="font-weight-bold small text-dark">Click to Update Front Side</div>
                                            <div id="front-name" class="file-name">@if($employee->cnic_front_path) Existing file preserved @else No file chosen @endif</div>
                                            <input type="file" id="cnic_front_input" name="cnic_front" style="display:none" accept="image/*,application/pdf"
                                                onchange="updateFileName(this, 'front-name')">
                                        </div>
                                        @if($employee->cnic_front_path)
                                            <div class="mt-2 text-center">
                                                <a href="{{ route('admin.employees.cnic.view', [$employee->id, 'front']) }}" target="_blank" class="text-primary small font-weight-bold">
                                                    <i class="fas fa-external-link-alt mr-1"></i> Preview Current Document
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="form-group position-relative">
                                        <label>CNIC Back Side</label>
                                        @if($employee->cnic_back_path)
                                            <div class="upload-label-badge">
                                                <span class="badge badge-success px-2 py-1" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                                    <i class="fas fa-check-circle mr-1"></i> Uploaded
                                                </span>
                                            </div>
                                        @endif
                                        <div class="file-upload-wrapper" onclick="document.getElementById('cnic_back_input').click()">
                                            <div class="file-upload-icon"><i class="fas fa-id-badge"></i></div>
                                            <div class="font-weight-bold small text-dark">Click to Update Back Side</div>
                                            <div id="back-name" class="file-name">@if($employee->cnic_back_path) Existing file preserved @else No file chosen @endif</div>
                                            <input type="file" id="cnic_back_input" name="cnic_back" style="display:none" accept="image/*,application/pdf"
                                                onchange="updateFileName(this, 'back-name')">
                                        </div>
                                        @if($employee->cnic_back_path)
                                            <div class="mt-2 text-center">
                                                <a href="{{ route('admin.employees.cnic.view', [$employee->id, 'back']) }}" target="_blank" class="text-primary small font-weight-bold">
                                                    <i class="fas fa-external-link-alt mr-1"></i> Preview Current Document
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Access & Deployment -->
                <div class="saas-card">
                    <div class="section-tag"><i class="fas fa-network-wired"></i> Access & Deployment</div>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>System Access Role</label>
                                <select class="saas-input saas-select" name="role" required>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" {{ $employee->user->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>Primary Office / Branch</label>
                                <select class="saas-input saas-select" name="branch_id" required>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $employee->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>Reporting Team</label>
                                <select class="saas-input saas-select" name="team_id">
                                    <option value="">-- No Team --</option>
                                    @foreach ($teams as $team)
                                        <option value="{{ $team->id }}" {{ $employee->team_id == $team->id ? 'selected' : '' }}>
                                            {{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Operating Shift</label>
                                <select class="saas-input saas-select" name="schedule" required>
                                    @foreach ($schedules as $schedule)
                                        <option value="{{ $schedule->id }}" {{ optional($employee->currentShiftAssignment)->shift_id == $schedule->id ? 'selected' : '' }}>
                                            {{ $schedule->shift_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Gender Identification</label>
                                <select class="saas-input saas-select" name="gender">
                                    <option value="male" {{ $employee->gender == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ $employee->gender == 'female' ? 'selected' : '' }}>Female
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Details -->
                <div class="saas-card">
                    <div class="section-tag"><i class="fas fa-calendar-alt"></i> Employment Timeline & Contact</div>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>Joining Date</label>
                                <input type="date" class="saas-input" name="joining_date"
                                    value="{{ $employee->joining_date }}" required>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="saas-input" value="{{ $employee->dob }}">
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>Probationary Status (Months)</label>
                                <input type="number" class="saas-input" name="probation" value="{{ $employee->probation }}"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Contact Number</label>
                                <input type="text" class="saas-input" name="contact_no" value="{{ $employee->contact_no }}"
                                    required />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Emergency Contact</label>
                                <input type="text" class="saas-input" name="emergency_no"
                                    value="{{ $employee->emergency_no }}" required />
                            </div>
                        </div>
                        @if (auth()->user()->hasRole(['admin', 'administrator']))
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Gross Monthly Salary (PKR)</label>
                                    <input type="number" class="saas-input" name="salary" value="{{ $employee->salary }}" />
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="saas-card" style="border-left: 6px solid #10b981;">
                    <div class="section-tag" style="color: #10b981;"><i class="fas fa-university"></i> Financial
                        Disbursement Details</div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" class="saas-input" value="{{ $employee->bank_name }}" name="bank_name"
                                    placeholder="e.g. Meezan Bank" />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Account Holder Name</label>
                                <input type="text" class="saas-input" value="{{ $employee->bank_account_name }}"
                                    name="bank_account_name" />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Account Number</label>
                                <input type="text" class="saas-input" value="{{ $employee->account_number }}"
                                    name="account_number" />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>IBAN</label>
                                <input type="text" class="saas-input" value="{{ $employee->iban }}" name="iban"
                                    placeholder="PKXX..." />
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Branch Code / Label</label>
                                <input type="text" class="saas-input" value="{{ $employee->branch_code }}"
                                    name="branch_code" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policy Override -->
                <div class="saas-card" style="border-left: 6px solid #8b5cf6;">
                    <div class="section-tag" style="color: #8b5cf6;"><i class="fas fa-user-cog"></i> Attendance Policy
                        Override</div>
                    <div class="policy-grid">
                        <div class="form-group">
                            <label>Late Grace (m)</label>
                            <input type="number" class="saas-input" name="late_minutes_margin"
                                value="{{ $employee->late_minutes_margin }}">
                        </div>
                    </div>
                </div>

                <!-- Leave Entitlement -->
                <div class="saas-card" style="border-left: 6px solid #10b981;">
                    <div class="section-tag" style="color: #10b981;">
                        <i class="fas fa-calendar-check"></i> Leave Entitlement — {{ $leaveYear }}
                    </div>

                    <p class="text-muted mb-4" style="font-size: 0.875rem;">
                        Days allocated to this employee for {{ $leaveYear }}. Leave these at the standard entitlement
                        unless this person has been given something different — anything you change here is kept and
                        will not be reset by the nightly balance sync.
                        @if($leaveEligibleFrom && $leaveEligibleFrom->isFuture())
                            <br>
                            <span style="color: #b45309;">
                                <i class="fas fa-hourglass-half"></i>
                                Entitlement unlocks on {{ $leaveEligibleFrom->format('d M Y') }}
                                ({{ \App\Services\LeaveService::eligibilityMonths() }} months after joining).
                            </span>
                        @endif
                    </p>

                    <div class="policy-grid">
                        @foreach($leaveTypes as $type)
                            @php
                                $balance = $leaveBalances->get($type->slug);
                                $used = (float) ($balance->used ?? 0);
                                $allocated = (float) ($balance->allocated ?? 0);
                                $remaining = (float) ($balance->remaining ?? 0);
                                $trim = fn($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
                            @endphp

                            <div class="form-group">
                                <label>
                                    {{ $type->name }}
                                    @if($type->auto_allocate)
                                        <span class="text-muted" style="font-weight: 400;">
                                            (standard {{ $type->max_days }})
                                        </span>
                                    @endif
                                </label>

                                @if($type->auto_allocate)
                                    <input type="number" step="0.5" min="0" max="365" class="saas-input"
                                        name="leave_allocations[{{ $type->slug }}]" value="{{ $trim($allocated) }}">
                                @else
                                    {{-- Compensatory leave is earned by working holidays; maternity is granted
                                         case by case. Neither is typed in here. --}}
                                    <input type="number" class="saas-input" value="{{ $trim($allocated) }}" disabled>
                                @endif

                                <small class="text-muted">
                                    Used {{ $trim($used) }} &middot; Remaining {{ $trim($remaining) }}
                                    @if($balance && $balance->is_override)
                                        &middot; <span style="color: #7c3aed;">custom</span>
                                    @endif
                                    @if($type->slug === \App\Models\LeaveType::CPL_SLUG)
                                        <br><a href="{{ route('admin.compensatory_leaves.index') }}">Earned from public holidays &rarr;</a>
                                    @elseif(!$type->auto_allocate)
                                        <br>Granted case by case, not allocated yearly.
                                    @endif
                                </small>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 pb-5 d-flex align-items-center justify-content-center gap-4">
                    <a href="{{ route('admin.employees.index') }}" class="btn-cancel-onboard">
                        <i class="fas fa-times"></i> Discard Changes
                    </a>
                    <button type="submit" class="btn-update" id="submit-btn">
                        <i class="fas fa-save"></i> Synchronize Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cropper Modal -->
    <div class="modal fade" id="cropperModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title font-weight-bold">Refine Profile Picture</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-0">
                    <div style="max-height: 500px;">
                        <img id="image-to-crop" style="max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer p-4">
                    <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary font-weight-bold px-4" id="crop-save">Apply Crop</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                let cropper;
                const profileInput = document.getElementById('profile_input');
                const modalImg = document.getElementById('image-to-crop');
                const previewImg = document.getElementById('preview-img');
                const croppedInput = document.getElementById('cropped_profile');

                window.updateFileName = function(input, targetId) {
                    let name = input.files.length > 0 ? input.files[0].name : "No file chosen";
                    document.getElementById(targetId).textContent = name;

                    if (input.files.length > 0) {
                        $(input).closest('.file-upload-wrapper').css({
                            'border-color': 'var(--saas-success)',
                            'background': '#ecfdf5'
                        });
                    }
                };

                profileInput.addEventListener('change', function (e) {
                    const files = e.target.files;
                    if (files && files.length > 0) {
                        const reader = new FileReader();
                        reader.onload = function (event) {
                            modalImg.src = event.target.result;
                            $('#cropperModal').modal('show');
                        };
                        reader.readAsDataURL(files[0]);
                    }
                });

                $('#cropperModal').on('shown.bs.modal', function () {
                    cropper = new Cropper(modalImg, {
                        aspectRatio: 1,
                        viewMode: 2,
                    });
                }).on('hidden.bs.modal', function () {
                    cropper.destroy();
                    cropper = null;
                });

                $('#crop-save').click(function () {
                    const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
                    previewImg.src = canvas.toDataURL();
                    croppedInput.value = canvas.toDataURL();
                    $('#cropperModal').modal('hide');
                });

                // AJAX Submission
                $('#refine-form').on('submit', function (e) {
                    e.preventDefault();
                    const $btn = $('#submit-btn');
                    const oldHtml = $btn.html();
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Synchronizing...');
                    $('#form-errors').fadeOut().find('ul').empty();

                    const formData = new FormData(this);

                    $.ajax({
                        url: $(this).attr('action'),
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (resp) {
                            if (resp.success) {
                                toastr.success('Talent profile refined successfully!');
                                setTimeout(() => {
                                    window.location.href = "{{ route('admin.employees.index') }}";
                                }, 1000);
                            }
                        },
                        error: function (xhr) {
                            $btn.prop('disabled', false).html(oldHtml);
                            if (xhr.status === 422) {
                                const errors = xhr.responseJSON.errors;
                                $('#form-errors').fadeIn();
                                for (let k in errors) {
                                    $('#form-errors ul').append(`<li>${errors[k][0]}</li>`);
                                }
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            } else {
                                toastr.error('A core system synchronization error occurred.');
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection