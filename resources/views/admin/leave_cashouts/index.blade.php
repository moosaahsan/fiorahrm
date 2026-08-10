@extends('admin.layouts.app')

@section('title', 'Leave Encashment')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
<div class="sm:col-span-6 text-left">
    <h4 class="tw-page-title">Leave Encashment</h4>
    <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-sm font-semibold">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-brand-600 hover:text-brand-700">Admin</a></li>
        <li class="breadcrumb-item active text-slate-500">Leave Encashment</li>
    </ol>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-brand-600">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">Year-End Leave Encashment</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Leave does not carry forward. Pay out whatever is left — the amount is entered by HR and added to the selected payroll run.</p>
            </div>
        </div>
        @can('manage-leave-cashouts')
            <button class="tw-btn-primary h-12 px-6" data-toggle="modal" data-target="#cashout-modal">
                <i class="bi bi-plus-lg"></i> Record Encashment
            </button>
        @endcan
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Awaiting Payroll</p>
            <p class="mb-0 text-3xl font-extrabold text-amber-600">{{ $summary['pending'] }}</p>
        </div>
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Pending Amount</p>
            <p class="mb-0 text-2xl font-extrabold text-slate-700">Rs. {{ number_format($summary['pending_amount'], 0) }}</p>
        </div>
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Paid Out</p>
            <p class="mb-0 text-2xl font-extrabold text-emerald-600">Rs. {{ number_format($summary['paid_amount'], 0) }}</p>
        </div>
        <div class="tw-directory-card p-5">
            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Days Encashed</p>
            <p class="mb-0 text-3xl font-extrabold text-brand-600">{{ rtrim(rtrim(number_format($summary['days_cashed'], 2), '0'), '.') }}</p>
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
                    <option value="Paid">Paid</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="tw-filter-label"><i class="bi bi-calendar-range mr-1"></i> Leave Year</label>
                <select id="filter-year" class="tw-form-input h-12 py-0">
                    <option value="">All Years</option>
                    @foreach(range(now()->year - 3, now()->year) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button class="tw-btn-primary h-12 px-6" id="filter-button"><i class="bi bi-funnel-fill"></i> Search</button>
                <button class="tw-btn-secondary h-12 px-5" id="reset-filters"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </div>
    </div>

    <div class="tw-directory-card overflow-hidden p-0">
        <table id="cashout-table" class="table table-hover mb-0 w-100 tw-admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Year</th>
                    <th>Leave Type</th>
                    <th>Days</th>
                    <th>Amount</th>
                    <th>Payroll Run</th>
                    <th>Status</th>
                    <th>Processed By</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@can('manage-leave-cashouts')
<div class="modal fade" id="cashout-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('admin.leave_cashouts.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Record Leave Encashment</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="tw-filter-label">Employee</label>
                    <select name="employee_id" id="cashout-employee" class="select2-modal tw-form-input h-12 py-0" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="tw-filter-label">Leave Year</label>
                    <select name="year" id="cashout-year" class="tw-form-input h-12 py-0" required>
                        @foreach(range(now()->year, now()->year - 3) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="tw-filter-label">Leave Type</label>
                    <select name="leave_type" id="cashout-type" class="tw-form-input h-12 py-0" required>
                        <option value="">Pick an employee and year first</option>
                    </select>
                    <small class="text-slate-500" id="remaining-hint">Only types with a remaining balance are listed.</small>
                </div>

                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="tw-filter-label">Days to Encash</label>
                        <input type="number" name="days" id="cashout-days" step="0.5" min="0.5" class="tw-form-input h-12" required>
                    </div>
                    <div>
                        <label class="tw-filter-label">Amount (Rs.)</label>
                        <input type="number" name="amount" step="0.01" min="0" class="tw-form-input h-12" required>
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="tw-filter-label">Pay in Month</label>
                        <select name="payroll_month" class="tw-form-input h-12 py-0" required>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="tw-filter-label">Payroll Year</label>
                        <select name="payroll_year" class="tw-form-input h-12 py-0" required>
                            @foreach(range(now()->year, now()->year + 1) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="tw-filter-label">Notes</label>
                    <textarea name="notes" rows="2" class="tw-form-input" placeholder="How the amount was arrived at"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-btn-secondary px-5 py-2.5" data-dismiss="modal">Cancel</button>
                <button type="submit" class="tw-btn-primary px-5 py-2.5">Record Encashment</button>
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
        $('.select2-modal').select2({ width: '100%', dropdownParent: $('#cashout-modal') });

        const statusBadge = function (status) {
            const map = {
                'Pending':   ['bg-amber-100 text-amber-700', 'hourglass-split'],
                'Paid':      ['bg-emerald-100 text-emerald-700', 'check-circle'],
                'Cancelled': ['bg-slate-100 text-slate-600', 'slash-circle'],
            };
            const [classes, icon] = map[status] || map['Cancelled'];
            return `<span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold ${classes}"><i class="bi bi-${icon}"></i> ${status}</span>`;
        };

        const table = $('#cashout-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.leave_cashouts.data") }}',
                data: function (d) {
                    d.employee_id = $('#filter-employee').val();
                    d.status = $('#filter-status').val();
                    d.year = $('#filter-year').val();
                }
            },
            columns: [
                { data: 'employee_name', name: 'employee.name' },
                { data: 'year', name: 'year' },
                { data: 'leave_type_name', name: 'leave_type' },
                { data: 'days_out', name: 'days' },
                { data: 'amount_out', name: 'amount' },
                { data: 'payroll_run', name: 'payroll_year', orderable: false },
                { data: 'status', name: 'status', render: statusBadge },
                { data: 'processed_by_name', name: 'processedBy.name' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right' },
            ],
            order: [[1, 'desc']],
            dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
            language: {
                search: "",
                searchPlaceholder: "Search encashments...",
                emptyTable: `<div class="py-8 text-center"><i class="bi bi-cash-coin text-4xl text-slate-300"></i><p class="mb-0 mt-3 font-bold text-slate-500">No encashments recorded yet.</p></div>`,
                processing: `<div class="flex items-center justify-center gap-2 text-brand-600"><i class="bi bi-arrow-repeat fa-spin"></i> Loading...</div>`,
                lengthMenu: "Show _MENU_ records"
            }
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px').css('padding', '6px 15px');

        $('#filter-button').on('click', () => table.draw());

        $('#reset-filters').on('click', function () {
            $('#filter-employee').val('').trigger('change');
            $('#filter-status').val('');
            $('#filter-year').val('');
            table.draw();
        });

        // Load the employee's remaining balances so HR can only cash out what exists.
        const loadBalances = function () {
            const employeeId = $('#cashout-employee').val();
            const year = $('#cashout-year').val();
            const $type = $('#cashout-type');

            if (!employeeId || !year) {
                $type.html('<option value="">Pick an employee and year first</option>');
                return;
            }

            $type.html('<option value="">Loading…</option>');

            $.get('{{ route("admin.leave_cashouts.balances") }}', { employee_id: employeeId, year: year })
                .done(function (res) {
                    if (!res.balances.length) {
                        $type.html('<option value="">No balance left to encash</option>');
                        $('#remaining-hint').text('This employee has nothing left to encash for that year.');
                        return;
                    }

                    const options = res.balances.map(b =>
                        `<option value="${b.leave_type}" data-remaining="${b.remaining}">${b.label} — ${b.remaining} day(s) left</option>`
                    );
                    $type.html('<option value="">Select leave type</option>' + options.join(''));
                    $('#remaining-hint').text('Only types with a remaining balance are listed.');
                })
                .fail(function () {
                    $type.html('<option value="">Could not load balances</option>');
                });
        };

        $('#cashout-employee, #cashout-year').on('change', loadBalances);

        // Cap the days field at what is actually left.
        $('#cashout-type').on('change', function () {
            const remaining = $(this).find(':selected').data('remaining');
            const $days = $('#cashout-days');
            if (remaining) {
                $days.attr('max', remaining).val(remaining);
            } else {
                $days.removeAttr('max').val('');
            }
        });

        $('#cashout-table').on('click', '.cashout-cancel', function () {
            const id = $(this).data('id');
            const reason = prompt('Reason for cancelling (optional):');
            if (reason === null) return;

            $.ajax({
                url: '{{ url("admin/leave-cashouts") }}/' + id + '/cancel',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', reason: reason },
                success: function (res) {
                    toastr.success(res.message);
                    table.draw(false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong.');
                }
            });
        });
    });
</script>
@endpush
