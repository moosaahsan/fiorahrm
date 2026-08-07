@extends('admin.layouts.app')

@section('title', 'Team Policy Overrides')

@section('breadcrumb')
<div class="col-sm-6 text-left">
    <h4 class="page-title directory-header">Team Policy Overrides</h4>
    <ol class="breadcrumb saas-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="javascript:void(0);">Settings</a></li>
        <li class="breadcrumb-item active">Employee Settings</li>
    </ol>
</div>
@endsection

@section('content')
<div class="employee-settings-page">
<div class="container-fluid">
    <div class="row">
        <!-- Left: Team Selector -->
        <div class="col-lg-4">
            <div class="saas-card" style="max-height: 80vh; overflow-y: auto;">
                <div class="section-tag"><i class="fas fa-users"></i> Selection Pool (Teams)</div>
                <div id="team-list">
                    @foreach($teams as $team)
                    <div class="team-item" data-id="{{ $team->id }}" data-name="{{ $team->name }}">
                        <div>
                            <span class="font-weight-bold">{{ $team->name }}</span>
                            <div class="small text-muted">{{ $team->branch->name ?? 'Main Branch' }}</div>
                        </div>
                        <span class="badge-count">{{ $team->employees_count }} Members</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right: Settings Form -->
        <div class="col-lg-8">
            <div class="saas-card" id="settings-card" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="section-tag mb-0"><i class="fas fa-sliders-h"></i> Intelligence Configuration</div>
                    <div class="tw-badge px-3 py-2 bg-brand-50 text-brand-700" id="selected-team-badge" style="border-radius: 10px;">Select a team</div>
                </div>

                <form id="team-settings-form">
                    <input type="hidden" name="team_id" id="team_id_input">
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted">Break Duration (m)</label>
                                <input type="number" class="saas-input" name="break_duration" value="60" required>
                                <div class="small text-muted mt-1">Total allowed break time in minutes.</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted">Late Grace (m)</label>
                                <input type="number" class="saas-input" name="late_minutes_margin" value="5" required>
                                <div class="small text-muted mt-1">Grace period before being marked late.</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted">Yearly Leaves</label>
                                <input type="number" class="saas-input" name="leaves_allowed_in_year" value="16" required>
                                <div class="small text-muted mt-1">Annual paid leave quota for employees.</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted">Idle Limit (m)</label>
                                <input type="number" class="saas-input" name="idle_time_allowed" value="5" required>
                                <div class="small text-muted mt-1">Inactivity threshold on desktop app.</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted">Full Day Allowance</label>
                                <input type="number" class="saas-input" name="number_full_days_allowed_in_month" value="0" required>
                                <div class="small text-muted mt-1">Excused full days allowed per month.</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted">Half Day Allowance</label>
                                <input type="number" class="saas-input" name="number_half_days_allowed_in_month" value="0" required>
                                <div class="small text-muted mt-1">Excused half days allowed per month.</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-soft-warning border-0 mt-2 mb-4" style="border-radius: 16px; background: rgba(255, 193, 7, 0.1);">
                        <div class="d-flex gap-3">
                            <i class="fas fa-exclamation-triangle mt-1"></i>
                            <div class="small">
                                <strong>Important Note:</strong> Applying these settings will override individual configurations for <strong>all members</strong> in this team immediately.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-apply" id="apply-btn">
                        <i class="fas fa-sync-alt"></i> Synchronize Poly Overrides
                    </button>
                </form>

                <!-- Individual Overrides Table -->
                <div id="outliers-section" style="display: none; margin-top: 3rem; border-top: 2px dashed #f1f5f9; padding-top: 2rem;">
                    <div class="section-tag"><i class="fas fa-user-edit"></i> Individual Overrides Detected</div>
                    <p class="small text-muted mb-4">The following employees have unique configurations that differ from the suggested team policy above. Applying the team policy will overwrite these.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <thead class="bg-light" style="border-radius: 10px;">
                                <tr>
                                    <th class="px-3 py-2 small font-weight-bold">Employee</th>
                                    <th class="px-3 py-2 small font-weight-bold">Custom Values</th>
                                    <th class="px-3 py-2 small font-weight-bold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody id="outliers-list">
                                <!-- Dynamic outliers -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div class="saas-card" id="empty-state">
                <div class="empty-state">
                    <i class="fas fa-users-cog"></i>
                    <h3>Welcome to Batch Policy Manager</h3>
                    <p class="text-muted">Select a team from the left to configure intelligence policy overrides in bulk. You can adjust durations, margins, and allowances for the entire team in one action.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.team-item').click(function() {
            const teamId = $(this).data('id');
            const teamName = $(this).data('name');
            
            $('.team-item').removeClass('active');
            $(this).addClass('active');
            
            $('#empty-state').hide();
            $('#settings-card').fadeIn();
            $('#selected-team-badge').text(teamName);
            $('#team_id_input').val(teamId);
            
            // Show loading state
            const $btn = $('#apply-btn');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Fetching Current Poly...');
            
            // Load current settings from first member
            $.get(`{{ url('admin/settings/employee/team') }}/${teamId}`, function(resp) {
                const data = resp.settings;
                const outliers = resp.outliers;

                for (let key in data) {
                    $(`input[name="${key}"]`).val(data[key]);
                }

                // Render Outliers
                const $outliersSection = $('#outliers-section');
                const $outliersList = $('#outliers-list');
                $outliersList.empty();

                if (outliers && outliers.length > 0) {
                    outliers.forEach(function(emp) {
                        let diffTags = '';
                        emp.diffs.forEach(function(diff) {
                            diffTags += `<span class="tw-badge-warning mr-1" style="font-size: 0.7rem;">${diff.label}: ${diff.value}</span>`;
                        });

                        $outliersList.append(`
                            <tr>
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-weight-bold" style="font-size: 0.85rem;">${emp.name}</div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    ${diffTags}
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <a href="/admin/employees/${emp.id}/edit" target="_blank" class="tw-btn-secondary text-sm" style="border-radius: 8px;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        `);
                    });
                    $outliersSection.fadeIn();
                } else {
                    $outliersSection.hide();
                }

                $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Synchronize Poly Overrides');
            }).fail(function() {
                toastr.error('Failed to load team settings.');
                $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Synchronize Poly Overrides');
            });
        });

        $('#team-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $btn = $('#apply-btn');
            const oldHtml = $btn.html();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will bulk update policy settings for all employees in this team!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Apply to Team'
            }).then((result) => {
                if (result.isConfirmed) {
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Synchronizing Team Policy...');
                    
                    $.ajax({
                        url: "{{ route('admin.settings.applyToTeam') }}",
                        method: "POST",
                        data: $(this).serialize() + "&_token={{ csrf_token() }}",
                        success: function(resp) {
                            if (resp.success) {
                                Swal.fire('Success!', resp.message, 'success');
                            } else {
                                toastr.error(resp.message || 'Operation failed.');
                            }
                            $btn.prop('disabled', false).html(oldHtml);
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false).html(oldHtml);
                            if (xhr.status === 422) {
                                toastr.error(xhr.responseJSON.message || 'Validation error.');
                            } else {
                                toastr.error('An internal synchronization error occurred.');
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
@endsection
