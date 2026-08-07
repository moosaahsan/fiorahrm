@php
    use Carbon\Carbon;
    $employee = $data;
    $shift = optional($employee->shifts->first());
    $fallbackImg = "https://ui-avatars.com/api/?name=" . urlencode($employee->name) . "&background=6366f1&color=fff&size=200";
    $profilePic = $employee->profile_pic ? Storage::disk('public')->url($employee->profile_pic) : $fallbackImg;

    // Prepare settings array with fallbacks
    $settings = $employee->settings->pluck('setting_value', 'setting_name')->toArray();
    
    $displaySettings = [
        ['label' => 'Break Duration', 'value' => ($settings['break_duration'] ?? $employee->break_duration ?? '45') . ' min', 'icon' => 'fas fa-coffee', 'color' => '#6366f1'],
        ['label' => 'Half-Day Break', 'value' => ($employee->break_allowed_in_half_day ?? '30') . ' min', 'icon' => 'fas fa-hamburger', 'color' => '#8b5cf6'],
        ['label' => 'Idle Limit', 'value' => ($settings['idle_time_allowed'] ?? $employee->idle_time_allowed ?? '5') . ' min', 'icon' => 'fas fa-couch', 'color' => '#ec4899'],
        ['label' => 'Full Day Cap', 'value' => ($settings['full_day_allowed_in_month'] ?? $employee->number_full_days_allowed_in_month ?? '2') . ' days', 'icon' => 'fas fa-sun', 'color' => '#f59e0b'],
        ['label' => 'Half Day Cap', 'value' => ($settings['half_day_allowed_in_month'] ?? $employee->number_half_days_allowed_in_month ?? '4') . ' days', 'icon' => 'fas fa-cloud-sun', 'color' => '#10b981'],
        ['label' => 'Late Grace', 'value' => ($settings['late_grace_minutes'] ?? $employee->late_minutes_margin ?? '5') . ' min', 'icon' => 'fas fa-user-clock', 'color' => '#ef4444'],
        ['label' => 'Mark half day', 'value' => ($settings['mark_half_day_after'] ?? '120') . ' min', 'icon' => 'fas fa-stopwatch', 'color' => '#06b6d4'],
        ['label' => 'Response Grace', 'value' => ($settings['app_resp_grace_minutes'] ?? '1') . ' min', 'icon' => 'fas fa-reply', 'color' => '#3b82f6'],
        ['label' => 'Time Zone', 'value' => $settings['time_zone'] ?? 'Asia/Karachi', 'icon' => 'fas fa-globe-asia', 'color' => '#64748b'],
        ['label' => 'Annual Leaves', 'value' => ($settings['leaves_allowed_in_year'] ?? $employee->leaves_allowed_in_year ?? '16') . ' days', 'icon' => 'fas fa-calendar-alt', 'color' => '#4338ca'],
    ];
@endphp


<script>
    $('#shift_update_ajax_modal .modal-dialog').addClass('modal-lg modal-dialog-centered').css('max-width', '850px');
    $('#shift_update_ajax_modal').on('hidden.bs.modal', function () {
        $(this).find('.modal-dialog').removeClass('modal-lg').css('max-width', '');
    });
</script>

