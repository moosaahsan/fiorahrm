@extends('employee.layouts.app')

@section('content')
<div class="employee-portal-page">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="page-title mb-0">Profile Update</h4>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('employee.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Profile Update</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Material Design Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">

    <div class="profile-container">
        <form id="profileForm" enctype="multipart/form-data">
            @csrf
            
            <div class="profile-card">
                <!-- Banner -->
                <div class="profile-banner">
                    <div class="banner-shapes">
                        <div class="banner-shape" style="width: 200px; height: 200px; top: -50px; left: -50px;"></div>
                        <div class="banner-shape" style="width: 150px; height: 150px; bottom: 20px; right: 20px;"></div>
                    </div>
                </div>

                <!-- Header Info -->
                <div class="profile-header-content">
                    <div class="avatar-wrapper">
                        @php
                            $profilePic = $user->profile_pic
                                ? Storage::disk('public')->url($user->profile_pic)
                                : "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=6366f1&color=fff&size=200";
                        @endphp
                        <img id="profilePreview" src="{{ $profilePic }}" class="avatar-image" alt="Profile">
                    </div>
                    <h2 class="user-name">{{ $user->name }}</h2>
                    <p class="user-email">{{ $user->email }}</p>
                </div>

                <div class="form-section pt-0">
                    <div class="row">
                        <!-- Personal Details -->
                        <div class="col-md-4 mb-4">
                            <h5 class="section-title">
                                <i class="mdi mdi-account-outline text-primary"></i> Personal Information
                            </h5>
                            
                            <div class="form-group mb-3">
                                <label>Full Name</label>
                                <input type="text" value="{{ $user->name }}" class="form-control-premium" readonly style="opacity: 0.8; cursor: default;">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Email Address</label>
                                <input type="email" value="{{ $user->email }}" class="form-control-premium" readonly style="opacity: 0.8; cursor: default;">
                            </div>

                            <div class="form-group mb-3">
                                <label>Contact Number</label>
                                <input type="text" value="{{ $employee?->contact_no ?? $user->contact_no }}" class="form-control-premium" readonly style="opacity: 0.8; cursor: default;">
                            </div>
                        </div>

                        <!-- Work Schedule -->
                        <div class="col-md-4 mb-4">
                            <h5 class="section-title">
                                <i class="mdi mdi-clock-outline text-primary"></i> Active Work Schedule
                            </h5>
                            
                            <div class="form-group mb-3">
                                <label>Shift Name</label>
                                <input type="text" value="{{ $currentShiftAssignment?->shift?->shift_name ?? 'Not Assigned' }}" class="form-control-premium" readonly style="opacity: 0.8; cursor: default;">
                            </div>

                            <div class="form-group mb-3">
                                <label>Timings</label>
                                <div class="form-control-premium d-flex align-items-center bg-light" style="opacity: 0.9; cursor: default; height: auto; min-height: 45px;">
                                    @if($currentShiftAssignment?->shift)
                                        <span class="text-primary font-weight-bold">
                                            {{ \Carbon\Carbon::parse($currentShiftAssignment->shift->start_time)->format('h:i A') }}
                                        </span>
                                        <span class="mx-2 text-muted">—</span>
                                        <span class="text-primary font-weight-bold">
                                            {{ \Carbon\Carbon::parse($currentShiftAssignment->shift->end_time)->format('h:i A') }}
                                        </span>
                                    @else
                                        <span class="text-muted">No schedule found</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Account Status</label>
                                <div class="badge badge-success px-3 py-2" style="border-radius: 8px; font-size: 0.9rem; width: 100%; text-align: left;">
                                    <i class="mdi mdi-check-circle me-1"></i> Active
                                </div>
                            </div>
                        </div>

                        <!-- Policy Configurations -->
                        <div class="col-md-4 mb-4">
                            <h5 class="section-title">
                                <i class="mdi mdi-shield-check-outline text-primary"></i> Policy Configurations
                            </h5>
                            
                            <div class="policy-list bg-light p-3 rounded" style="border: 1px solid #e2e8f0;">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Break Duration</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['break_duration'] ?? 30 }} min</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Half-Day Break</span>
                                    <span class="font-weight-bold small text-dark">{{ $currentShiftAssignment?->shift?->halfday_break ?? (int)(($settings['break_duration'] ?? 30)/2) }} min</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Idle Limit</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['idle_time_allowed'] ?? 5 }} min</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Full Day Cap</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['full_day_allowed_in_month'] ?? 2 }} days</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Half Day Cap</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['half_day_allowed_in_month'] ?? 2 }} days</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Late Grace</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['late_grace_minutes'] ?? 5 }} min</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Mark Half Day</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['mark_half_day_after'] ?? 120 }} min</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Response Grace</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['app_resp_grace_minutes'] ?? 1 }} min</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Time Zone</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['time_zone'] ?? 'Asia/Karachi' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Annual Leaves</span>
                                    <span class="font-weight-bold small text-dark">{{ $settings['leaves_allowed_in_year'] ?? 16 }} days</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

@endsection

@push('scripts')
<script>
    // View-only mode for employee profile
    console.log('Profile loaded in view-only mode.');
</script>
@endpush
