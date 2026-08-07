@php
    use Carbon\Carbon;

    // Standardize duration formatting
    function modal_format_duration($minutes): string
    {
        if ($minutes <= 0) return '0m';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return ($h ? "{$h}h " : "") . "{$m}m";
    }

    $now = Carbon::now('Asia/Karachi');
    $generalDoneMin = 0;
    $generalOpenMin = 0;
    $officialDoneMin = 0;

    foreach ($attendance->breaks as $b) {
        if ($b->end_time) {
            $mins = $b->spent_minutes ?? (int) ceil(Carbon::parse($b->created_at)->diffInMinutes(Carbon::parse($b->end_time)));
        } else {
            $mins = (int) ceil(Carbon::parse($b->created_at)->diffInMinutes($now));
        }

        if ($b->type === 'General') {
            $b->end_time ? $generalDoneMin += $mins : $generalOpenMin += $mins;
        } elseif ($b->end_time) {
            $officialDoneMin += $mins;
        }
    }

    $genDoneFormatted = modal_format_duration($generalDoneMin);
    $offDoneFormatted = modal_format_duration($officialDoneMin);
    $genOpenFormatted = modal_format_duration($generalOpenMin);
@endphp

<div class="modal-details-body pb-3">
    <!-- Header Section -->
    <div class="px-4 py-3 d-flex justify-content-between align-items-center border-bottom bg-light bg-opacity-50">
        <div>
            <h5 class="fw-bold mb-0">{{ $attendance->employee->name }}</h5>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="text-muted small"><i class="bi bi-person-badge me-1"></i> AST-{{ $attendance->emp_id }}</span>
                <span class="text-muted small">•</span>
                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> {{ $attendance->shift_date->format('d M, Y') }}</span>
            </div>
        </div>
        <div>
            @php
                $statusColor = match($attendance->status) {
                    'Present' => 'bg-success text-white',
                    'Absent' => 'bg-danger bg-opacity-10 text-danger',
                    default => 'bg-primary bg-opacity-10 text-primary'
                };
            @endphp
            <span class="status-pill {{ $statusColor }}">{{ $attendance->status ?? 'Present' }}</span>
        </div>
    </div>

    <div class="p-4">
        <!-- Key Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-blue-soft text-primary"><i class="bi bi-clock"></i></div>
                    <span class="stat-label">Shift Hours</span>
                    <p class="stat-value">{{ $attendance->check_in->format('h:i A') }} - {{ $attendance->check_out ? $attendance->check_out->format('h:i A') : 'Active' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-indigo-soft text-indigo"><i class="bi bi-hourglass-split"></i></div>
                    <span class="stat-label">Net Working Time</span>
                    <p class="stat-value text-primary">{{ calculateWorkedHours($attendance) }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-orange-soft text-orange"><i class="bi bi-coffee"></i></div>
                    <span class="stat-label">Total Breaks</span>
                    <p class="stat-value">{{ $genDoneFormatted }} 
                        @if($generalOpenMin > 0)
                            <span class="text-warning fs-6 fw-normal ms-1" title="Ongoing">(+{{ $genOpenFormatted }})</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <h6 class="section-title mb-4 bg-light p-2 rounded ps-3"><i class="bi bi-lightning-charge me-2 text-warning"></i>Activity Logs</h6>
        
        @if($attendance->breaks->isEmpty())
            <div class="text-center py-5 bg-light rounded-4 border-2 border-dashed">
                <div class="mb-3 text-muted opacity-25" style="font-size: 3rem;"><i class="bi bi-calendar-x"></i></div>
                <h6 class="fw-bold text-secondary">No break activities found</h6>
                <p class="text-muted small mb-0">This employee stayed on duty for the entire session.</p>
            </div>
        @else
            <div class="timeline-wrapper ms-2">
                @foreach($attendance->breaks->sortByDesc('start_time') as $b)
                    @php
                        $ended = (bool) $b->end_time;
                        $duration = $ended 
                            ? ($b->spent_minutes ?? (int) ceil(Carbon::parse($b->start_time)->diffInMinutes(Carbon::parse($b->end_time)))) 
                            : (int) ceil(Carbon::parse($b->start_time)->diffInMinutes($now));
                        
                        $isOfficial = $b->type !== 'General';
                        // Colors: Official -> Green, General -> Red
                        $borderColor = $isOfficial ? '#10b981' : '#ef4444'; // Emerald-500 vs Red-500
                        $bgColor = $isOfficial ? 'rgba(16, 185, 129, 0.05)' : 'rgba(239, 68, 68, 0.05)';
                    @endphp
                    <div class="timeline-point mb-3 {{ !$ended ? 'ongoing' : '' }}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content p-3 shadow-sm border-0" style="background: {{ $bgColor }}; border-left: 4px solid {{ $borderColor }} !important;">
                            <div class="d-flex flex-column flex-sm-row justify-content-between">
                                <div class="d-flex align-items-start" style="min-width: 0;">
                                    <!-- Time Column -->
                                    <div class="me-4 text-center d-flex flex-column justify-content-center" style="min-width: 85px;">
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ Carbon::parse($b->start_time)->format('h:i A') }}</div>
                                        <div class="text-muted small my-1">to</div>
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $ended ? Carbon::parse($b->end_time)->format('h:i A') : '...' }}</div>
                                    </div>

                                    <!-- Details Column -->
                                    <div class="d-flex flex-column" style="min-width: 0;">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="fw-bold fs-6 me-2 {{ $isOfficial ? 'text-success' : 'text-danger' }}">
                                                {{ $b->type }} Break
                                            </span>
                                            
                                            @if(!$ended)
                                                <span class="badge bg-warning text-dark small py-1 px-2 fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">ONGOING</span>
                                            @endif

                                            <!-- Status Icon -->
                                            @if($isOfficial)
                                                <div class="ms-2">
                                                    @if($b->status == 'Approved')
                                                         <span class="badge bg-success text-white py-1 px-2 rounded-pill"><i class="bi bi-check-lg me-1"></i>Approved</span>
                                                    @elseif($b->status == 'Rejected')
                                                         <span class="badge bg-danger text-white py-1 px-2 rounded-pill"><i class="bi bi-x-lg me-1"></i>Rejected</span>
                                                    @elseif(auth()->check() && (auth()->user()->is_admin || auth()->user()->hasRole('admin')))
                                                         <!-- Admin Controls will appear on the right, status is Pending -->
                                                    @else
                                                         <span class="badge bg-secondary text-white py-1 px-2 rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        @if($b->reason)
                                            <div class="p-2 rounded border border-secondary border-opacity-25 bg-white mb-1" style="max-width: 100%;">
                                                <div class="d-flex text-dark">
                                                    <i class="bi bi-quote me-2 text-muted fs-5" style="margin-top: -5px;"></i>
                                                    <span class="fst-italic fw-medium">{{ $b->reason }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Right Side Controls -->
                                <div class="d-flex flex-column align-items-end justify-content-start mt-2 mt-sm-0 ps-3">
                                    <div class="fw-bold fs-5 mb-2 {{ $isOfficial ? 'text-success' : 'text-danger' }}">{{ modal_format_duration($duration) }}</div>
                                    
                                    <div class="mt-auto d-flex flex-column align-items-end">
                                        @if($b->type !== 'General')
                                            @if($b->status == 'Approved')
                                                <span class="font-bold text-brand-600 mb-2"><i class="bi bi-person-check me-1"></i>{{ $b->approvedBy->name ?? 'Admin' }}</span>
                                            @elseif($b->status == 'Rejected')
                                                <span class="text-danger small fw-bold mb-2"><i class="bi bi-x-lg me-1"></i>Rejected by {{ $b->approvedBy->name ?? 'Admin' }}</span>
                                            @elseif($b->status == 'Pending' && (!auth()->user()->is_admin && !auth()->user()->hasRole('admin') && !auth()->user()->can('approve-break')))
                                                <span class="badge bg-secondary opacity-50 small mb-2">Pending Review</span>
                                            @endif
                                        @endif

                                        @if(auth()->user()->is_admin || auth()->user()->hasRole('admin') || auth()->user()->can('approve-break'))
                                            <div class="d-flex gap-2 justify-content-end">
                                                <button class="btn btn-sm btn-outline-success approve py-0 px-2 rounded" data-id="{{ $b->id }}" title="Accept (Mark Official)">
                                                    <i class="bi bi-check-lg fw-bold"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger reject py-0 px-2 rounded" data-id="{{ $b->id }}" title="Reject (Make General)">
                                                    <i class="bi bi-x-lg fw-bold"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Footer Quota Summary -->
        <div class="quota-banner d-flex justify-content-between align-items-center mt-4">
            <span class="fw-bold text-secondary small"><i class="bi bi-info-circle-fill me-2"></i>Daily Break Summary</span>
            <div class="d-flex gap-3">
                <div class="small"><span class="text-muted">General:</span> <span class="fw-bold text-dark">{{ $genDoneFormatted }}</span></div>
                <div class="small"><span class="text-muted">Official:</span> <span class="fw-bold text-dark">{{ $offDoneFormatted }}</span></div>
            </div>
        </div>
    </div>
</div>