<div class="modal-content elite-view-content text-left">
    <div class="banner-elite">
        <button type="button" class="close position-absolute text-white" style="right: 20px; top: 20px; z-index: 100; font-size: 2rem; opacity: 0.8; text-shadow: 0 2px 4px rgba(0,0,0,0.3);" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="modal-body p-0">
        <div class="header-content-wrapper">
            <img src="{{ $profilePic }}" class="profile-img-elite" alt="User" onerror="this.onerror=null;this.src='{{ $fallbackImg }}';">
            <h3 class="name-elite">{{ $employee->name }}</h3>
            <span class="pos-badge-elite"><i class="fas fa-id-badge"></i> {{ $employee->position }}</span>
            @if($employee->resign_date)
                @php
                    $exitLabel = match ($employee->exit_type) {
                        'terminated' => 'Terminated',
                        'suspended' => 'Suspended',
                        default => 'Resigned',
                    };
                    $badgeClass = match ($employee->exit_type) {
                        'terminated' => 'badge-dark',
                        'suspended' => 'badge-warning',
                        default => 'badge-danger',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} ml-2 fw-bold" style="padding: 6px 14px; border-radius: 50px; font-size: 0.85rem; vertical-align: middle;">{{ $exitLabel }}</span>
            @endif
        </div>

        <div class="px-5 pb-5 pt-4">
            <!-- Basic Stats Row -->
            <div class="row g-3 mb-5">
                <div class="col-md-4">
                    <div class="contact-card-elite">
                        <div class="icon-wrap-elite" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <span class="policy-label">Email</span>
                            <span class="value-text fw-bold text-dark d-block text-truncate" style="font-size: 0.85rem; max-width: 180px;" title="{{ $employee->email }}">{{ $employee->email }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card-elite">
                        <div class="icon-wrap-elite" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <span class="policy-label">Contact</span>
                            <span class="value-text fw-bold text-dark d-block">{{ $employee->contact_no }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card-elite">
                        <div class="icon-wrap-elite" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <span class="policy-label">Joined</span>
                            <span class="value-text fw-bold text-dark d-block">{{ Carbon::parse($employee->joining_date)->format('d M, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($employee->resign_date)
            <!-- Exit Details -->
            @php
                $isTerminated = $employee->exit_type === 'terminated';
                $isSuspended = $employee->exit_type === 'suspended';
                $sectionTitle = $isSuspended ? 'Suspension Details' : ($isTerminated ? 'Termination Details' : 'Resignation Details');
                $alertClass = $isSuspended ? 'alert-warning' : ($isTerminated ? 'alert-danger' : 'alert-warning');
                $alertStyle = $isSuspended
                    ? 'border-radius: 20px; border: 1px solid #fde68a; background-color: #fffbeb; color: #92400e; padding: 20px;'
                    : ($isTerminated
                        ? 'border-radius: 20px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; padding: 20px;'
                        : 'border-radius: 20px; border: 1px solid #ffeeba; background-color: #fff3cd; color: #856404; padding: 20px;');
                $exitText = $isSuspended ? 'Suspended from' : ($isTerminated ? 'Terminated on' : 'Resigned on');
            @endphp
            <div class="section-title-elite"><i class="fas fa-user-slash"></i> {{ $sectionTitle }}</div>
            <div class="alert {{ $alertClass }} mb-5" style="{{ $alertStyle }}">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 1.75rem; margin-right: 10px;"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <div class="fw-bold" style="font-size: 1rem;">{{ $exitText }}: {{ Carbon::parse($employee->resign_date)->format('d M, Y') }}</div>
                        @if($isSuspended && $employee->suspended_end_date)
                            <div class="mt-1 small" style="font-size: 0.9rem;"><strong>Until:</strong> {{ Carbon::parse($employee->suspended_end_date)->format('d M, Y') }}</div>
                        @endif
                        @if($employee->resign_reason)
                            <div class="mt-2 small" style="font-size: 0.9rem; line-height: 1.4;"><strong>Reason/Notes:</strong> {{ $employee->resign_reason }}</div>
                        @else
                            <div class="mt-2 small text-muted font-italic" style="font-size: 0.9rem;">No reason or notes provided.</div>
                        @endif
                        @if(!$isSuspended && $employee->served_notice !== null)
                            <div class="mt-2 small" style="font-size: 0.9rem; line-height: 1.4;">
                                <strong>Served Notice Period:</strong> 
                                <span class="fw-bold {{ $employee->served_notice ? 'text-success' : 'text-danger' }}">
                                    {{ $employee->served_notice ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        @endif
                        @if($employee->resignedBy)
                            <div class="mt-2 small" style="font-size: 0.9rem; line-height: 1.4;">
                                <strong>Offboarded By:</strong> 
                                <span class="fw-bold">{{ $employee->resignedBy->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if (auth()->user()->hasRole(['admin', 'administrator']))
            <!-- Salary Section - Admin Only -->
            <div class="section-title-elite"><i class="fas fa-money-bill-wave"></i> Salary & Compensation (Admin Only)</div>
            <div class="shift-bar-elite mb-5" style="background: #f8fafc; border: 2px solid #10b981; border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        @if ($employee->salary > 0)
                            <span class="policy-label text-success">Monthly Salary</span>
                            <h4 class="mb-1 fw-800 text-success" style="font-weight: 800;">Rs. {{ number_format($employee->salary, 2) }}</h4>
                        @else
                            <span class="policy-label text-muted">Compensation Details</span>
                            <h4 class="mb-1 fw-800 text-muted" style="font-weight: 800;">Not Specified</h4>
                            <p class="text-muted small mb-0 fw-600">Please edit employee to assign a salary.</p>
                        @endif
                    </div>
                    <div class="text-end">
                        <i class="fas fa-wallet fa-2x text-success" style="opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
            @endif

            <!-- Official Work Shift -->
            <div class="section-title-elite"><i class="fas fa-clock"></i> Active Work Schedule</div>
            <div class="shift-bar-elite mb-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-800" style="color: #0f172a;">{{ $shift->shift_name ?? 'No active shift' }}</h4>
                        @if($shift)
                        <p class="text-muted small mb-0 fw-600">
                            <i class="far fa-clock me-1 text-primary"></i> 
                            {{ Carbon::createFromFormat('H:i:s', $shift->start_time)->format('h:i A') }} — 
                            {{ Carbon::createFromFormat('H:i:s', $shift->end_time)->format('h:i A') }}
                        </p>
                        @endif
                    </div>
                    <div class="text-end">
                        <i class="fas fa-user-clock fa-2x opacity-25"></i>
                    </div>
                </div>
            </div>

            <!-- Policy & Constraints Grid -->
            <div class="section-title-elite"><i class="fas fa-shield-alt"></i> Policy Configurations</div>
            <div class="policy-grid">
                @foreach($displaySettings as $item)
                    <div class="policy-item-elite">
                        <div class="policy-icon" style="color: {{ $item['color'] }}">
                            <i class="{{ $item['icon'] }}"></i>
                        </div>
                        <span class="policy-label">{{ $item['label'] }}</span>
                        <span class="policy-value">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 p-4 bg-light justify-content-center">
        <button type="button" class="btn btn-dark px-5 py-2 fw-bold" data-dismiss="modal" style="border-radius: 12px;">Close Profile</button>
    </div>
</div>