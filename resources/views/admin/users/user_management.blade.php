@extends('admin.layouts.app')

@section('title', 'Admin Management Control Center')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="matrix-hub-canvas">
            <div class="hub-header">
                <div class="hub-title">
                    <h1>Admin Management</h1>
                    <div class="hub-subtitle">Authorize elevated personnel and manage global security protocols</div>
                </div>
                <button class="btn-matrix-hub" data-toggle="modal" data-target="#createAdminModal">
                    <i class="fas fa-user-plus"></i> Initialize New Admin
                </button>
            </div>

            <div class="saas-table-container">
                <table class="table saas-table" id="users-table">
                    <thead>
                        <tr>
                            <th>User Identity</th>
                            <th>Linked Personnel / Alias</th>
                            <th>Access Tiers</th>
                            <th>Entry Date</th>
                            <th class="text-end">Command Center</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content matrix-modal">
            <div class="modal-header matrix-border">
                <div class="d-flex align-items-center">
                    <div class="identity-shield-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h5 class="modal-title m-0">Initialize New Admin</h5>
                        <p class="text-muted small m-0">Deploy elevated security clearance</p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-white">&times;</span>
                </button>
            </div>
            <form id="createAdminForm">
                @csrf
                <div class="modal-body">
                    <div class="saas-form-group">
                        <label class="saas-label">Legal Identity</label>
                        <div class="saas-input-wrapper">
                            <i class="far fa-user"></i>
                            <input type="text" name="name" class="saas-form-control" placeholder="Full legal name" required>
                        </div>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">System Alias (Email)</label>
                        <div class="saas-input-wrapper">
                            <i class="far fa-envelope"></i>
                            <input type="email" name="email" class="saas-form-control" placeholder="admin@enterprise.domain" required>
                        </div>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">Authorization Token (Password)</label>
                        <div class="saas-input-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" name="password" class="saas-form-control" placeholder="Generate secure key" required>
                        </div>
                        <p class="text-muted xxs mt-2 opacity-50">Minimum 8 characters with multi-factor entropy recommended.</p>
                    </div>
                </div>
                <div class="modal-footer matrix-border">
                    <button type="button" class="btn btn-link text-muted fw-bold" data-dismiss="modal">Abort Mission</button>
                    <button type="submit" class="btn-matrix-hub">Initialize Access</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Role Modal -->
