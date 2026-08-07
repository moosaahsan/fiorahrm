@php 
    $data = $data ?? request('data');
    if (!$data) {
        $id = request('id');
        $data = $id ? \App\Models\Shift::find($id) : null;
    }
    // Safely parse times
    $startTime = data_get($data, 'start_time') ? \Carbon\Carbon::parse(data_get($data, 'start_time'))->format('H:i') : '09:00';
    $endTime = data_get($data, 'end_time') ? \Carbon\Carbon::parse(data_get($data, 'end_time'))->format('H:i') : '18:00';
@endphp

@if(!$data)
    <div class="modal-content premium-modal-card">
        <div class="modal-body p-5 text-center">
            <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
            <h5 class="text-slate-700">Unable to load shift data</h5>
            <button type="button" class="btn btn-secondary mt-3" data-dismiss="modal">Close</button>
        </div>
    </div>
@else
    <div class="modal-content premium-modal-card">
        <!-- Premium Header -->
        <div class="modal-header border-0 p-4 position-relative overflow-hidden" style="background: #4338ca !important; border-radius: 28px 28px 0 0 !important;">
            <div class="d-flex align-items-center position-relative" style="z-index: 2;">
                <div class="premium-icon-box-white me-3">
                    <i class="fas fa-edit text-white"></i>
                </div>
                <div>
                    <h5 class="modal-title fw-800 text-white mb-0">Modify Shift</h5>
                    <p class="text-white opacity-75 x-small mb-0 fw-500">Update operational timings and visibility</p>
                </div>
            </div>
            <button type="button" class="btn-close-custom ms-auto" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times text-white"></i>
            </button>
        </div>

        <!-- Premium Body -->
        <div class="modal-body p-4 pt-2">
            <form id="update_shift_form">
                @csrf
                <input type="hidden" id="shift_id" name="shift_id" value="{{ data_get($data, 'id') }}">

                <div class="premium-section-group mb-4">
                    <label class="premium-label">Shift Label</label>
                    <div class="premium-input-wrapper">
                        <i class="fas fa-signature input-icon"></i>
                        <input type="text" class="premium-input" name="shift_name" value="{{ data_get($data, 'shift_name') }}" required>
                        <div class="input-focus-line"></div>
                    </div>
                </div>

                @php $allBranches = \App\Models\Branch::where('is_active', true)->get(); @endphp
                <div class="premium-section-group mb-4">
                    <label class="premium-label">Office Branch</label>
                    <div class="premium-input-wrapper">
                        <i class="fas fa-building input-icon"></i>
                        <select class="premium-input" name="branch_id" required style="padding-left: 3.5rem !important; appearance: auto;">
                            @foreach ($allBranches as $branch)
                                <option value="{{ $branch->id }}" {{ data_get($data, 'branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <div class="input-focus-line"></div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <label class="premium-label">Punch In</label>
                        <div class="premium-input-wrapper">
                            <i class="fas fa-clock input-icon text-emerald"></i>
                            <input type="time" class="premium-input" name="start_time" value="{{ $startTime }}" required>
                            <div class="input-focus-line bg-emerald"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="premium-label">Punch Out</label>
                        <div class="premium-input-wrapper">
                            <i class="fas fa-clock input-icon text-rose"></i>
                            <input type="time" class="premium-input" name="end_time" value="{{ $endTime }}" required>
                            <div class="input-focus-line bg-rose"></div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Toggles Side-by-Side -->
                <div class="premium-parameters-card p-3 rounded-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-sliders-h text-indigo-400 me-2 x-small"></i>
                        <span class="x-small fw-800 text-indigo-500 text-uppercase letter-spacing-1">Configuration</span>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="premium-toggle-item p-2 px-3 rounded-3 h-100">
                                <div class="form-check form-switch ps-0 d-flex align-items-center flex-nowrap">
                                    <label class="fw-700 text-slate-800 x-small mb-0 flex-grow-1 text-start text-nowrap" for="up_crosses_midnight">Midnight</label>
                                    <input class="form-check-input premium-switch ms-2 flex-shrink-0" type="checkbox" id="up_crosses_midnight" name="crosses_midnight" 
                                           value="1" {{ data_get($data, 'crosses_midnight') ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="premium-toggle-item p-2 px-3 rounded-3 h-100">
                                <div class="form-check form-switch ps-0 d-flex align-items-center flex-nowrap">
                                    <label class="fw-700 text-slate-800 x-small mb-0 flex-grow-1 text-start text-nowrap" for="up_is_active">Active</label>
                                    <input class="form-check-input premium-switch ms-2 flex-shrink-0" type="checkbox" id="up_is_active" name="is_active" 
                                           value="1" {{ data_get($data, 'is_active') ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Footer -->
                <div class="modal-footer border-0 p-0 pt-4 gap-3">
                    <button type="button" class="btn-cancel-premium ms-auto" data-dismiss="modal">Discard</button>
                    <button type="submit" class="btn-primary-premium">
                        <span class="btn-label">Update Changes</span>
                        <i class="fas fa-sync-alt ms-2 icon-move"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    $(document).ready(function() {
        $('#update_shift_form').on('submit', function (e) {
            e.preventDefault();
            let $btn = $(this).find('button[type="submit"]');
            let id = $('#shift_id').val();
            $btn.prop('disabled', true).addClass('opacity-50');

            $.ajax({
                url: "/admin/shift/" + id,
                method: "POST",
                data: $(this).serialize() + '&_method=PUT',
                success: function (resp) {
                    if (resp.success) {
                        toastr.success('Shift record updated successfully');
                        $('#shift_update_ajax_modal').modal('hide');
                        if ($.fn.DataTable.isDataTable('#shift-table')) {
                            $('#shift-table').DataTable().ajax.reload(null, false);
                        }
                    }
                },
                error: function (xhr) {
                    toastr.error('Error', 'Update failed. Check inputs.');
                    $btn.prop('disabled', false).removeClass('opacity-50');
                }
            });
        });
    });
</script>