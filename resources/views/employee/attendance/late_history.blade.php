@extends('employee.layouts.app')

@section('title', 'Late Arrival Intelligence')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="page-title mb-1" style="font-size: 2rem;">Late Arrival Intelligence</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">Compliance</a></li>
                        <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1" aria-current="page">Time Variance Logs</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                @can('delete-late-arrival')
                <a href="{{ route('employee.late_arrivals.trash') }}" class="tw-btn-danger d-flex align-items-center gap-2" style="border-radius: 50px; padding: 10px 24px; font-weight: 700;">
                    <i class="bi bi-trash3-fill"></i> Recycle Bin
                </a>
                @endcan
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
                    <div class="icon-container" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Incident Volume</div>
                        <div class="stat-value" id="stat-incidents">0</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: #fff7ed; color: #ea580c;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Time Leakage</div>
                        <div class="stat-value" id="stat-minutes">0h</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card-saas">
                    <div class="icon-container" style="background: #f0fdf4; color: #16a34a;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Compliance Index</div>
                        <div class="stat-value" id="stat-compliance">94.2%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SaaS Command Center -->
        <div class="command-center">
            <div class="row align-items-end g-3">
                <div class="col-lg-4">
                    <label class="small fw-bold text-muted mb-2">Search Personnel</label>
                    <input type="text" id="filter-employee" class="saas-filter-input" placeholder="Type name to locate...">
                </div>
                <div class="col-lg-4">
                    <label class="small fw-bold text-muted mb-2">Target Date</label>
                    <input type="date" id="filter-date" class="saas-filter-input">
                </div>
                <div class="col-lg-4">
                    <div class="d-flex gap-2 justify-content-end">
                        <button id="filter-button" class="btn-saas-generate flex-grow-1">
                            <i class="bi bi-funnel-fill"></i> Filter Matrix
                        </button>
                        <button id="reset-filters" class="btn-saas-action" style="height: 48px; width: 48px;" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Table Matrix -->
        <div class="premium-table-card">
            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-family: 'Outfit'; font-weight: 800;">Time Variance Dataset</h5>
                    <p class="text-muted small mb-0">Audit log for punctuality compliance</p>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="w-100">
                    <table class="table table-hover dt-responsive nowrap w-100" id="late-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                
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


    <!-- Details Modal -->
    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-0 bg-light p-4">
                    <h5 class="modal-title fw-extrabold text-dark"><i class="bi bi-cpu-fill me-2 text-primary"></i>Operational Signal Analysis</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="attendance-details-content">
                    <!-- Loaded dynamically -->
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
                    url: '{{ route("employee.attendance.late_history.data") }}',
                    data: d => {
                        d.name = $('#filter-employee').val();
                        d.date = $('#filter-date').val();
                    },
                    dataSrc: json => {
                        $('#stat-incidents').text(json.total_incidents || 0);
                        $('#stat-minutes').text((json.lost_hours || 0) + 'h');
                        return json.data;
                    }
                },
                columns: [
                    { data: 'formatted_date', name: 'late_arrivals.date' },
                    
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

            $('.dataTables_filter input').addClass('saas-filter-input').css('width', '250px');

            $('#filter-button').on('click', () => table.draw());
            $('#reset-filters').on('click', () => {
                $('#filter-employee').val('');
                $('#filter-date').val('');
                table.draw();
            });

            // View Details click handler
            $(document).on('click', '.view-details', function () {
                const id = $(this).data('id');
                const $content = $('#attendance-details-content');
                
                // Clear old content & show premium loader
                $content.html(`
                    <div class="modal-loader-wrapper d-flex flex-column align-items-center justify-content-center p-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="loader-text animate-pulse fw-bold">Analyzing Operational Signals...</div>
                    </div>
                `);

                $('#attendanceDetailsModal').modal('show');
                
                // Load new data
                $content.load(`/employee/attendance/details/${id}`, function (response, status, xhr) {
                    if (status === "error") {
                        $content.html(`
                            <div class="p-5 text-center">
                                <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                <h5 class="fw-bold">Intelligence Failure</h5>
                                <p class="text-muted">Could not retrieve operational signals. Please try again.</p>
                            </div>
                        `);
                    }
                });
            });

        });
    </script>
@endpush