<div class="modal fade" id="changeRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content matrix-modal">
            <div class="modal-header matrix-border">
                <div class="d-flex align-items-center">
                    <div class="identity-shield-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <h5 class="modal-title m-0">Modify Access Layer</h5>
                        <p class="text-muted small m-0">Update personnel authorization level</p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-white">&times;</span>
                </button>
            </div>
            <form id="changeRoleForm">
                @csrf
                <input type="hidden" name="user_id" id="role_user_id">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Target Personnel</div>
                        <h3 id="role_user_name" class="fw-bold tracking-tight text-white m-0"></h3>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">Required Capability Level</label>
                        <div class="saas-input-wrapper">
                            <i class="fas fa-layer-group"></i>
                            <select name="role" class="saas-form-control" required style="appearance: none;">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ strtoupper($role->name) }} SECURITY LAYER</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">Monitoring Scope (Team Cluster)</label>
                        <div class="saas-input-wrapper">
                            <i class="fas fa-network-wired"></i>
                            <select name="team_ids[]" id="managed_teams_select" class="saas-form-control select2" multiple="multiple">
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-muted small mt-2 opacity-50">Personnel assigned to these teams will be visible to this user.</p>
                    </div>
                </div>
                <div class="modal-footer matrix-border">
                    <button type="button" class="btn btn-link text-muted fw-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-matrix-hub">Update Access Layer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content matrix-modal">
            <div class="modal-header matrix-border">
                <div class="d-flex align-items-center">
                    <div class="identity-shield-icon" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.1);">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h5 class="modal-title m-0">Recalibrate Access</h5>
                        <p class="text-muted small m-0">Update authorization token for <span id="reset_user_name_display" class="text-warning font-weight-bold"></span></p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-white">&times;</span>
                </button>
            </div>
            <form id="resetPasswordForm">
                @csrf
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <div class="saas-form-group">
                        <label class="saas-label text-warning">New Authorization Token</label>
                        <div class="saas-input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="saas-form-control" placeholder="Enter new password" required>
                        </div>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label text-warning">Confirm Recalibration</label>
                        <div class="saas-input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password_confirmation" class="saas-form-control" placeholder="Repeat new password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer matrix-border">
                    <button type="button" class="btn btn-link text-muted fw-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-matrix-hub" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;">Recalibrate Token</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            // Check if table already exists (safety)
            if ($.fn.DataTable.isDataTable('#users-table')) {
                $('#users-table').DataTable().destroy();
            }

            var table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                retrieve: true,
                ajax: "{{ route('admin.user_management.data') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'linked_personnel', name: 'linked_personnel' },
                    { data: 'roles_list', name: 'roles_list' },
                    { data: 'created_at', name: 'created_at', render: function(data) {
                        return new Date(data).toLocaleDateString();
                    }},
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ],
                order: [[3, 'desc']],
                language: {
                    paginate: {
                        next: '<i class="fas fa-chevron-right"></i>',
                        previous: '<i class="fas fa-chevron-left"></i>'
                    },
                    processing: '<div class="matrix-loader">Decrypting Identity Hub...</div>'
                }
            });

            // Create Admin
            $('#createAdminForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('admin.user_management.store_admin') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        $('#createAdminModal').modal('hide');
                        $('#createAdminForm')[0].reset();
                        table.draw();
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON.message || 'Operation failed', 'error');
                    }
                });
            });

            // Promote to Admin
            $(document).on('click', '.promote-user', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                Swal.fire({
                    title: 'Authorize Elevate?',
                    text: `Promote ${name} to Administrative protocols?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    confirmButtonText: 'Yes, Authorize'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`/admin/user-management/promote/${id}`, {_token: "{{ csrf_token() }}"}, function(res) {
                            Swal.fire('Authorized', res.message, 'success');
                            table.draw();
                        });
                    }
                });
            });

            // Change Role Logic with Multi-Team Support
            $(document).on('click', '.change-role', function() {
                var userId = $(this).data('id');
                var currentRole = $(this).data('role');
                $('#role_user_id').val(userId);
                $('#role_user_name').text($(this).data('name'));
                
                // Set current role in select
                if (currentRole) {
                    $('#changeRoleForm select[name="role"]').val(currentRole);
                }

                // Reset select2
                $('#managed_teams_select').val(null).trigger('change');

                // Initialize Select2 if not already done
                if (!$('#managed_teams_select').hasClass("select2-hidden-accessible")) {
                    $('#managed_teams_select').select2({
                        placeholder: "Monitoring Scope (Optional)",
                        width: '100%',
                        dropdownParent: $('#changeRoleModal')
                    });
                }

                // Fetch current monitoring cluster
                $.get(`/admin/user-management/user-teams/${userId}`, function(teamIds) {
                    $('#managed_teams_select').val(teamIds).trigger('change');
                });

                $('#changeRoleModal').modal('show');
            });

            $('#changeRoleForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#role_user_id').val();
                $.ajax({
                    url: `/admin/user-management/update-role/${id}`,
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire('Identity Reprofiled', res.message, 'success');
                        $('#changeRoleModal').modal('hide');
                        table.draw();
                    }
                });
            });

            // Reset Password Logic
            $(document).on('click', '.reset-password', function() {
                $('#reset_user_id').val($(this).data('id'));
                $('#reset_user_name_display').text($(this).data('name'));
                $('#resetPasswordModal').modal('show');
            });

            $('#resetPasswordForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#reset_user_id').val();
                $.ajax({
                    url: `/admin/user-management/update-password/${id}`,
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire('Token Recalibrated', res.message, 'success');
                        $('#resetPasswordModal').modal('hide');
                        $('#resetPasswordForm')[0].reset();
                    },
                    error: function(err) {
                        Swal.fire('Protocol Error', err.responseJSON.message || 'Verification failed', 'error');
                    }
                });
            });

            // Identity Termination (Delete Admin)
            $(document).on('click', '.delete-admin', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                Swal.fire({
                    title: 'Purge Identity?',
                    text: `Are you sure you want to terminate ${name}? This action is irreversible and will revoke all administrative access.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Confirm Termination'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/user-management/terminate/${id}`,
                            method: "DELETE",
                            data: { _token: "{{ csrf_token() }}" },
                            success: function(res) {
                                table.draw();
                                Swal.fire('Identity Purged', res.message, 'success');
                            },
                            error: function(err) {
                                Swal.fire('Termination Aborted', err.responseJSON.message || 'Verification failed', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
