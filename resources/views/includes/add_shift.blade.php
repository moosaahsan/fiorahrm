<div class="modal fade" id="addnew" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> 
        <div class="modal-content premium-modal-card">
            
            <!-- Premium Header -->
            <div class="modal-header border-0 p-4 position-relative overflow-hidden" style="background: #4338ca !important;">
                <div class="d-flex align-items-center position-relative" style="z-index: 2;">
                    <div class="premium-icon-box me-3" style="background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.2) !important; box-shadow: none !important;">
                        <i class="fas fa-layer-group text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-800 text-white mb-0">New Work Shift</h5>
                        <p class="text-white opacity-75 x-small mb-0 fw-500">Establish a new operational schedule</p>
                    </div>
                </div>
                <button type="button" class="btn-close-custom ms-auto" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times text-white"></i>
                </button>
            </div>

            <form id="add-shift" action="{{ route('admin.shifts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 pt-2">
                    
                    <div class="error-container-wrapper mb-3" style="display:none;">
                        <div class="alert alert-danger-soft border-0 shadow-none rounded-3 px-3 py-2">
                            <ul class="error-container mb-0 x-small fw-600"></ul>
                        </div>
                    </div>

                    <div class="premium-section-group mb-4">
                        <label class="premium-label">Shift Core Identity</label>
                        <div class="premium-input-wrapper">
                            <i class="fas fa-signature input-icon"></i>
                            <input type="text" class="premium-input" name="shift_name" placeholder="e.g. Standard Morning" required>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="premium-section-group mb-4">
                        <label class="premium-label">Office Branch</label>
                        <div class="premium-input-wrapper">
                            <i class="fas fa-building input-icon"></i>
                            <select class="premium-input" name="branch_id" required style="padding-left: 3.5rem !important; appearance: auto;">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="input-focus-line"></div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-6">
                            <label class="premium-label">Shift Commences</label>
                            <div class="premium-input-wrapper">
                                <i class="fas fa-sign-in-alt input-icon text-emerald"></i>
                                <input type="time" class="premium-input" name="start_time" required>
                                <div class="input-focus-line bg-emerald"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="premium-label">Shift Concludes</label>
                            <div class="premium-input-wrapper">
                                <i class="fas fa-sign-out-alt input-icon text-rose"></i>
                                <input type="time" class="premium-input" name="end_time" required>
                                <div class="input-focus-line bg-rose"></div>
                            </div>
                        </div>
                    </div>

                    <div class="premium-parameters-card p-3 rounded-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-sliders-h text-slate-400 me-2 x-small"></i>
                            <span class="x-small fw-800 text-slate-500 text-uppercase letter-spacing-1">Configuration</span>
                        </div>
                        
                    <div class="premium-parameters-card p-3 rounded-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-sliders-h text-indigo-400 me-2 x-small"></i>
                            <span class="x-small fw-800 text-indigo-500 text-uppercase letter-spacing-1">Configuration</span>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="premium-toggle-item p-2 px-3 rounded-3 h-100">
                                    <div class="form-check form-switch ps-0 d-flex align-items-center flex-nowrap">
                                        <label class="fw-700 text-slate-800 x-small mb-0 flex-grow-1 text-start text-nowrap" for="midCheck">Midnight</label>
                                        <input class="form-check-input premium-switch ms-2 flex-shrink-0" type="checkbox" name="crosses_midnight" id="midCheck" value="1">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="premium-toggle-item p-2 px-3 rounded-3 h-100">
                                    <div class="form-check form-switch ps-0 d-flex align-items-center flex-nowrap">
                                        <label class="fw-700 text-slate-800 x-small mb-0 flex-grow-1 text-start text-nowrap" for="activeCheck">Active</label>
                                        <input class="form-check-input premium-switch ms-2 flex-shrink-0" type="checkbox" name="is_active" id="activeCheck" value="1" checked>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0 gap-3">
                    <button type="button" class="btn-cancel-premium ms-auto" data-dismiss="modal">Discard</button>
                    <button type="submit" class="btn-primary-premium">
                        <span class="btn-label">Create Shift</span>
                        <i class="fas fa-arrow-right ms-2 icon-move"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $('#add-shift').submit(function (event) {
        event.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $errorWrapper = $('.error-container-wrapper');
        
        $('.error-container').empty();
        $errorWrapper.hide();
        
        $submitBtn.prop('disabled', true).addClass('opacity-50');

        $.ajax({
            url: $form.attr('action'),
            method: "POST",
            data: $form.serialize(),
            success: function (data) {
                $('#addnew').modal('hide');
                $form[0].reset();
                toastr.success('Success', 'Shift established successfully');
                $('#shift-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                $errorWrapper.fadeIn();
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    for (const field in errors) {
                        $('.error-container').append('<li class="d-flex align-items-center mb-1"><i class="fas fa-exclamation-circle me-2"></i>' + errors[field][0] + '</li>');
                    }
                } else {
                    $('.error-container').append('<li>Critical error. Please contact infrastructure.</li>');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).removeClass('opacity-50');
            }
        });
    });
</script>