@php
    $prefix = $prefix ?? 'header'; // Default to 'header' if not provided
    $employee_breaks = $employee_breaks ?? [
        'allowed_break_minutes' => 0,
        'total_spent_minutes' => 0,
        'remaining_minutes' => 0,
        'exceeded_minutes' => 0
    ];
@endphp
<li class="list-inline-item d-none d-md-inline-block">
    <div class="d-flex gap-3 align-items-center">
        <div class="break-summary-card">
            <span class="label">Total</span>
            <span class="value" id="{{ $prefix }}-total-break">{{ $employee_breaks['allowed_break_minutes'] }}m</span>
        </div>
        <div class="break-summary-card">
            <span class="label">Spent</span>
            <span class="value text-danger" id="{{ $prefix }}-total-spent">{{ $employee_breaks['total_spent_minutes'] }}m</span>
        </div>
        <div class="break-summary-card">
            <span class="label">Remaining</span>
            <span class="value text-success" id="{{ $prefix }}-remaining">{{ $employee_breaks['remaining_minutes'] }}m</span>
        </div>
        <div class="break-summary-card">
            <span class="label">Exceeded</span>
            <span class="value text-warning" id="{{ $prefix }}-exceeded">{{ $employee_breaks['exceeded_minutes'] }}m</span>
        </div>
        <div class="break-summary-card" id="{{ $prefix }}-break-timer-card" style="display: {{ $activeBreak ? 'block' : 'none' }};">
            <span class="label">On Break</span>
            <span class="value text-primary" id="{{ $prefix }}-break-duration">{{ $initialBreakDuration }}m</span>
        </div>
    </div>
</li>