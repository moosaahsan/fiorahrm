@extends('admin.layouts.app')

@section('title', 'Late Arrival Intelligence')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="tw-page-title mb-1 text-2xl">Late Arrival Intelligence</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#" class="small text-uppercase font-bold text-slate-500 no-underline">Compliance</a></li>
                        <li class="breadcrumb-item active small font-bold uppercase text-brand-600" aria-current="page">Time Variance Logs</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                @can('delete-late-arrival')
                <a href="{{ route('admin.late_arrivals.trash') }}" class="tw-btn-secondary !rounded-full px-6 py-2.5">
                    <i class="bi bi-trash3-fill"></i> Recycle Bin
                </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="tw-stat-card">
                <div class="icon-container !bg-rose-50 !text-rose-500">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <div>
                    <div class="stat-label">Incident Volume</div>
                    <div class="stat-value" id="stat-incidents">0</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container !bg-orange-50 !text-orange-600">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-label">Time Leakage</div>
                    <div class="stat-value" id="stat-minutes">0h</div>
                </div>
            </div>
            <div class="tw-stat-card">
                <div class="icon-container !bg-emerald-50 !text-emerald-600">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="stat-label">Compliance Index</div>
                    <div class="stat-value" id="stat-compliance">94.2%</div>
                </div>
            </div>
        </div>

        <div class="tw-command-hub">
            <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                <div class="md:col-span-4">
                    <label class="tw-filter-label">Search Personnel</label>
                    <input type="text" id="filter-employee" class="tw-form-input" placeholder="Type name to locate...">
                </div>
                <div class="md:col-span-4">
                    <label class="tw-filter-label">Target Date</label>
                    <input type="date" id="filter-date" class="tw-form-input" value="{{ today()->toDateString() }}">
                </div>
                <div class="flex gap-2 md:col-span-4">
                    <button id="filter-button" class="tw-btn-primary h-12 flex-1">
                        <i class="bi bi-funnel-fill"></i> Filter Matrix
                    </button>
                    <button id="reset-filters" class="tw-action-btn h-12 w-12 shrink-0" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="tw-directory-card overflow-hidden p-0">
            <div class="border-b border-slate-100 p-5">
                <h5 class="mb-0 font-display text-lg font-extrabold text-slate-900">Time Variance Dataset</h5>
                <p class="mb-0 text-sm text-slate-500">Audit log for punctuality compliance</p>
            </div>
            <div class="p-1">
                <div class="table-responsive">
                    <table class="table table-hover tw-admin-table w-100" id="late-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Identity Architecture</th>
                                <th>Scheduled In</th>
                                <th>Check-In</th>
                                <th>Variance</th>
                                <th>Root Cause/Reason</th>
                                <th>Utility</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content tw-modal-shell">
                <div id="editLateModalContent">
                    <div class="py-5 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-sm font-bold uppercase text-slate-500">Initializing Interface...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const table = $('#late-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route("admin.late_arrivals.index") }}',
                    data: d => {
                        d.name = $('#filter-employee').val();
                        d.date = $('#filter-date').val();
                    },
                    dataSrc: json => {
                        $('#stat-incidents').text(json.total_incidents);
                        $('#stat-minutes').text(json.lost_hours + 'h');
                        return json.data;
                    }
                },
                columns: [
                    { data: 'formatted_date', name: 'late_arrivals.date' },
                    {
                        data: 'employee_name',
                        name: 'employees.name',
                        render: function(data, type, row) {
                            const initials = data.split(' ').map(n => n[0]).join('').toUpperCase();
                            const avatar = row.profile_pic_url ? `<img src="${row.profile_pic_url}" class="emp-avatar">` : `<div class="emp-avatar">${initials}</div>`;
                            return `
                                <div class="emp-identity">
                                    ${avatar}
                                    <div class="emp-info">
                                        <span class="name">${data}</span>
                                        <span class="id-badge">${row.employee_id_badge}</span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { data: 'scheduled_start', name: 'late_arrivals.scheduled_start' },
                    { data: 'actual_check_in', name: 'late_arrivals.actual_check_in' },
                    { data: 'late_duration', name: 'late_arrivals.late_minutes', orderable: false, searchable: false },
                    { data: 'late_reason', name: 'late_arrivals.late_reason', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ],
                order: [[0, 'desc']],
                dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Query variances...",
                    lengthMenu: "Show _MENU_ logs"
                },
                pageLength: 20
            });

            $('.dataTables_filter input').addClass('tw-form-input').css('width', '250px');

            $('#filter-button').on('click', () => table.draw());
            $('#reset-filters').on('click', () => {
                $('#filter-employee').val('');
                $('#filter-date').val('');
                table.draw();
            });

            $(document).on('click', '.edit_late', function () {
                const id = $(this).data('id');
                $('#editLateModal').modal('show');
                $('#editLateModalContent').load(`/admin/late-arrivals/${id}/edit`);
            });

            $(document).on('click', '.delete_late', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Incident Log?',
                    text: "This operation cannot be reversed instantly.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Confirm Deletion'
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            url: `/admin/late-arrivals/${id}`,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: () => {
                                toastr.success('Log successfully purged');
                                table.ajax.reload(null, false);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
