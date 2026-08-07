@php
    use App\Models\Shift;
    $employee = $data;
    $schedules = $schedules ?? Shift::all();
    $currentShiftId = optional($employee->currentShiftAssignment)->shift_id ?? optional($employee->shifts->first())->id;
    $currentRoleName = optional($employee->user->roles->first())->name ?? null;
    $roles = \App\Models\Role::all();
    $teams = \App\Models\Team::all();
@endphp


<div class="modal-content saas-ultra text-left">
    <div class="modal-header saas-header">
        <h4 class="modal-title saas-title mb-0">Profile Editor</h4>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <form id="edit-employee-form" method="POST" action="{{ route('admin.employees.update', $employee->id) }}"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="profile-hero">
            <div class="profile-frame" id="profileTrigger">
                <div class="profile-img-inner">
                    @if ($employee->profile_pic && Storage::disk('public')->exists($employee->profile_pic))
                        <img id="employeeProfilePreview" src="{{ Storage::disk('public')->url($employee->profile_pic) }}"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div id="employeeProfilePreviewFallback"
                            class="h-100 w-100 d-flex align-items-center justify-content-center bg-indigo text-white"
                            style="font-size: 3rem; font-weight: 800; background: #6366f1;">
                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="upload-overlay"><i class="fas fa-camera"></i></div>
                </div>
            </div>
            <input type="file" name="profile_pic" id="employeeProfileImage" accept="image/*" class="d-none">
            <input type="hidden" name="cropped_profile" id="croppedProfilePic">
        </div>

        <div class="saas-body-container">
            <!-- Identity -->
            <div class="saas-premium-card" id="identity-card">
                <div class="saas-card-header"><i class="fas fa-user-circle"></i> Identity Details</div>
                <div class="row g-4">
                    <div class="col-md-6" id="name-col-edit">
                        <label class="saas-label">Full Name</label>
                        <input type="text" name="name" class="saas-input-premium" value="{{ $employee->name }}"
                            required>
                    </div>
                    <div class="col-md-6 employee-specific" id="pos-col-edit">
                        <label class="saas-label">Job Position</label>
                        <input type="text" name="position" class="saas-input-premium" value="{{ $employee->position }}"
                            required>
                    </div>
                    <div class="col-md-6 employee-specific">
                        <label class="saas-label">Gender</label>
                        <select class="saas-input-premium" name="gender" required>
                            <option value="male" {{ $employee->gender == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ $employee->gender == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ $employee->gender == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 employee-specific">
                        <label class="saas-label">Date of Birth</label>
                        <input type="date" name="dob" class="saas-input-premium" value="{{ $employee->dob }}">
                    </div>
                    <div class="col-md-6" id="email-col-edit">
                        <label class="saas-label">Email Address</label>
                        <input type="email" name="email" class="saas-input-premium" value="{{ $employee->email }}"
                            required>
                    </div>
                    <div class="col-md-6 employee-specific">
                        <label class="saas-label">Contact No</label>
                        <input type="text" name="contact_no" class="saas-input-premium"
                            value="{{ $employee->contact_no }}" required>
                    </div>
                    <div class="col-md-6 employee-specific">
                        <label class="saas-label">Emergency Contact No</label>
                        <input type="text" name="emergency_no" class="saas-input-premium"
                            value="{{ $employee->emergency_no }}" required>
                    </div>
                </div>
            </div>

            <!-- Employment -->
            <div class="employee-specific">
                <div class="saas-premium-card" id="employment-card">
                    <div class="saas-card-header"><i class="fas fa-building"></i> Employment Context</div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="saas-label">Joining Date</label>
                            <input type="date" name="joining_date" class="saas-input-premium"
                                value="{{ $employee->joining_date }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="saas-label">Probation</label>
                            <select class="saas-input-premium" name="probation" required>
                                @for ($i = 0; $i <= 6; $i++)
                                    <option value="{{ $i }}" {{ $employee->probation == $i ? 'selected' : '' }}>{{ $i }}
                                        Months</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="saas-label">Shift Schedule</label>
                            <select class="saas-input-premium" name="schedule" required>
                                <option value="" disabled>- Select Shift -</option>
                                @foreach ($schedules as $shift)
                                    <option value="{{ $shift->id }}" {{ $currentShiftId == $shift->id ? 'selected' : '' }}>
                                        {{ $shift->shift_name }} ({{ $shift->start_time }} - {{ $shift->end_time }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (auth()->user()->hasRole(['admin', 'administrator']))
                        <div class="col-md-12 mt-4">
                            <label class="saas-label">Monthly Salary (PKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="salary" class="saas-input-premium" value="{{ $employee->salary }}" placeholder="e.g. 50000" required>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Access & Role -->
            <div class="saas-premium-card" id="role-card">
                <div class="saas-card-header"><i class="fas fa-shield-alt"></i> Access & Security</div>
                <div class="row g-4">
                    <div class="col-md-6" id="role-col-edit">
                        <label class="saas-label">System Role</label>
                        <select class="saas-input-premium" name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ $currentRoleName == $role->name ? 'selected' : '' }}>
                                    {{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="saas-label">Office Branch</label>
                        <select class="saas-input-premium" name="branch_id" required>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $employee->branch_id == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6" id="password-col-edit">
                        <label class="saas-label">Update Password</label>
                        <input type="password" name="password" class="saas-input-premium" placeholder="••••••••">
                    </div>
                    <div class="col-md-12 employee-specific">
                        <label class="saas-label">Assigned Team</label>
                        <select class="saas-input-premium" name="team_id">
                            <option value="">-- No Team --</option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" {{ $employee->team_id == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Policy & Constraints -->
            <div class="employee-specific">
                <div class="saas-premium-card" id="policy-card" style="border-left: 6px solid #4f46e5;">
                    <div class="saas-card-header"><i class="fas fa-sliders-h"></i> Policy & Thresholds</div>
                    @php $settings = $employee->settings->pluck('setting_value', 'setting_name')->toArray(); @endphp
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label class="saas-label">Break (m)</label>
                            <input type="number" name="break_duration" class="saas-input-premium"
                                value="{{ $settings['break_duration'] ?? $employee->break_duration ?? 45 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Late Grace (m)</label>
                            <input type="number" name="late_minutes_margin" class="saas-input-premium"
                                value="{{ $settings['late_grace_minutes'] ?? $employee->late_minutes_margin ?? 5 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Idle Limit (m)</label>
                            <input type="number" name="idle_time_allowed" class="saas-input-premium"
                                value="{{ $settings['idle_time_allowed'] ?? $employee->idle_time_allowed ?? 5 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Leaves / Year</label>
                            <input type="number" name="leaves_allowed_in_year" class="saas-input-premium"
                                value="{{ $settings['leaves_allowed_in_year'] ?? $employee->leaves_allowed_in_year ?? 16 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Half-Day Limit</label>
                            <input type="number" name="number_half_days_allowed_in_month" class="saas-input-premium"
                                value="{{ $settings['half_day_allowed_in_month'] ?? $employee->number_half_days_allowed_in_month ?? 2 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Full-Day Limit</label>
                            <input type="number" name="number_full_days_allowed_in_month" class="saas-input-premium"
                                value="{{ $settings['full_day_allowed_in_month'] ?? $employee->number_full_days_allowed_in_month ?? 2 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Half-Day Break (m)</label>
                            <input type="number" name="break_allowed_in_half_day" class="saas-input-premium"
                                value="{{ $settings['break_allowed_in_half_day'] ?? $employee->break_allowed_in_half_day ?? 30 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Mark Half-Day After (m)</label>
                            <input type="number" name="mark_half_day_after" class="saas-input-premium"
                                value="{{ $settings['mark_half_day_after'] ?? $employee->mark_half_day_after ?? 120 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="saas-label">Time Zone</label>
                            <select name="time_zone" class="saas-input-premium">
                                <option value="Asia/Karachi" {{ ($settings['time_zone'] ?? $employee->time_zone) == 'Asia/Karachi' ? 'selected' : '' }}>Pakistan (GMT+5)</option>
                                <option value="UTC" {{ ($settings['time_zone'] ?? $employee->time_zone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="saas-modal-footer">
            <button type="button" class="btn-saas-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-saas-primary" id="submit-btn"><i class="fas fa-save"></i> Save
                Changes</button>
        </div>
    </form>
</div>

<!-- Cropper Modal (SAAS-ULTRA STYLE) -->
<div class="modal fade" id="employeeCropperModal" tabindex="-1" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"
            style="border-radius: 24px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
            <div class="modal-header" style="background: #1e1b4b; color: white; border: none; padding: 1.5rem 2rem;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-crop-alt mr-2"></i> Refine Profile Image</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0 bg-light"
                style="min-height: 400px; display: flex; align-items: center; justify-content: center;">
                <div style="width: 100%; max-height: 500px; overflow: hidden;">
                    <img id="employeeCropperImg" style="max-width: 100%; display: block;">
                </div>
            </div>
            <div class="modal-footer" style="background: white; border: none; padding: 1.5rem 2rem;">
                <button type="button" class="btn btn-light" data-dismiss="modal"
                    style="border-radius: 12px; font-weight: 600; padding: 10px 25px;">Discard</button>
                <button type="button" class="btn btn-indigo" id="employeeCropImage"
                    style="background: #4f46e5; color: white; border-radius: 12px; font-weight: 700; padding: 10px 30px; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);">
                    <i class="fas fa-check-circle mr-2"></i> Crop & Set Profile
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        let cropper;

        // Visual Toggling for Role
        function toggleEmployeeFields(role) {
            if (role === 'admin') {
                $('.employee-specific').hide();
                $('#shift_update_ajax_modal .modal-dialog').animate({ maxWidth: '750px' }, 400);
                $('#name-col-edit, #email-col-edit, #password-col-edit, #role-col-edit').removeClass('col-md-4 col-md-3 col-md-6').addClass('col-md-6 mb-3');

                $('.employee-specific input, .employee-specific select').prop('required', false);
            } else {
                $('.employee-specific').show();
                $('#shift_update_ajax_modal .modal-dialog').animate({ maxWidth: '1000px' }, 400);
                $('#name-col-edit, #email-col-edit, #password-col-edit, #role-col-edit').removeClass('col-md-6 mb-3').addClass('col-md-6');
                $('#name-col-edit, #email-col-edit').addClass('col-md-6');

                $('.employee-specific input:required, .employee-specific select:required').prop('required', true);
            }
        }

        toggleEmployeeFields("{{ $currentRoleName }}");

        $('select[name="role"]').on('change', function () {
            toggleEmployeeFields($(this).val());
        });

        // Image Handling
        $('#profileTrigger').on('click', () => $('#employeeProfileImage').click());

        $('#employeeProfileImage').on('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    // Remove any old cropper modal from body to avoid ID conflicts
                    $('body > #employeeCropperModal').remove();

                    const $modal = $('#employeeCropperModal').clone().appendTo('body');
                    $modal.find('#employeeCropperImg').attr('src', event.target.result);
                    $modal.modal('show');
                };
                reader.readAsDataURL(file);
            }
        });

        // Use delegated events for the dynamically appended modal
        $(document).on('shown.bs.modal', '#employeeCropperModal', function () {
            const image = $(this).find('#employeeCropperImg')[0];
            if (cropper) cropper.destroy();
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        }).on('hidden.bs.modal', '#employeeCropperModal', function () {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            $(this).remove(); // Remove from DOM after hide
            if ($('#shift_update_ajax_modal').hasClass('show')) {
                $('body').addClass('modal-open');
            }
        });

        $(document).on('click', '#employeeCropImage', function () {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
                if (canvas) {
                    const base64 = canvas.toDataURL('image/png');
                    if ($('#employeeProfilePreview').length) {
                        $('#employeeProfilePreview').attr('src', base64);
                    } else {
                        $('#employeeProfilePreviewFallback').hide();
                        $('#profileTrigger .profile-img-inner').html(`<img id="employeeProfilePreview" src="${base64}" style="width: 100%; height: 100%; object-fit: cover;">`);
                    }
                    $('#croppedProfilePic').val(base64);
                    $(this).closest('.modal').modal('hide');
                } else {
                    toastr.error('Could not generate crop. Please try again.');
                }
            }
        });

        // AJAX Save
        $('#edit-employee-form').submit(function (e) {
            e.preventDefault();
            const btn = $('#submit-btn');
            const oldHtml = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: this.action,
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function (resp) {
                    if (resp.success) {
                        toastr.success('Profile updated successfully!');
                        $('#shift_update_ajax_modal').modal('hide');
                        if ($.fn.DataTable.isDataTable('#employees-table')) {
                            $('#employees-table').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(key => toastr.error(errors[key][0]));
                    } else {
                        toastr.error('A system error occurred.');
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).html(oldHtml);
                }
            });
        });
    });
</script>