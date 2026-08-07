@extends('employee.layouts.app')

@section('content')
<div class="employee-portal-page">
<div class="container">
    <div class="checkin-wrapper">
        <div class="checkin-card">
            
            <div class="icon-box">
                <i class="fas fa-fingerprint"></i>
            </div>

            <h2 class="fw-bold mb-1">Employee Check-In</h2>
            <div id="live-clock">00:00:00</div>
            <div class="current-date">{{ date('l, d F Y') }}</div>

            @if(session('error'))
                <div class="alert alert-danger shadow-sm border-0">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success shadow-sm border-0">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                </div>
            @endif

            <div class="shift-info">
                <div class="shift-item">
                    <span class="shift-label">Assigned Shift</span>
                    <span class="shift-value text-primary">{{ $shift->shift->shift_name }}</span>
                </div>
                <div class="shift-item">
                    <span class="shift-label">Start Time</span>
                    <span class="shift-value">{{ Carbon\Carbon::parse($shift->shift->start_time)->format('h:i A') }}</span>
                </div>
                <div class="shift-item">
                    <span class="shift-label">End Time</span>
                    <span class="shift-value">{{ Carbon\Carbon::parse($shift->shift->end_time)->format('h:i A') }}</span>
                </div>
            </div>

            <div id="checkin-container">
                <form id="checkin-form">
                    @csrf
                    <input type="hidden" name="shift_id" value="{{ $shift->shift_id }}">
                    <button type="submit" class="btn btn-checkin" id="submit-btn" style="height: 60px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-sign-in-alt me-2" style="font-size: 1.2rem;"></i> Confirm Check-In
                    </button>
                </form>
            </div>

            <p class="mt-4 text-muted small">
                <i class="fas fa-map-marker-alt me-1"></i> Location tracking is active for attendance.
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Live Clock Functionality
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const clockEl = document.getElementById('live-clock');
            if (clockEl) clockEl.textContent = `${hours}:${minutes}:${seconds}`;
        }
        
        setInterval(updateClock, 1000);
        updateClock();

        const checkinForm = document.getElementById('checkin-form');

        checkinForm.onsubmit = function(e) {
            e.preventDefault();
            performCheckIn();
        };

        function performCheckIn(reason = '') {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Checking In...';

            $.ajax({
                url: "{{ route('employee.checkin.submit') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    shift_id: "{{ $shift->shift_id }}",
                    late_reason: reason
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'You have checked in successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "{{ route('employee.dashboard') }}";
                    });
                },
                error: function(xhr) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Confirm Check-In';

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
                                performCheckIn(result.value);
                            }
                        });
                    } else {
                        Swal.fire('Error', msg, 'error');
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection