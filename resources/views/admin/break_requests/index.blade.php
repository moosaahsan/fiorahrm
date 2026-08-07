@extends('admin.layouts.app')

@section('title', 'Break Intelligence Centre')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="tw-page-title mb-1 text-2xl">Operational Continuity Centre</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#" class="small text-uppercase font-bold text-slate-500 no-underline">Availability</a></li>
                        <li class="breadcrumb-item active small font-bold uppercase text-brand-600" aria-current="page">Break Intelligence</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="tw-stat-card">
                <div class="icon-container">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-value" id="stat-pending">{{ $pendingCount }}</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container !bg-emerald-50 !text-emerald-500">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <div class="stat-label">Active Breaks</div>
                    <div class="stat-value" id="stat-active">{{ $activeCount }}</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container !bg-amber-50 !text-amber-500">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="stat-label">Daily Time Leakage</div>
                    <div class="stat-value" id="stat-leakage">{{ $totalMinutes }}m</div>
                </div>
            </div>
        </div>

        <div class="tw-command-hub">
            <form action="{{ route('admin.breaks.index') }}" method="GET" id="filter-form">
                <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                    <div class="md:col-span-3">
                        <label class="tw-filter-label">Shift Date Audit</label>
                        <input type="date" name="date" class="tw-form-input" value="{{ $selectedDate }}" onchange="this.form.submit()">
                    </div>
                    <div class="md:col-span-3">
                        <label class="tw-filter-label">Decision Matrix Filter</label>
                        <select name="status" class="tw-form-input" onchange="this.form.submit()">
                            <option value="Pending" {{ $selectedStatus == 'Pending' ? 'selected' : '' }}>Pending Official Breaks</option>
                            <option value="Live" {{ $selectedStatus == 'Live' ? 'selected' : '' }}>Currently on Break</option>
                            <option value="Approved" {{ $selectedStatus == 'Approved' ? 'selected' : '' }}>Approved Official</option>
                            <option value="Rejected" {{ $selectedStatus == 'Rejected' ? 'selected' : '' }}>Rejected (Official)</option>
                            <option value="All" {{ $selectedStatus == 'All' ? 'selected' : '' }}>Full Audit Log</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="tw-filter-label">Personnel Availability Search</label>
                        <select id="filter-employee" class="tw-form-input">
                            <option value="">All Personnel</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <p class="mb-0 text-sm font-bold text-slate-500">
                            <i class="bi bi-info-circle me-1"></i> Audit Logs for {{ \Carbon\Carbon::parse($selectedDate)->format('d M, Y') }}
                        </p>
                    </div>
                </div>
            </form>
        </div>

        <div class="tw-directory-card overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div>
                    <h5 class="mb-0 font-display text-lg font-extrabold text-slate-900">Break Intelligence Matrix</h5>
                    <p class="mb-0 text-sm text-slate-500">Live audit of employee availability and break compliance</p>
                </div>
                <button class="tw-btn-primary rounded-full px-5" type="button" data-toggle="modal" data-target="#manualBreakModal">
                    <i class="bi bi-clock-history me-2"></i>Log Manual Break
                </button>
            </div>
            <div class="p-1">
                <div class="table-responsive">
                    <table class="table table-hover tw-admin-table w-100" id="official-requests-table">
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
                                                <img src="{{ $request['profile_pic_url'] }}" class="emp-avatar" alt="">
                                            @else
                                                <div class="emp-avatar">{{ strtoupper($initials) }}</div>
                                            @endif
                                            <div class="emp-info">
                                                <span class="name">{{ $request['employee_name'] }}</span>
                                                <span class="id-badge">EMP-{{ $request['employee_id'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small font-extrabold text-brand-600">#{{ $request['shift_name'] }}</td>
                                    <td class="font-bold">{{ \Carbon\Carbon::parse($request['shift_date'])->format('d M, Y') }}</td>
                                    <td class="small font-bold text-slate-600">
                                        {{ $request['start_time'] ? \Carbon\Carbon::parse($request['start_time'])->format('h:i A') : 'N/A' }}
                                        <i class="bi bi-arrow-right mx-1 opacity-50"></i>
                                        {{ $request['end_time'] ? \Carbon\Carbon::parse($request['end_time'])->format('h:i A') : 'N/A' }}
                                    </td>
                                    <td><span class="font-extrabold text-slate-900">{{ $request['duration'] ?? 0 }}m</span></td>
                                    <td>
                                        <div class="max-w-[180px] cursor-pointer truncate text-sm text-slate-500"
                                            data-toggle="tooltip" title="{{ $request['reason'] }}" data-reason="{{ $request['reason'] }}">
                                            <i class="bi bi-chat-left-text me-1 opacity-75"></i> {{ $request['reason'] }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($request['approved_by_name'])
                                            <span class="font-bold text-brand-600"><i class="bi bi-person-check me-1"></i> {{ $request['approved_by_name'] }}</span>
                                        @else
                                            <span class="small italic text-slate-400">Waiting...</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($request['status'] === 'On Break' || $request['status'] === 'Ongoing' || $request['status'] === 'On break')
                                            <span class="saas-status-badge on-break animate-pulse">
                                                <span class="me-2 inline-block h-2 w-2 rounded-full bg-amber-700"></span>
                                                Live Break
                                            </span>
                                        @elseif($request['status'] === 'Completed')
                                            <span class="saas-status-badge completed">
                                                <i class="bi bi-check-circle-fill me-1"></i> Finished
                                            </span>
                                        @elseif($request['status'] === 'Approved')
                                            <span class="saas-status-badge border-brand-500/20 bg-brand-500/10 text-brand-600">
                                                <i class="bi bi-shield-check me-1"></i> Approved
                                            </span>
                                        @elseif($request['status'] === 'Rejected')
                                            <span class="saas-status-badge rejected">
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
                                                    <span class="badge rounded-pill border bg-slate-50 px-3 py-2 text-[0.65rem] font-bold uppercase tracking-wide text-slate-500">
                                                        <i class="bi bi-person-fill me-1"></i>Self Request
                                                    </span>
                                                @else
                                                    @can('approve-break')
                                                        <button class="btn-saas-action approve-btn" data-id="{{ $request['id'] }}" title="Accept (Mark Official)">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    @endcan
                                                    @can('reject-break')
                                                        <button class="btn-saas-action reject-btn" data-id="{{ $request['id'] }}" title="Reject (Make General)">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                            @else
                                                <span class="badge rounded-pill border bg-slate-50 px-3 py-2 text-[0.65rem] font-bold uppercase tracking-wide text-slate-500">Audit Locked</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-5 text-center">
                                        <div class="mb-2 mt-4 opacity-30"><i class="bi bi-shield-check text-5xl"></i></div>
                                        <p class="small font-bold uppercase tracking-wide text-slate-500">Excellent Compliance. No pending requests.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content tw-modal-shell">
                <div class="border-b border-slate-100 p-5 pb-0">
                    <h5 class="font-display text-lg font-extrabold text-brand-600">Break Reason Architecture</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="p-5 pt-2">
                    <p id="reason-text" class="mb-0 font-medium leading-relaxed text-slate-600"></p>
                </div>
                <div class="border-t border-slate-100 p-5">
                    <button type="button" class="tw-btn-secondary rounded-full px-5" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manualBreakModal" tabindex="-1" aria-labelledby="manualBreakModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content tw-modal-shell">
                <form id="manual-break-form" action="{{ route('admin.breaks.storeManual') }}" method="POST">
                    @csrf
                    <div class="border-b border-slate-100 p-5 pb-0">
                        <h5 class="font-display text-lg font-extrabold text-brand-600" id="manualBreakModalLabel">
                            <i class="bi bi-clock-history me-2"></i>Log Manual Break
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="p-5">
                        <div class="mb-4">
                            <label for="employee_id" class="tw-filter-label">Personnel / Employee</label>
                            <select name="employee_id" id="employee_id" class="tw-form-input" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} (EMP-{{ $emp->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="shift_date" class="tw-filter-label">Shift Date</label>
                            <input type="date" name="shift_date" id="shift_date" class="tw-form-input" value="{{ $selectedDate }}" required>
                        </div>
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="start_time" class="tw-filter-label">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="tw-form-input" required>
                            </div>
                            <div>
                                <label for="end_time" class="tw-filter-label">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="tw-form-input" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="break_type" class="tw-filter-label">Break Type</label>
                            <select name="type" id="break_type" class="tw-form-input" required>
                                <option value="Official">Official (Paid / Approved)</option>
                                <option value="General">General (Unpaid / Deducted)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="reason" class="tw-filter-label">Reasoning / Notes</label>
                            <textarea name="reason" id="reason" class="tw-form-input" rows="3" placeholder="Enter break reason..."></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 p-5 pt-0">
                        <button type="button" class="tw-btn-secondary rounded-full px-5" data-dismiss="modal">Close</button>
                        <button type="submit" class="tw-btn-primary rounded-full px-5">Save Break</button>
                    </div>
                </form>
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

            $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px');

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                const selected = $('#filter-employee').val();
                if (!selected) return true;
                return $(settings.aoData[dataIndex].nTr).data('employee-id') == selected;
            });

            $('#filter-employee').on('change', () => table.draw());

            $(document).on('click', '.approve-btn', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Approve Logic?',
                    text: 'This will mark the break as Official / Paid.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    confirmButtonText: 'Confirm Approval',
                    cancelButtonText: 'Dismiss',
                    customClass: { confirmButton: 'btn btn-success rounded-pill px-4', cancelButton: 'btn btn-secondary rounded-pill px-4' },
                    buttonsStyling: false
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            url: `/admin/breaks/${id}/approve`,
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            success: (res) => {
                                if (res.success) {
                                    toastr.success('Availability Updated');
                                    setTimeout(() => location.reload(), 800);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.reject-btn', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Reject Request?',
                    text: 'This will revert the break to General / Deducted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Reject Request',
                    cancelButtonText: 'Dismiss',
                    customClass: { confirmButton: 'btn btn-danger rounded-pill px-4', cancelButton: 'btn btn-secondary rounded-pill px-4' },
                    buttonsStyling: false
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            url: `/admin/breaks/${id}/reject`,
                            method: 'PATCH',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            success: (res) => {
                                if (res.success) {
                                    toastr.error('Request Denied');
                                    setTimeout(() => location.reload(), 800);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '[data-toggle="tooltip"]', function () {
                const reason = $(this).attr('data-reason') || $(this).attr('data-original-title') || $(this).attr('title') || 'N/A';
                $('#reason-text').text(reason);
                $('#reasonModal').modal('show');
            });

            $('#manual-break-form').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: (res) => {
                        submitBtn.prop('disabled', false).text('Save Break');
                        if (res.success) {
                            $('#manualBreakModal').modal('hide');
                            toastr.success(res.message || 'Break logged successfully');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(res.message || 'Failed to save break');
                        }
                    },
                    error: (xhr) => {
                        submitBtn.prop('disabled', false).text('Save Break');
                        const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                        toastr.error(errorMsg);
                    }
                });
            });

            $('body').tooltip({ selector: '[data-toggle="tooltip"]' });
        });
    </script>
@endpush
