<div class="modal fade" id="addnew" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" id="onboarding-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i><span class="fas fa-user-plus"></span></i>
                    New Onboarding
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="add-employees" action="{{ route('admin.employees.store') }}" method="POST">
                @csrf
                <div class="onboarding-scroll-area">
                    <ul class="error-container"></ul>

                    <!-- Identity Section -->
                    <div class="saas-card" id="identity-section">
                        <div class="section-tag"><i class="fas fa-id-card"></i> Identity & Profile</div>
                        <div class="row">
                            <div class="col-md-6 mb-4" id="name-col">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control saas-input" placeholder="e.g. Nadeem Ahmed"
                                        id="name" name="name" required />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4 employee-specific" id="pos-col">
                                <div class="form-group">
                                    <label>Job Position</label>
                                    <input type="text" class="form-control saas-input"
                                        placeholder="e.g. Senior Laravel Developer" id="position" name="position"
                                        required />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4" id="email-col">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control saas-input" placeholder="name@company.com"
                                        id="email" name="email" required />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4" id="password-col">
                                <div class="form-group">
                                    <label>Account Password</label>
                                    <input type="password" class="form-control saas-input" id="password" name="password"
                                        placeholder="••••••••" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4" id="role-col">
                                <div class="form-group">
                                    <label>Access Role</label>
                                    <select class="form-control saas-input saas-select" id="role" name="role" required>
                                        <option value="" disabled selected>- Choose Role -</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4 employee-specific" id="branch-col">
                                <div class="form-group">
                                    <label>Assigned Branch</label>
                                    <select class="form-control saas-input saas-select" id="branch_id" name="branch_id"
                                        required>
                                        <option value="" disabled selected>- Choose Office -</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4 employee-specific" id="gender-col">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select class="form-control saas-input saas-select" id="gender" name="gender"
                                        required>
                                        <option value="male" selected>Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Details - Only for non-admins -->
                    <div class="employee-specific">
                        <div class="saas-card" id="employment-section">
                            <div class="section-tag"><i class="fas fa-calendar-check"></i> Employment Timeline</div>
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <div class="form-group">
                                        <label>Joining Date</label>
                                        <input type="date" class="form-control saas-input" name="joining_date" required
                                            value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="form-group">
                                        <label>Date of Birth</label>
                                        <input type="date" name="dob" class="form-control saas-input">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="form-group">
                                        <label>Probation</label>
                                        <select class="form-control saas-input saas-select" name="probation" required>
                                            <option value="" disabled selected>- Duration -</option>
                                            @for ($i = 2; $i <= 6; $i++)
                                                <option value="{{ $i }}">{{ $i }} Months</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Contact Number</label>
                                        <input type="text" class="form-control saas-input" placeholder="+92 3XX XXXXXXX"
                                            name="contact_no" required />
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Emergency Contact</label>
                                        <input type="text" class="form-control saas-input"
                                            placeholder="Relative's Phone" name="emergency_no" required />
                                    </div>
                                </div>
                                @if (auth()->user()->hasRole(['admin', 'administrator']))
                                <div class="col-md-12 mb-4">
                                    <div class="form-group">
                                        <label>Monthly Salary (PKR) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control saas-input" placeholder="e.g. 50000" name="salary" required />
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="saas-card" id="schedule-section">
                            <div class="section-tag"><i class="fas fa-clock"></i> Schedule & Logistics</div>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Default Shift</label>
                                        <select class="form-control saas-input saas-select" name="schedule" required>
                                            <option value="" disabled selected>- Select Shift -</option>
                                            @foreach ($schedules as $schedule)
                                                <option value="{{ $schedule->id }}">
                                                    {{ $schedule->shift_name }} ({{ $schedule->start_time }} to
                                                    {{ $schedule->end_time }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="form-group">
                                        <label>Assign Team</label>
                                        <select class="form-control saas-input saas-select" name="team_id">
                                            <option value="">-- No Team --</option>
                                            @foreach ($teams as $team)
                                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Policy Config -->
                        <div class="saas-card" style="border-left: 6px solid #4f46e5;">
                            <div class="section-tag"><i class="fas fa-cog"></i> Configuration & Constraints</div>
                            <div class="policy-grid">
                                <div class="form-group">
                                    <label>Break (min)</label>
                                    <input type="number" class="form-control saas-input no-arrows" name="break_duration"
                                        value="45">
                                </div>
                                <div class="form-group">
                                    <label>Half-Day Break (min)</label>
                                    <input type="number" class="form-control saas-input no-arrows"
                                        name="break_allowed_in_half_day" value="30">
                                </div>
                                <div class="form-group">
                                    <label>Idle Limit (min)</label>
                                    <input type="number" class="form-control saas-input no-arrows"
                                        name="idle_time_allowed" value="5">
                                </div>
                                <div class="form-group">
                                    <label>Late Grace (min)</label>
                                    <input type="number" class="form-control saas-input no-arrows"
                                        name="late_minutes_margin" value="5">
                                </div>
                                <div class="form-group">
                                    <label>Half Day After (min)</label>
                                    <input type="number" class="form-control saas-input no-arrows"
                                        name="mark_half_day_after" value="120">
                                </div>
                                <div class="form-group">
                                    <label>Leaves / Year</label>
                                    <input type="number" class="form-control saas-input no-arrows"
                                        name="leaves_allowed_in_year" value="16"
                                        style="border-color: #4f46e5; color: #4f46e5; font-weight: 800;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-saas">
                    <button type="button" class="btn-cancel-saas" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-onboard">
                        <i class="fas fa-paper-plane"></i> Onboard Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Smooth Role Toggling
        $('#role').on('change', function () {
            const role = $(this).val();
            if (role === 'admin') {
                $('#onboarding-dialog').animate({ maxWidth: '900px' }, 400);
                $('.employee-specific').fadeOut(300);

                // Adjustment for admin mode
                $('#name-col, #email-col, #password-col, #role-col').removeClass('col-md-6 mb-4').addClass('col-md-6 mb-3');

                // Remove required
                $('.employee-specific input, .employee-specific select').each(function () {
                    $(this).data('was-required', $(this).prop('required'));
                    $(this).prop('required', false);
                });
            } else {
                $('#onboarding-dialog').animate({ maxWidth: '1140px' }, 400); // modal-xl default
                $('.employee-specific').fadeIn(400);

                // Revert column widths
                $('#name-col, #email-col, #password-col, #role-col').removeClass('col-md-6 mb-3').addClass('col-md-6 mb-4');

                // Restore required
                $('.employee-specific input, .employee-specific select').each(function () {
                    if ($(this).data('was-required') !== undefined) {
                        $(this).prop('required', $(this).data('was-required'));
                    } else {
                        $(this).prop('required', true);
                    }
                });
            }
        });

        // AJAX Submission
        $('#add-employees').submit(function (e) {
            e.preventDefault();
            let $btn = $(this).find('button[type="submit"]');
            let oldHtml = $btn.html();

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: $(this).attr('action'),
                method: "POST",
                data: $(this).serialize(),
                success: function (resp) {
                    if (resp.success) {
                        toastr.success('Profile created successfully!');
                        $('#addnew').modal('hide');
                        if ($.fn.DataTable.isDataTable('#employees-table')) {
                            $('#employees-table').DataTable().ajax.reload(null, false);
                        }
                    }
                },
                error: function (xhr) {
                    $('.error-container').empty();
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        for (let f in errors) {
                            $('.error-container').append('<li>' + errors[f][0] + '</li>');
                        }
                    } else {
                        toastr.error('A system error occurred.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).html(oldHtml);
                }
            });
        });
    });
</script>