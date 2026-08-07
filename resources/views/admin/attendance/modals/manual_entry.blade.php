@php
    $isEdit = isset($attendance);
    $currentStatus = $isEdit ? $attendance->status : 'Present';
    if ($isEdit && $attendance->halfDay) $currentStatus = 'Half Day';
    if ($isEdit && $attendance->lateArrival) $currentStatus = 'Late';
@endphp

<div class="hrm-modal-container">
    <div class="modal-header hrm-modal-header">
        <div>
            <h5 class="modal-title hrm-modal-title">
                <i class="bi bi-shield-check text-indigo-400" style="font-size: 1.8rem;"></i>
                <div>
                   {{ $isEdit ? 'Sync Attendance Stream' : 'Engine: Manual Entry' }}
                   <span class="hrm-modal-subtitle">Enterprise Resource Intelligence System</span>
                </div>
            </h5>
        </div>
        <button type="button" class="close text-white shadow-none" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <form id="hrmManualAttendanceForm">
        @csrf
        <div class="modal-body p-4 bg-light">
            <div class="container-fluid p-0">
                <div class="row g-4">
                    <!-- Identity Hub -->
                    <div class="col-md-7">
                        <div class="hrm-form-section h-100">
                             <label class="hrm-form-label"><i class="bi bi-fingerprint"></i> Staff Member</label>
                             <div class="hrm-input-container">
                                 <div class="hrm-input-group">
                                    <select name="employee_id" id="modal-employee" class="hrm-input select2-modal" required {{ $isEdit ? 'disabled' : '' }}>
                                        <option value="">-- Authenticate Staff --</option>
                                        @foreach($employees as $emp)
                                            @php
                                                $hasPic = !empty($emp->profile_pic);
                                                // Using relative path for maximum reliability in both local/ip environments
                                                $avatarPath = $hasPic ? asset('public_storage/' . $emp->profile_pic) : "https://ui-avatars.com/api/?name=" . urlencode($emp->name) . "&background=6366f1&color=fff";
                                            @endphp
                                            <option value="{{ $emp->id }}" 
                                                    data-avatar="{{ $avatarPath }}"
                                                    data-name="{{ $emp->name }}"
                                                    {{ ($isEdit && $attendance->emp_id == $emp->id) ? 'selected' : '' }}>
                                                {{ $emp->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- Shift Lock -->
                    <div class="col-md-5">
                        <div class="hrm-form-section h-100">
                             <label class="hrm-form-label"><i class="bi bi-clock-history"></i> Auto-Resolved Shift</label>
                             <div class="hrm-input-group" id="modal-shift-group" style="border: 2px dashed #ddd; background: #fafafa;">
                                <select name="shift_id" id="modal-shift" class="hrm-input" {{ $isEdit ? '' : 'disabled' }}>
                                    @if($isEdit)
                                        @foreach($shifts as $s)
                                            <option value="{{ $s->id }}" 
                                                    data-start="{{ $s->start_time ? date('H:i', strtotime($s->start_time)) : '' }}" 
                                                    data-end="{{ $s->end_time ? date('H:i', strtotime($s->end_time)) : '' }}"
                                                    {{ $attendance->shift_id == $s->id ? 'selected' : '' }}>
                                                {{ $s->shift_name }} [{{ $s->start_time }}]
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">Awaiting Resolution...</option>
                                        @foreach($shifts as $s)
                                            <option value="{{ $s->id }}" 
                                                    data-start="{{ $s->start_time ? date('H:i', strtotime($s->start_time)) : '' }}" 
                                                    data-end="{{ $s->end_time ? date('H:i', strtotime($s->end_time)) : '' }}">
                                                {{ $s->shift_name }} [{{ $s->start_time }}]
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                             </div>
                             <div id="shift-source-container"></div>
                        </div>
                    </div>

                    <!-- Timeline & Status -->
                    <div class="col-md-6">
                        <div class="hrm-form-section">
                             <label class="hrm-form-label"><i class="bi bi-calendar3"></i> Record Date</label>
                             <div class="hrm-input-group">
                                 <input type="date" name="shift_date" class="hrm-input" required value="{{ $isEdit ? $attendance->shift_date->toDateString() : date('Y-m-d') }}" {{ $isEdit ? 'readonly' : '' }}>
                             </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="hrm-form-section">
                             <label class="hrm-form-label"><i class="bi bi-activity"></i> Work Status</label>
                             <div class="hrm-input-group">
                                 <select name="status" id="modal-status-toggle" class="hrm-input" required>
                                     <option value="Present" {{ $currentStatus == 'Present' ? 'selected' : '' }}>Present (Active)</option>
                                     <option value="Absent" {{ $currentStatus == 'Absent' ? 'selected' : '' }}>Absent (Deducted)</option>
                                     <option value="Absent (Unpaid)" {{ $currentStatus == 'Absent (Unpaid)' ? 'selected' : '' }}>Absent (Unpaid)</option>
                                     <option value="Half Day" {{ $currentStatus == 'Half Day' ? 'selected' : '' }}>Half Day (0.5)</option>
                                     <option value="Late" {{ $currentStatus == 'Late' ? 'selected' : '' }}>Late (Exceeded)</option>
                                 </select>
                             </div>
                        </div>
                    </div>

                    <!-- Precision Time Metrics -->
                    <div class="col-md-12 time-fields-section" style="{{ in_array($currentStatus, ['Absent', 'Absent (Unpaid)']) ? 'display:none;' : '' }}">
                        <div class="hrm-form-section bg-white border">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="hrm-form-label text-primary"><i class="bi bi-box-arrow-in-right"></i> Precision In</label>
                                    <div class="hrm-input-group">
                                        <input type="time" name="check_in" class="hrm-input" value="{{ ($isEdit && $attendance->check_in) ? $attendance->check_in->format('H:i') : '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="hrm-form-label text-danger"><i class="bi bi-box-arrow-right"></i> Precision Out</label>
                                    <div class="hrm-input-group">
                                        <input type="time" name="check_out" class="hrm-input" value="{{ ($isEdit && $attendance->check_out) ? $attendance->check_out->format('H:i') : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks Hub -->
                    <div class="col-md-12">
                        <div class="hrm-form-section">
                            <label class="hrm-form-label"><i class="bi bi-chat-left-dots"></i> Analysis Notes</label>
                            <div class="hrm-input-group">
                                <textarea name="reason" class="hrm-input" rows="2" placeholder="Document the analytical reason for this manual entry...">{{ $isEdit ? ($attendance->lateArrival->late_reason ?? ($attendance->halfDay->reason ?? '')) : '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer hrm-modal-footer">
            <button type="button" class="btn btn-cancel-elite" data-dismiss="modal">Discard Changes</button>
            <button type="submit" class="btn btn-save-elite" id="btnHrmSaveAttendance">
                <i class="bi bi-lightning-fill"></i> {{ $isEdit ? 'Sync Architecture' : 'Generate Record' }}
            </button>
        </div>
        @if($isEdit)
            <input type="hidden" name="employee_id" value="{{ $attendance->emp_id }}">
        @endif
    </form>
</div>

<script>
    $(document).ready(function() {
        // Initialize Select2 with premium theme and Avatar support
        if ($.fn.select2) {
            function formatStaff(state) {
                if (!state.id || !state.element) return state.text;
                
                // Using getAttribute for better compatibility with data-attributes in Select2
                const avatar = state.element.getAttribute('data-avatar');
                const name = state.element.getAttribute('data-name') || state.text;
                
                if (!avatar) return state.text;

                return $(
                    `<div class="d-flex align-items-center gap-3">
                        <img src="${avatar}" 
                             onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6366f1&color=fff';"
                             class="rounded-circle border border-2 shadow-sm" 
                             style="width: 32px; height: 32px; object-fit: cover; display: block;" 
                             width="32" height="32" />
                        <span class="fw-600">${state.text}</span>
                    </div>`
                );
            };

            $('.select2-modal').select2({
                dropdownParent: $('#ajaxModal'),
                width: '100%',
                templateResult: formatStaff,
                templateSelection: formatStaff
            });
        }

        // Status Animation Hub
        $('#modal-status-toggle').on('change', function() {
            const val = $(this).val();
            if (val === 'Absent' || val === 'Absent (Unpaid)') {
                $('.time-fields-section').slideUp(400);
            } else {
                $('.time-fields-section').slideDown(400);
            }
        });

        // Intelligence Resolution Engine
        function runIntelligenceShiftResolution() {
            const emp = $('#modal-employee').val();
            const date = $('input[name="shift_date"]').val();
            const shiftSelect = $('#modal-shift');
            const sourceContainer = $('#shift-source-container');
            
            if (!emp || !date) return;
            
            // Show scanning state without wiping options
            sourceContainer.html(`
                <div class="shift-source-info">
                    <div class="fa fa-spinner fa-spin"></div>
                    <span>Resolving Shift Hierarchy...</span>
                </div>
            `).fadeIn(200);
            
            shiftSelect.prop('disabled', true);
            $('#modal-shift-group').css({'border': '2px dashed #ddd', 'background': '#fafafa'});
            
            $.get(`/admin/employee/${emp}/shift-on-date`, { shift_date: date }, function(res) {
                const shift = res.shift;
                const source = res.source;

                if (shift && shift.id) {
                    // Update value and UI
                    shiftSelect.val(shift.id);
                    
                    // Auto-fill times if not edit mode or if inputs are empty
                    @if(!$isEdit)
                        const start = shiftSelect.find(':selected').data('start');
                        const end = shiftSelect.find(':selected').data('end');
                        if (start) $('input[name="check_in"]').val(start);
                        if (end) $('input[name="check_out"]').val(end);
                    @endif
                    
                    // Logic to enable/disable based on source
                    const isFallback = (source === 'Initial Assignment' || source === 'System Default');
                    
                    if (isFallback) {
                        shiftSelect.prop('disabled', false);
                        $('#modal-shift-group').css({
                            'border': '2px solid #6366f1',
                            'background': '#fff',
                            'box-shadow': '0 0 0 4px rgba(99, 102, 241, 0.1)'
                        });
                    } else {
                        shiftSelect.prop('disabled', true);
                        $('#modal-shift-group').css({
                            'border': '2px dashed #ddd',
                            'background': '#fafafa',
                            'box-shadow': 'none'
                        });
                    }

                    // Sync hidden data (always, whether enabled or not, for consistency)
                    if ($('#hidden-shift-id').length === 0) {
                        $('#hrmManualAttendanceForm').append(`<input type="hidden" name="shift_id" id="hidden-shift-id" value="${shift.id}">`);
                    } else {
                        $('#hidden-shift-id').val(shift.id);
                    }

                    // Show premium source badge
                    sourceContainer.html(`
                        <div class="shift-source-info animate__animated animate__zoomIn">
                            <div class="pulse-ai"></div>
                            <span>Auto-Resolved: ${source} ${isFallback ? '(Editable)' : ''}</span>
                        </div>
                    `).fadeIn(300);
                } else {
                    shiftSelect.val('').prop('disabled', true);
                    sourceContainer.html(`
                        <div class="shift-source-info" style="background:#fee2e2; color:#ef4444; border-color:#fecaca;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>No Shift Identified</span>
                        </div>
                    `).fadeIn(300);
                    $('#hidden-shift-id').val('');
                }
            }).fail(function() {
                toastr.error('Resolution Hub Failure');
                sourceContainer.html('<span>Resolution Failed</span>');
            });
        }

        $('#modal-employee').on('change', runIntelligenceShiftResolution);
        $('input[name="shift_date"]').on('change', runIntelligenceShiftResolution);

        // Shift Change Event for enabled mode
        $('#modal-shift').on('change', function() {
            const selected = $(this).find(':selected');
            const start = selected.data('start');
            const end = selected.data('end');
            
            // Auto-fill times on manual change
            if (start) $('input[name="check_in"]').val(start);
            if (end) $('input[name="check_out"]').val(end);

            if ($(this).is(':enabled')) {
                const val = $(this).val();
                if ($('#hidden-shift-id').length > 0) {
                    $('#hidden-shift-id').val(val);
                } else {
                    $('#hrmManualAttendanceForm').append(`<input type="hidden" name="shift_id" id="hidden-shift-id" value="${val}">`);
                }
            }
        });

        // Edit Mode Initialization
        @if($isEdit)
            if ($('#hidden-shift-id').length === 0) {
                $('#hrmManualAttendanceForm').append(`<input type="hidden" name="shift_id" id="hidden-shift-id" value="{{ $attendance->shift_id }}">`);
            }
            
            $('#shift-source-container').html(`
                <div class="shift-source-info">
                    <i class="bi bi-shield-lock-fill text-primary"></i>
                    <span>Locked Original Record (Editable)</span>
                </div>
            `);
        @endif

        // Form Transaction Handler
        $('#hrmManualAttendanceForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const btn = $('#btnHrmSaveAttendance');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing Architecture...');

            const url = "{{ $isEdit ? route('admin.attendance.manual.update', $attendance->id) : route('admin.attendance.manual.store') }}";

            $.post(url, formData)
                .done(function(res) {
                    if (res.success) {
                        toastr.success(res.message);
                        $('#ajaxModal').modal('hide');
                        if (typeof table !== 'undefined') table.ajax.reload(null, false);
                    } else {
                        toastr.error(res.message);
                        btn.prop('disabled', false).html(originalHtml);
                    }
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON ? xhr.responseJSON.message : 'System Failure';
                    toastr.error(msg);
                    btn.prop('disabled', false).html(originalHtml);
                });
        });
    });
</script>
