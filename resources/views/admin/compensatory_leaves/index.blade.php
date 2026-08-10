@extends('admin.layouts.app')

@section('title', 'Compensatory Leave')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Compensatory Leave</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Admin</a></li>
        <li class="breadcrumb-item active text-slate-500">Compensatory Leave</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-brand-600">
                <i class="bi bi-calendar2-heart"></i>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">Compensatory Leave (CPL)</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Days earned by working on a public holiday. Approving a credit adds it to the employee's CPL balance.</p>
            </div>
        </div>
        @can('manage-compensatory-leaves')
            <button class="tw-btn-primary h-12 px-6" data-toggle="modal" data-target="#grant-cpl-modal">
                <i class="bi bi-plus-lg"></i> Grant CPL
            </button>
        @endcan
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Awaiting Approval</p>
            <p class="mb-0 text-3xl font-extrabold text-amber-600">{{ $summary['pending'] }}</p>
        </div>
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Approved Credits</p>
            <p class="mb-0 text-3xl font-extrabold text-emerald-600">{{ $summary['approved'] }}</p>
        </div>
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Days Credited</p>
            <p class="mb-0 text-3xl font-extrabold text-brand-600">{{ rtrim(rtrim(number_format($summary['days_approved'], 2), '0'), '.') }}</p>
        </div>
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Earned in {{ now()->year }}</p>
            <p class="mb-0 text-3xl font-extrabold text-slate-700">{{ $summary['this_year'] }}</p>
        </div>
    </div>

    <div class="tw-command-hub">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
            <div class="md:col-span-4">
                <label class="tw-filter-label"><i class="bi bi-person-badge mr-1"></i> Employee</label>
                <select id="filter-employee" class="select2 tw-form-input h-12 py-0">
                    <option value="">All Personnel</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="tw-filter-label"><i class="bi bi-flag mr-1"></i> Status</label>
                <select id="filter-status" class="tw-form-input h-12 py-0">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="tw-filter-label"><i class="bi bi-calendar-range mr-1"></i> Year</label>
                <select id="filter-year" class="tw-form-input h-12 py-0">
                    <option value="">All Years</option>
                    @foreach(range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button class="tw-btn-primary h-12 px-6" id="filter-button">
                    <i class="bi bi-funnel-fill"></i> Search
                </button>
                <button class="tw-btn-secondary h-12 px-5" id="reset-filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <div class="tw-directory-card overflow-hidden p-0">
        <table id="cpl-table" class="table table-hover mb-0 w-100 tw-admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Holiday Worked</th>
                    <th>Occasion</th>
                    <th>Days Earned</th>
                    <th>Status</th>
                    <th>Balance</th>
                    <th>Approved By</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@can('manage-compensatory-leaves')
<div class="modal fade" id="grant-cpl-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('admin.compensatory_leaves.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Grant Compensatory Leave</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="tw-filter-label">Employee</label>
                    <select name="employee_id" class="select2-modal tw-form-input h-12 py-0" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="tw-filter-label">Date Worked</label>
                    <input type="date" name="worked_date" class="tw-form-input h-12" required>
                </div>
                <div class="mb-4">
                    <label class="tw-filter-label">Days Earned</label>
                    <input type="number" name="days_earned" step="0.5" min="0.5" max="30"
                           value="{{ app_settings('cpl_days_per_holiday') }}" class="tw-form-input h-12">
                    <small class="text-slate-500">Default comes from Settings → cpl_days_per_holiday.</small>
                </div>
                <div>
                    <label class="tw-filter-label">Notes</label>
                    <textarea name="notes" rows="2" class="tw-form-input" placeholder="Why this credit is being granted"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-btn-secondary px-5 py-2.5" data-dismiss="modal">Cancel</button>
                <button type="submit" class="tw-btn-primary px-5 py-2.5">Grant Credit</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
    $(function () {
        $('#filter-employee').select2({ width: '100%' });
        $('.select2-modal').select2({ width: '100%', dropdownParent: $('#grant-cpl-modal') });

        const statusBadge = function (status) {
            const map = {
                'Pending':   ['bg-amber-100 text-amber-700', 'hourglass-split'],
                'Approved':  ['bg-emerald-100 text-emerald-700', 'check-circle'],
                'Rejected':  ['bg-rose-100 text-rose-700', 'x-circle'],
                'Cancelled': ['bg-slate-100 text-slate-600', 'slash-circle'],
            };
            const [classes, icon] = map[status] || map['Cancelled'];
            return `<span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold ${classes}"><i class="bi bi-${icon}"></i> ${status}</span>`;
        };

        const table = $('#cpl-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.compensatory_leaves.data") }}',
                data: function (d) {
                    d.employee_id = $('#filter-employee').val();
                    d.status = $('#filter-status').val();
                    d.year = $('#filter-year').val();
                }
            },
            columns: [
                { data: 'employee_name', name: 'employee.name' },
                { data: 'worked_on', name: 'worked_date' },
                { data: 'holiday', name: 'holiday_title' },
                { data: 'days', name: 'days_earned' },
                { data: 'status', name: 'status', render: statusBadge },
                { data: 'balance_state', name: 'is_credited' },
                { data: 'approved_by_name', name: 'approver.name' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right' },
            ],
            order: [[1, 'desc']],
            dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
            language: {
                search: "",
                searchPlaceholder: "Search compensatory leave...",
                emptyTable: `<div class="py-8 text-center"><i class="bi bi-calendar2-heart text-4xl text-slate-300"></i><p class="mb-0 mt-3 font-bold text-slate-500">No compensatory leave records yet.</p></div>`,
                processing: `<div class="flex items-center justify-center gap-2 text-brand-600"><i class="bi bi-arrow-repeat fa-spin"></i> Loading...</div>`,
                lengthMenu: "Show _MENU_ records"
            }
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

        $('#filter-button').on('click', () => table.draw());

        $('#reset-filters').on('click', function () {
            $('#filter-employee').val('').trigger('change');
            $('#filter-status').val('');
            $('#filter-year').val('{{ now()->year }}');
            table.draw();
        });

        const decide = function (id, url, payload) {
            $.ajax({
                url: url,
                method: 'POST',
                data: Object.assign({ _token: '{{ csrf_token() }}' }, payload || {}),
                success: function (res) {
                    toastr.success(res.message);
                    table.draw(false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong.');
                }
            });
        };

        $('#cpl-table').on('click', '.cpl-approve', function () {
            const id = $(this).data('id');
            if (!confirm('Approve this compensatory leave and credit it to the balance?')) return;
            decide(id, '{{ url("admin/compensatory-leaves") }}/' + id + '/approve');
        });

        $('#cpl-table').on('click', '.cpl-reject', function () {
            const id = $(this).data('id');
            const reason = prompt('Reason for rejection (optional):');
            if (reason === null) return;
            decide(id, '{{ url("admin/compensatory-leaves") }}/' + id + '/reject', { reason: reason });
        });
    });
</script>
@endpush
