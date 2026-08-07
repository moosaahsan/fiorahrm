@php
    $toggleUrl = $url ?? route('admin.shifts.toggle-status', $shift->id);
@endphp

<div class="d-flex justify-content-center">
    <label class="saas-switch-box">
        <input type="checkbox" class="status-toggle" data-id="{{ $shift->id }}" {{ $shift->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
        <span class="saas-slider"></span>
    </label>
</div>