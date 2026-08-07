@extends('employee.layouts.app')

@section('title', 'Break Intelligence Centre')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="page-title mb-1" style="font-size: 2rem;">Operational Continuity Centre</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#"
                                class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">Availability</a>
                        </li>
                        <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1"
                            aria-current="page">Break Intelligence</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

@endsection

@section('content')
<div class="employee-portal-page">
    <div class="container-fluid">
        <!-- SaaS Stat Row -->
        <div class="row mb-5">
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Review</div>
                        <div class="stat-value" id="stat-pending">{{ $pendingCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Active Breaks</div>
                        <div class="stat-value" id="stat-active">{{ $activeCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Daily Time Leakage</div>
                        <div class="stat-value" id="stat-leakage">{{ $totalMinutes }}m</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SaaS Command Center -->
        <div class="command-center">
            <form action="{{ route('employee.break.index') }}" method="GET" id="filter-form">
                <div class="row align-items-end g-3">
                    <div class="col-lg-3">
                        <label class="small fw-bold text-muted mb-2">Shift Date Audit</label>
                        <input type="date" name="date" class="saas-filter-input" value="{{ $selectedDate }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-lg-3">
                        <label class="small fw-bold text-muted mb-2">Decision Matrix Filter</label>
                        <select name="status" class="saas-filter-input" onchange="this.form.submit()">
                            <option value="Pending" {{ $selectedStatus == 'Pending' ? 'selected' : '' }}>Pending Official Breaks</option>
                            <option value="Live" {{ $selectedStatus == 'Live' ? 'selected' : '' }}>Currently on Break</option>
                            <option value="Approved" {{ $selectedStatus == 'Approved' ? 'selected' : '' }}>Approved Official</option>
                            <option value="Rejected" {{ $selectedStatus == 'Rejected' ? 'selected' : '' }}>Rejected (General)</option>
                            <option value="All" {{ $selectedStatus == 'All' ? 'selected' : '' }}>Full Audit Log</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="small fw-bold text-muted mb-2">Personnel Availability Search</label>
                        <select id="filter-employee" class="saas-filter-input"
                            style="appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right .7rem top 50%; background-size: .65rem auto;">
                            <option value="">All Personnel</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 text-end">
                        <p class="small text-muted mb-0 fw-bold"><i class="bi bi-info-circle me-1"></i> Audit Logs for {{ \Carbon\Carbon::parse($selectedDate)->format('d M, Y') }}</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Premium Table Matrix -->
        <div class="premium-table-card">
            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-family: 'Outfit'; font-weight: 800;">Break
                        Intelligence Matrix</h5>
                    <p class="text-muted small mb-0">Live audit of employee availability and break compliance</p>
                </div>
                <div>

                </div>
            </div>
            <div class="card-body p-3">
                <div class="w-100">
                    <table class="table table-hover dt-responsive nowrap w-100" id="official-requests-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Personnel Identity</th>
                                <th>Allocated Shift</th>
                                <th>Date</th>
                                <th>Window</th>
                                <th>Net Duration</th>
                                <th>Reasoning</th>
                                <th>Decision By</th>
                                <th>Live Status</th>
                                <th class="text-end">Command</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($officialRequests as $request)
                                <tr data-employee-id="{{ $request['employee_id'] }}">
                                    <td>
                                        <div class="emp-identity">
                                            @php $initials = collect(explode(' ', trim($request['employee_name'] ?? '')))->filter()->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join(''); @endphp
                                            @if($request['profile_pic_url'])
                                                <img src="{{ $request['profile_pic_url'] }}" class="emp-avatar">
                                            @else
                                                <div class="emp-avatar">{{ strtoupper($initials) }}</div>
                                            @endif
                                            <div class="emp-info">
                                                <span class="name">{{ $request['employee_name'] }}</span>
                                                <span class="small text-muted fw-bold">EMP-{{ $request['employee_id'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small fw-extrabold text-indigo">#{{ $request['shift_name'] }}</td>
                                    <td class="fw-bold">{{ \Carbon\Carbon::parse($request['shift_date'])->format('d M, Y') }}
                                    </td>
                                    <td class="small fw-bold text-slate-600">
                                        {{ $request['start_time'] ? \Carbon\Carbon::parse($request['start_time'])->format('h:i A') : 'N/A' }}
                                        <i class="bi bi-arrow-right mx-1 opacity-50"></i>
                                        {{ $request['end_time'] ? \Carbon\Carbon::parse($request['end_time'])->format('h:i A') : 'N/A' }}
                                    </td>
                                    <td><span class="fw-extrabold text-dark">{{ $request['duration'] ?? 0 }}m</span></td>
                                    <td>
                                        <div class="text-muted small"
                                            style="max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer;"
                                            data-toggle="tooltip" title="{{ $request['reason'] }}" data-reason="{{ $request['reason'] }}">
                                            <i class="bi bi-chat-left-text me-1 opacity-75"></i> {{ $request['reason'] }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($request['approved_by_name'])
                                            <span class="fw-bold text-indigo"><i class="bi bi-person-check me-1"></i> {{ $request['approved_by_name'] }}</span>
                                        @else
                                            <span class="text-muted small italic">Waiting...</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($request['status'] === 'On Break' || $request['status'] === 'Ongoing' || $request['status'] === 'On break')
                                            <span class="saas-status-badge on-break animate-pulse">
                                                <span class="pulse-dot me-2"
                                                    style="width:8px; height:8px; background:#b45309; border-radius:50%;"></span>
                                                Live Break
                                            </span>
                                        @elseif($request['status'] === 'Completed')
                                            <span class="saas-status-badge completed">
                                                <i class="bi bi-check-circle-fill me-1"></i> Finished
                                            </span>
                                        @elseif($request['status'] === 'Approved')
                                            <span class="saas-status-badge completed" style="background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.2); color: #6366f1;">
                                                <i class="bi bi-shield-check me-1"></i> Approved
                                            </span>
                                        @elseif($request['status'] === 'Rejected')
                                            <span class="saas-status-badge" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #ef4444;">
                                                <i class="bi bi-x-circle me-1"></i> Rejected
                                            </span>
                                        @else
                                            <span class="saas-status-badge pending">
                                                <i class="bi bi-hourglass-split me-1"></i> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            @if($request['status'] !== 'Approved' && $request['status'] !== 'Rejected')
                                                @if(isset($currentEmployeeId) && $request['employee_id'] == $currentEmployeeId)
                                                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                                        <i class="bi bi-person-fill me-1"></i>Self Request
                                                    </span>
                                                @else
                                                    @can('approve-break')
                                                        <button class="btn-saas-action approve-btn" data-id="{{ $request['id'] }}"
                                                            title="Accept (Mark Official)">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    @endcan
                                                    @can('reject-break')
                                                        <button class="btn-saas-action reject-btn" data-id="{{ $request['id'] }}"
                                                            title="Reject (Make General)">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                            @else
                                                <span class="badge bg-light text-muted border px-3 py-2 rounded-pill" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em;">Audit Locked</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="opacity-30 mb-2 mt-4"><i class="bi bi-shield-check"
                                                style="font-size: 3rem;"></i></div>
                                        <p class="fw-bold text-muted small uppercase ls-1">Excellent Compliance. No pending
                                            requests.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Reason Modal -->
    <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header border-bottom-0 p-4 pb-0">
                    <h5 class="modal-title fw-extrabold text-indigo" style="font-family:'Outfit';">Break Reason Architecture
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-2">
                    <p id="reason-text" class="mb-0 text-slate-600 fw-medium lh-lg"></p>
                </div>
                <div class="modal-footer border-top-0 p-4">
                    <button type="button" class="btn fw-bold px-4 rounded-pill" style="background:#f1f5f9; color:#475569;"
                        data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const table = $('#official-requests-table').DataTable({
                order: [[2, 'desc']],
                pageLength: 25,
                responsive: true,
                dom: '<"p-4 d-flex justify-content-between align-items-center"f>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Query break matrix...",
                }
            });

            $('.dataTables_filter input').addClass('saas-filter-input').css('width', '250px');

            // Personnel filtering
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                const selected = $('#filter-employee').val();
                if (!selected) return true;
                return $(settings.aoData[dataIndex].nTr).data('employee-id') == selected;
            });

            $('#filter-employee').on('change', () => table.draw());


            // Init tooltips
            $('body').tooltip({ selector: '[data-toggle="tooltip"]' });
        });
    </script>
@endpush