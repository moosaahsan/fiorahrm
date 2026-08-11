@extends('admin.layouts.app')

@section('title', 'Onboard New Talent')

@section('breadcrumb')
    <div class="col-sm-6 text-left">
        <h4 class="page-title directory-header">New Talent Onboarding</h4>
        <ol class="breadcrumb saas-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}">Workforce</a></li>
            <li class="breadcrumb-item active">Onboard</li>
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
                <h5 class="text-danger mb-2">Check following details:</h5>
                <ul></ul>
            </div>

            <form id="onboarding-form" action="{{ route('admin.employees.store') }}" method="POST">
                @csrf

                <!-- Identity Section -->
                <div class="saas-card">
                    <div class="section-tag"><i class="fas fa-id-card"></i> Identity & Access Profile</div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Full Legal Name</label>
                                <input type="text" class="saas-input" placeholder="e.g. Nadeem Ahmed" name="name"
                                    required />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4 employee-specific">
                            <div class="form-group">
                                <label>Job Designation</label>
                                <input type="text" class="saas-input" placeholder="e.g. Senior Laravel Developer"
                                    name="position" required />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Corporate Email</label>
                                <input type="email" class="saas-input" placeholder="name@company.com" name="email"
                                    required />
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label>Account Password</label>
                                <input type="password" class="saas-input" name="password" placeholder="••••••••" required>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="form-group">
                                <label>System Access Role</label>
                                <select class="saas-input saas-select" id="role" name="role" required>
                                    <option value="" disabled selected>- Choose Role -</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4 employee-specific">
                            <div class="form-group">
                                <label>Assigned HQ / Branch</label>
                                <select class="saas-input saas-select" name="branch_id" required>
                                    <option value="" disabled selected>- Choose Office -</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4 employee-specific">
                            <div class="form-group">
                                <label>Gender Identification</label>
                                <select class="saas-input saas-select" name="gender" required>
                                    <option value="male" selected>Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline & Contacts -->
                <div class="employee-specific">
                    <div class="saas-card">
                        <div class="section-tag"><i class="fas fa-calendar-check"></i> Employment Timeline & Contact</div>
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="form-group">
                                    <label>Joining Date</label>
                                    <input type="date" class="saas-input" name="joining_date" required
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" class="saas-input">
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="form-group">
                                    <label>Probationary Period</label>
                                    <select class="saas-input saas-select" name="probation" required>
                                        <option value="0">No Probation</option>
                                        @for ($i = 1; $i <= 6; $i++)
                                            <option value="{{ $i }}" {{ $i == 3 ? 'selected' : '' }}>{{ $i }}
                                                Month{{ $i > 1 ? 's' : '' }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>Primary Contact (Whatsapp)</label>
                                    <input type="text" class="saas-input" placeholder="+92 3XX XXXXXXX" name="contact_no"
                                        required />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>Emergency SOS Number</label>
                                    <input type="text" class="saas-input" placeholder="Relative's Phone" name="emergency_no"
                                        required />
                                </div>
                            </div>
                            @if (auth()->user()->hasRole(['admin', 'administrator']))
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Gross Monthly Salary (PKR)</label>
                                        <input type="number" step="1" class="saas-input" placeholder="Enter base amount"
                                            name="salary" />
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="saas-card">
                        <div class="section-tag"><i class="fas fa-clock"></i> Deployment & Team Allocation</div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>Operational Shift</label>
                                    <select class="saas-input saas-select" name="schedule" required>
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
                                    <label>Reporting Team</label>
                                    <select class="saas-input saas-select" name="team_id">
                                        <option value="">-- Standalone (No Team) --</option>
                                        @foreach ($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEW: Bank Details Section -->
                    <div class="saas-card bank-card">
                        <div class="section-tag"><i class="fas fa-university"></i> Bank Disbursement Details (Optional)
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>Bank / Financial Institution</label>
                                    <input type="text" class="saas-input" placeholder="e.g. Meezan Bank, HBL"
                                        name="bank_name" />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>Account Holder Name</label>
                                    <input type="text" class="saas-input" placeholder="As per bank record"
                                        name="bank_account_name" />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>Account Number</label>
                                    <input type="text" class="saas-input" placeholder="Enter account digit string"
                                        name="account_number" />
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label>IBAN (Optional)</label>
                                    <input type="text" class="saas-input" placeholder="PKXX XXXX XXXX XXXX XXXX"
                                        name="iban" />
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Branch Code / Identifier</label>
                                    <input type="text" class="saas-input" placeholder="e.g. 0543, Rawalpindi Main"
                                        name="branch_code" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Policy Config -->
                    <div class="saas-card" style="border-left: 6px solid #8b5cf6;">
                        <div class="section-tag"><i class="fas fa-cog"></i> Attendance Policy</div>
                        <div class="policy-grid">
                            <div class="form-group">
                                <label>Late Grace (min)</label>
                                <input type="number" class="saas-input" name="late_minutes_margin" value="5">
                            </div>
                        </div>
                        <p class="text-muted mt-3 mb-0" style="font-size: 0.875rem;">
                            <i class="fas fa-info-circle"></i>
                            Leave entitlement is applied automatically from the leave policy. To give this person a
                            different allocation, edit their profile after saving.
                        </p>
                    </div>
                </div>

                <div class="mt-5 pb-5 d-flex align-items-center justify-content-center gap-4">
                    <a href="{{ route('admin.employees.index') }}" class="btn-cancel-onboard">
                        <i class="fas fa-times"></i> Cancel & Return
                    </a>
                    <button type="submit" class="btn-onboard" id="submit-btn">
                        <i class="fas fa-check-circle"></i> Complete Onboarding
                    </button>
                </div>
                <p class="text-center mt-4 text-muted small">
                    <i class="fas fa-lock mr-2"></i> All personnel data is encrypted and stored in accordance with
                    institutional policy.
                </p>
        </div>
        </form>
    </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                // Dynamic Role Visibility
                $('#role').on('change', function () {
                    const role = $(this).val();
                    if (role === 'admin' || role === 'administrator') {
                        $('.employee-specific').slideUp(500);
                        $('.employee-specific input, .employee-specific select').prop('required', false);
                    } else {
                        $('.employee-specific').slideDown(500);
                        $('.employee-specific input:not([name="salary"]):not([name="dob"]):not([name="bank_name"]):not([name="bank_account_name"]):not([name="account_number"]):not([name="iban"]):not([name="branch_code"]):not([name="team_id"]), .employee-specific select:not([name="team_id"])').prop('required', true);
                    }
                });

                // AJAX Form Submission
                $('#onboarding-form').on('submit', function (e) {
                    e.preventDefault();
                    const $btn = $('#submit-btn');
                    const originalHtml = $btn.html();

                    $btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i> Stabilizing Data...');
                    $('#form-errors').fadeOut().find('ul').empty();

                    $.ajax({
                        url: $(this).attr('action'),
                        method: "POST",
                        data: $(this).serialize(),
                        success: function (resp) {
                            if (resp.success) {
                                toastr.success('New talent onboarded successfully!');
                                setTimeout(() => {
                                    window.location.href = "{{ route('admin.employees.index') }}";
                                }, 1000);
                            }
                        },
                        error: function (xhr) {
                            $btn.prop('disabled', false).html(originalHtml);
                            if (xhr.status === 422) {
                                const errors = xhr.responseJSON.errors;
                                $('#form-errors').fadeIn();
                                for (let key in errors) {
                                    $('#form-errors ul').append(`<li>${errors[key][0]}</li>`);
                                }
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            } else {
                                toastr.error('A system execution logic failure occurred.');
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection