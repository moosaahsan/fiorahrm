@php
    use Illuminate\Support\Facades\Storage;

    $user = auth()->user();
    $isAdmin = $user->hasAnyRole(['admin', 'hr', 'administrator']);
    $profilePic = $user->profile_pic ?? null;
    $employee = \App\Models\Employee::where('user_id', $user->id)->first();

    if (!$isAdmin && !$profilePic && $employee) {
        $profilePic = $employee->profile_pic ?? null;
    }

    $profilePicUrl = get_profile_picture_url($profilePic, $user->name);

    $employee_breaks = $employee ? getEmployeeBreakDetailHelper($employee->id) : [
        'allowed_break_minutes' => 0,
        'total_spent_minutes' => 0,
        'remaining_minutes' => 0,
        'exceeded_minutes' => 0,
        'total_spent' => '0 minutes',
        'remaining' => '0 minutes',
        'exceeded' => '0 minutes',
    ];

    $assignedShift = $employee ? getTodayAssignedShift($employee->id) : null;
    $shiftDate = $assignedShift ? resolve_shift_date($assignedShift) : now()->toDateString();
    $attendance_today = $employee ? \App\Models\Attendance::where('emp_id', $employee->id)
        ->where('shift_date', $shiftDate)
        ->first() : null;
    $hasCheckedIn = $attendance_today && $attendance_today->check_in && !$attendance_today->check_out;

    $employeeSettings = $employee ? get_employee_settings($employee->id) : null;
    $shiftStartTime = ($assignedShift && $assignedShift->shift) ? $assignedShift->shift->start_time : null;

    $activeBreak = $employee ? \App\Models\EmployeeBreak::where('emp_id', $employee->id)
        ->where('status', 'On Break')
        ->where('shift_date', $shiftDate)
        ->first() : null;
    $initialBreakDuration = $activeBreak ? (int) round(\Carbon\Carbon::parse($activeBreak->start_time)->diffInMinutes(now())) : 0;
@endphp

<div class="topbar">
    <div class="topbar-left d-flex align-items-center gap-3" style="background:#ffffff1a;">
        <button type="button" class="btn p-0 d-md-none text-muted" onclick="toggleMenu()">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <a href="/" class="logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="height:42px;">
        </a>
    </div>

    <div class="d-none d-lg-flex align-items-center gap-4">
        @if($employee && $hasCheckedIn)
            <div class="break-stats-wrapper">
                <div class="break-card-mini">
                    <span class="label">Total</span>
                    <span class="value" id="header-total-break">{{ $employee_breaks['allowed_break_minutes'] }}m</span>
                </div>
                <div class="break-card-mini">
                    <span class="label">Spent</span>
                    <span class="value text-danger" id="header-total-spent">{{ $employee_breaks['total_spent_minutes'] }}m</span>
                </div>
                <div class="break-card-mini">
                    <span class="label">Rem.</span>
                    <span class="value text-success" id="header-remaining">{{ $employee_breaks['remaining_minutes'] }}m</span>
                </div>
                <div class="break-card-mini" id="header-break-timer-card" style="display: none; background: #eef2ff;">
                    <span class="label text-indigo">Active</span>
                    <span class="value text-primary" id="header-break-duration">0m</span>
                </div>
            </div>

            <div class="break-actions">
                @if($activeBreak)
                    <button id="end-break-btn" class="btn-action btn-end" data-break-id="{{ $activeBreak->id }}">
                        <i class="fas fa-stop-circle"></i> End Break
                    </button>
                    <button id="start-break-btn" class="btn-action btn-start" style="display: none;">
                        <i class="fas fa-play-circle"></i> Start Break
                    </button>
                @else
                    <button id="start-break-btn" class="btn-action btn-start">
                        <i class="fas fa-play-circle"></i> Start Break
                    </button>
                    <button id="end-break-btn" class="btn-action btn-end" style="display: none;">
                        <i class="fas fa-stop-circle"></i> End Break
                    </button>
                @endif
            </div>
        @elseif($employee && !$hasCheckedIn && $assignedShift)
            <div class="break-actions">
                <button id="header-check-in-btn" class="btn-action btn-checkin" 
                    data-shift-start="{{ $shiftStartTime }}"
                    data-grace="{{ $employeeSettings['lateGraceMinutes'] ?? 0 }}"
                    data-halfday="{{ $employeeSettings['markHalfDayAfter'] ?? 0 }}">
                    <i class="fas fa-sign-in-alt"></i> Check In
                </button>
            </div>
        @endif
    </div>

    <div class="topbar-right d-flex align-items-center gap-3">
        <div class="clock-container d-none d-md-block">
            <i class="far fa-clock me-1 text-muted"></i>
            <span id="clock">00:00:00</span>
        </div>

        <a class="notif-btn" href="#" data-toggle="dropdown">
            <i class="far fa-bell"></i>
            <span class="notif-badge"></span>
        </a>

        <div class="dropdown">
            <a class="user-profile-btn dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <div class="user-info d-none d-md-block">
                    <span class="name">{{ $user->name }}</span>
                    <span class="role">{{ $isAdmin ? 'Administrator' : 'Employee' }}</span>
                </div>
                <img src="{{ $profilePicUrl }}" alt="User" class="user-avatar">
            </a>
            <ul class="dropdown-menu dropdown-menu-right shadow-lg border-0 mt-3"
                style="border-radius: 12px; min-width: 200px;">
                <li><a class="dropdown-item py-2" href="{{ $isAdmin ? route('admin.profile.edit') : route('employee.profile.edit') }}"><i
                            class="far fa-user-circle me-2 text-muted"></i> Profile</a></li>
                @if($isAdmin)
                    <li><a class="dropdown-item py-2" href="{{ route('admin.settings') }}"><i
                                class="fas fa-cog me-2 text-muted"></i> Settings</a></li>
                @endif
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item py-2 text-danger fw-600" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-power-off me-2"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>


<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    let breakTimerInterval = null;

    function updateBreakTimer(breakId, prefix = 'header') {
        if (!breakId) {
            $(`#${prefix}-break-timer-card`).hide();
            if (breakTimerInterval) clearInterval(breakTimerInterval);
            return;
        }
        $.ajax({
            url: '{{ route("employee.break.live") }}',
            method: 'GET',
            data: { break_id: breakId },
            success: function (response) {
                if (response.success) {
                    $(`#${prefix}-break-duration`).text(`${response.duration}m`);
                    $(`#${prefix}-total-spent`).text(`${response.total_spent}m`);
                    $(`#${prefix}-remaining`).text(`${response.remaining}m`);
                    $(`#${prefix}-exceeded`).text(`${response.exceeded}m`);
                    $(`#${prefix}-break-timer-card`).show();
                } else {
                    $(`#${prefix}-break-timer-card`).hide();
                    if (breakTimerInterval) clearInterval(breakTimerInterval);
                    toastr.error(response.message || 'Failed to fetch break data.');
                }
            },
            error: function (error) {
                $(`#${prefix}-break-timer-card`).hide();
                if (breakTimerInterval) clearInterval(breakTimerInterval);
                toastr.error(error.responseJSON?.message || 'Failed to fetch break data.');
            }
        });
    }

    function startBreak() {
        Swal.fire({
            title: 'Take a Break',
            padding: '1.5rem',
            background: '#ffffff',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-play me-2"></i> Start Break',
            cancelButtonText: 'Cancel',
            customClass: {
                container: 'modern-swal-container',
                popup: 'modern-swal-popup',
                title: 'modern-swal-title',
                confirmButton: 'modern-swal-confirm',
                cancelButton: 'modern-swal-cancel',
                htmlContainer: 'modern-swal-html'
            },
            html: `
                <div class="text-start px-1">
                    <p class="text-muted mb-4 small">Choose your break type and provide a reason if necessary.</p>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark mb-2 d-block">Break Type</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 px-3" style="border-radius: 12px 0 0 12px; border: 1px solid #e2e8f0;">
                                <i class="fas fa-mug-hot text-primary"></i>
                            </span>
                            <select id="break-type" class="form-select border-start-0 ps-1" style="border-radius: 0 12px 12px 0; height: 50px; font-size: 15px; border: 1px solid #e2e8f0;">
                                <option value="general">☕ General Break</option>
                                <option value="official">🏢 Official Break</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-dark mb-2 d-block">Reason / Note</label>
                        <textarea id="break-reason" class="form-control" rows="5" 
                            placeholder="Type your reason here (required for official breaks)..." 
                            style="border-radius: 12px; font-size: 15px; padding: 15px; resize: none; border: 1px solid #e2e8f0; min-height: 140px; line-height: 1.6;"></textarea>
                    </div>
                </div>
                `,
            preConfirm: () => {
                const breakType = document.getElementById('break-type').value;
                const reason = document.getElementById('break-reason').value;
                if (breakType === 'official' && !reason) {
                    Swal.showValidationMessage('Reason is required for official breaks');
                    return false;
                }
                return { break_type: breakType, reason: reason };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#start-break-btn').prop('disabled', true);
                $.ajax({
                    url: '{{ route("employee.break.start") }}',
                    method: 'POST',
                    data: { break_type: result.value.break_type, reason: result.value.reason },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(`Started ${response.break.type} break!`);
                            $('#start-break-btn').hide().prop('disabled', false);
                            $('#end-break-btn').show().attr('data-break-id', response.break.id);
                            ['header', 'footer'].forEach(prefix => {
                                $(`#${prefix}-break-duration`).text(`0m`);
                                $(`#${prefix}-break-timer-card`).show();
                                updateBreakTimer(response.break.id, prefix);
                            });
                            breakTimerInterval = setInterval(() => {
                                ['header', 'footer'].forEach(prefix => updateBreakTimer(response.break.id, prefix));
                            }, 60000);
                        }
                    },
                    error: function (error) {
                        $('#start-break-btn').prop('disabled', false);
                        toastr.error(error.responseJSON?.message || 'Failed to start break.');
                    }
                });
            }
        });
    }

    function endBreak(breakId) {
        if (!breakId) {
            console.error('No break ID provided for endBreak');
            toastr.error('No break ID provided.');
            return;
        }
        $('#end-break-btn').prop('disabled', true);
        $.ajax({
            url: '{{ route("employee.break.end", ["id" => ":id"]) }}'.replace(':id', breakId),
            method: 'POST',
            success: function (response) {
                if (response.success) {
                    toastr.success('Break ended!');
                    $('#start-break-btn').show();
                    $('#end-break-btn').hide().prop('disabled', false);
                    ['header', 'footer'].forEach(prefix => {
                        $(`#${prefix}-break-timer-card`).hide();
                    });
                    if (breakTimerInterval) clearInterval(breakTimerInterval);
                }
            },
            error: function (error) {
                $('#end-break-btn').prop('disabled', false);
                toastr.error(error.responseJSON?.message || 'Failed to end break.');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const employeeId = parseInt('{{ $employee->id ?? 0 }}');
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        if (!employeeId) {
            console.error('Employee ID not found. Cannot subscribe to channel.');
            $('#start-break-btn, #end-break-btn, #header-break-timer-card, #footer-break-timer-card').hide();
            return;
        }

        // Set up CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Wait for Echo and Pusher to be ready
        function initEcho() {
            const echoReady = typeof window.Echo !== 'undefined';
            const pusherReady = typeof window.Pusher !== 'undefined';
            
            if (!echoReady || !pusherReady) {
                console.warn(`Waiting for dependencies... Echo: ${echoReady}, Pusher: ${pusherReady}. Retrying in 500ms...`);
                setTimeout(initEcho, 500);
                return;
            }

            console.log('Echo and Pusher ready, subscribing to channel...');
            
            // Enable Pusher debug logging
            window.Pusher.logToConsole = true;

            // Subscribe to Pusher channel
            window.Echo.private(`employee.${employeeId}.breaks`)
                .listen('.break.updated', (event) => {
                    console.log('Break update received from desktop:', event);
                    const data = event.breakData; // Data comes wrapped in breakData from the event class
                    
                    if (!data) {
                        console.error('Invalid event data structure received:', event);
                        return;
                    }

                    ['header', 'footer'].forEach(prefix => {
                        const totalBreakEl = document.getElementById(`${prefix}-total-break`);
                        const totalSpentEl = document.getElementById(`${prefix}-total-spent`);
                        const remainingEl = document.getElementById(`${prefix}-remaining`);
                        const exceededEl = document.getElementById(`${prefix}-exceeded`);

                        if (totalBreakEl) totalBreakEl.textContent = `${data.allowed_break_minutes}m`;
                        if (totalSpentEl) totalSpentEl.textContent = `${data.total_spent_minutes}m`;
                        if (remainingEl) remainingEl.textContent = `${data.remaining_minutes}m`;
                        if (exceededEl) exceededEl.textContent = `${data.exceeded_minutes}m`;

                        if (data.active_break_id) {
                            $(`#${prefix}-break-duration`).text(`${data.active_break_duration}m`);
                            $(`#${prefix}-break-timer-card`).show();
                        } else {
                            $(`#${prefix}-break-timer-card`).hide();
                        }
                    });

                    if (data.active_break_id) {
                        $('#start-break-btn').hide();
                        $('#end-break-btn').show().attr('data-break-id', data.active_break_id);
                        if (!breakTimerInterval) {
                            breakTimerInterval = setInterval(() => {
                                ['header', 'footer'].forEach(p => updateBreakTimer(data.active_break_id, p));
                            }, 60000);
                        }
                    } else {
                        $('#start-break-btn').show();
                        $('#end-break-btn').hide();
                        if (breakTimerInterval) {
                            clearInterval(breakTimerInterval);
                            breakTimerInterval = null;
                        }
                    }
                    toastr.success(data.on_break ? 'Break started from desktop!' : 'Break ended from desktop!');
                });

            // Subscribe to Admin Notifications
            if (isAdmin) {
                console.log('Subscribing to private-admin-notifications...');
                window.Echo.private('admin-notifications')
                    .listen('.leave.applied', (event) => {
                        console.log('EVENT RECEIVED: leave.applied', event);
                        const leave = event.leave;
                        const employeeName = event.employeeName;
                        
                        toastr.info(
                            `<strong>${employeeName}</strong> has applied for <strong>${leave.leave_type.replace('_', ' ').toUpperCase()}</strong> leave.`, 
                            'New Leave Application', 
                            {
                                timeOut: 0, // Stay until closed
                                closeButton: true,
                                progressBar: true,
                                onclick: function() {
                                    window.location.href = '{{ route("admin.leaves.index") }}';
                                }
                            }
                        );

                        // Visual indicator on the bell
                        $('.notif-badge').show();
                    });
            }
        }

        // Initialize Echo subscription
        initEcho();

        // Bind button click listeners
        $(document).on('click', '#header-check-in-btn', function() {
        const btn = $(this);
        const shiftStartStr = btn.data('shift-start');
        const graceMinutes = parseInt(btn.data('grace'));
        const halfDayThreshold = parseInt(btn.data('halfday'));

        if (!shiftStartStr) {
            Swal.fire('Error', 'No shift start time found.', 'error');
            return;
        }

        const now = new Date();
        const start = new Date();
        const [hours, minutes, seconds] = shiftStartStr.split(':');
        start.setHours(hours, minutes, seconds, 0);

        // Calculate late minutes
        const graceStart = new Date(start.getTime() + graceMinutes * 60000);
        const isLate = now > graceStart;
        const diffMs = now - start;
        const totalLateMinutes = Math.max(0, Math.floor(diffMs / 60000));

        if (isLate) {
            let title = 'Late Check-In';
            let message = `You are checking in late by ${totalLateMinutes} minutes.`;
            
            if (totalLateMinutes >= halfDayThreshold) {
                title = 'Half-Day Check-In';
                message = `You are checking in ${totalLateMinutes} minutes late, which exceeds the half-day threshold (${halfDayThreshold}m). This session will be marked as a <b>Half-Day</b>.`;
            }

            Swal.fire({
                title: title,
                html: message + '<br><br>Please provide a reason to continue:',
                input: 'textarea',
                inputPlaceholder: 'Type your reason here...',
                showCancelButton: true,
                confirmButtonText: 'Check In',
                confirmButtonColor: '#6366f1',
                preConfirm: (reason) => {
                    if (!reason) {
                        Swal.showValidationMessage('Reason is required.');
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitCheckIn(result.value);
                }
            });
        } else {
            submitCheckIn();
        }
    });

    function submitCheckIn(reason = '') {
        Swal.fire({
            title: 'Checking In...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '{{ route("employee.checkin.submit") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                late_reason: reason
            },
            success: function(response) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Checked in successfully!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Check-in failed.';
                
                if (msg.toLowerCase().includes('reason')) {
                    let title = 'Check-in Reason Required';
                    let icon = 'warning';
                    
                    if (msg.toLowerCase().includes('half-day')) {
                        title = 'Half-Day Check-in';
                        icon = 'error';
                    }

                    Swal.fire({
                        title: title,
                        html: msg + '<br><br>Please provide a reason to continue:',
                        input: 'textarea',
                        inputPlaceholder: 'Type your reason here...',
                        showCancelButton: true,
                        confirmButtonText: 'Check In',
                        confirmButtonColor: '#6366f1',
                        preConfirm: (reasonValue) => {
                            if (!reasonValue) {
                                Swal.showValidationMessage('Reason is required.');
                            }
                            return reasonValue;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitCheckIn(result.value);
                        }
                    });
                } else {
                    Swal.fire('Error', msg, 'error');
                }
            }
        });
    }

    $(document).on('click', '#start-break-btn', function() {
            startBreak();
        });

        $(document).on('click', '#end-break-btn', function() {
            const breakId = $(this).attr('data-break-id');
            endBreak(breakId);
        });

        // Check for active break on page load
        $.ajax({
            url: '{{ route("employee.break.active") }}',
            method: 'GET',
            success: function (response) {
                if (response.success && response.activeBreak) {
                    $('#start-break-btn').hide();
                    $('#end-break-btn').show().attr('data-break-id', response.activeBreak.id);
                    ['header', 'footer'].forEach(prefix => {
                        $(`#${prefix}-break-duration`).text(`${response.activeBreak.duration}m`);
                        $(`#${prefix}-break-timer-card`).show();
                        updateBreakTimer(response.activeBreak.id, prefix);
                    });
                    if (breakTimerInterval) clearInterval(breakTimerInterval);
                    breakTimerInterval = setInterval(() => {
                        ['header', 'footer'].forEach(prefix => updateBreakTimer(response.activeBreak.id, prefix));
                    }, 60000);
                } else {
                    $('#start-break-btn').show();
                    $('#end-break-btn').hide();
                    ['header', 'footer'].forEach(prefix => {
                        $(`#${prefix}-break-timer-card`).hide();
                    });
                    if (breakTimerInterval) {
                        clearInterval(breakTimerInterval);
                        breakTimerInterval = null;
                    }
                }
            },
            error: function (error) {
                console.error('Error checking active break:', error);
                const msg = error.responseJSON && error.responseJSON.message ? error.responseJSON.message : 'Failed to check active break.';
                toastr.error(msg);
            }
        });
    });
</script>

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Configure toastr options
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 3000,
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };
    </script>
@endsection