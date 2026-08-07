@extends('admin.layouts.app')

@section('title', 'Attendance Intelligence')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .saas-stat-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
            transition: all 0.25s ease;
            height: 100%;
            display: flex;
            align-items: center;
        }
        .saas-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            border-color: #e2e8f0;
        }
        .saas-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 14px;
            flex-shrink: 0;
        }
        .saas-stat-content {
            display: flex;
            flex-direction: column;
        }
        .saas-stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .saas-stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            line-height: 1.1;
            color: #0f172a;
        }
        /* Color Variants */
        .saas-stat-card.variant-success .saas-stat-icon { background: #dcfce7; color: #16a34a; }
        .saas-stat-card.variant-success .saas-stat-value { color: #15803d; }
        .saas-stat-card.variant-warning .saas-stat-icon { background: #e0f2fe; color: #0284c7; }
        .saas-stat-card.variant-warning .saas-stat-value { color: #0369a1; }
        .saas-stat-card.variant-danger .saas-stat-icon { background: #fee2e2; color: #dc2626; }
        .saas-stat-card.variant-danger .saas-stat-value { color: #b91c1c; }
        .saas-stat-card.variant-halfday .saas-stat-icon { background: #fef3c7; color: #d97706; }
        .saas-stat-card.variant-halfday .saas-stat-value { color: #b45309; }
        .saas-stat-card.variant-neutral .saas-stat-icon { background: #f1f5f9; color: #475569; }
        .saas-stat-card.variant-brand .saas-stat-icon { background: #e0e7ff; color: #4f46e5; }
        .saas-stat-card.variant-brand .saas-stat-value { color: #4338ca; }
    </style>
@endpush

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="tw-page-title mb-1 text-2xl">Attendance & Break Analytics</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0">
                        <li class="breadcrumb-item"><a href="#"
                                class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">HR Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1"
                            aria-current="page">Attendance Logs</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                @can('manage-attendance')
                    <button class="btn btn-outline-primary rounded-pill px-4" id="btnImportAttendance">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Import Attendance
                    </button>
                    <button class="tw-btn-primary rounded-full px-6" id="btnAddManualAttendance"
                        >
                        <i class="bi bi-calendar-plus-fill"></i> Manual Input
                    </button>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid attendance-logs-page">
        <!-- SaaS Stat Row -->
        <div class="row mb-5 g-4">
            <div class="col-xl-2 col-md-4">
                <div class="stat-card-saas h-100">
                    <div class="icon-container" style="background: var(--hrm-primary-soft); color: var(--hrm-primary);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Staff</div>
                        <div class="stat-value" id="stat-total-staff">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="stat-card-saas h-100">
                    <div class="icon-container" style="background: #f0fdf4; color: #16a34a;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Present</div>
                        <div class="stat-value" id="stat-present">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="stat-card-saas h-100">
                    <div class="icon-container" style="background: #fef2f2; color: #ef4444;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Absent</div>
                        <div class="stat-value" id="stat-absent">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="stat-card-saas h-100">
                    <div class="icon-container" style="background: #f0f9ff; color: #0ea5e9;">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Late</div>
                        <div class="stat-value" id="stat-late">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="stat-card-saas h-100">
                    <div class="icon-container" style="background: #fffbeb; color: #f59e0b;">
                        <i class="bi bi-calendar-range-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Half Day</div>
                        <div class="stat-value" id="stat-halfday">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="stat-card-saas h-100">
                    <div class="icon-container" style="background: #fafafa; color: #71717a;">
                        <i class="bi bi-stopwatch-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Worked Hours</div>
                        <div class="stat-value" id="stat-hours">0h</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SaaS Command Center -->
        <div class="command-center">
            <div class="filter-group-label">Search Parameters</div>
            <div class="row align-items-end g-3">
                <div class="col-lg-2">
                    <label class="small fw-bold text-muted mb-2">Select Department</label>
                    <select id="filter-department" class="saas-filter-input">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="small fw-bold text-muted mb-2">Select Employee</label>
                    <select id="filter-employee" class="saas-filter-input">
                        <option value="">All Employees</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="small fw-bold text-muted mb-2">Select Status</label>
                    <select id="filter-status" class="saas-filter-input">
                        <option value="">All Statuses</option>
                        <option value="Present">Present (Active)</option>
                        <option value="Absent (Paid)">Absent (Paid)</option>
                        <option value="Absent (Unpaid)">Absent (Unpaid)</option>
                        <option value="Half Day">Half Day</option>
                        <option value="Late">Late (Exceeded)</option>
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="small fw-bold text-muted mb-2">Select Shifts</label>
                    <select id="filter-multi-shift" class="saas-filter-input select2-multiple" multiple="multiple">
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->shift_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="small fw-bold text-muted mb-2">Select Date Range</label>
                    <div class="input-group">
                        <input type="text" id="filter-date" class="saas-filter-input" placeholder="Select dates...">
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="d-flex gap-2 justify-content-end">
                        <button id="filter-button" class="btn-saas-generate flex-grow-1">
                            <i class="bi bi-filter-right"></i> Search
                        </button>
                        <button id="reset-filters" class="btn-saas-reset" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Break Exceeded Analytical Report Dual-Boxes -->
        <div id="break-exceeded-summary-container" class="mb-4 animate-fade-in" style="display: none;">
            <div class="row g-4">
                <!-- Left Box: Days Exceeded List -->
                <div class="col-md-6">
                    <div class="tw-card h-100 border-0"
                        style="border-radius: 16px; border: 1px solid var(--slate-200); background: #ffffff; min-height: 280px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                        <div
                            class="tw-card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                            <h6 class="fw-extrabold mb-0 d-flex align-items-center gap-2"
                                style="font-family: 'Outfit'; font-weight: 800; font-size: 1.15rem; color: var(--slate-900);">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-danger animate-pulse"
                                    style="width: 38px; height: 38px; background: rgba(239, 68, 68, 0.1);">
                                    <i class="bi bi-clock-history fs-5 text-danger"></i>
                                </div>
                                Break Exceeded Logs
                            </h6>
                            <span class="tw-badge-danger px-3 py-1.5"
                                style="font-size: 0.78rem; border-radius: 6px; border: 1.5px solid rgba(239, 68, 68, 0.15);">Alert
                                Active</span>
                        </div>
                        <div class="tw-card-body px-4 pb-4 pt-2" style="max-height: 200px; overflow-y: auto;"
                            id="exceeded-days-logs-list">
                            <!-- Injected dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Right Box: Cumulative Stats -->
                <div class="col-md-6">
                    <div class="tw-card h-100 border-0"
                        style="border-radius: 16px; border: 1px solid var(--slate-200); background: #ffffff; min-height: 280px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); position: relative; overflow: hidden;">
                        <!-- Small decorative top bar -->
                        <div
                            style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #6366f1 0%, #f59e0b 100%);">
                        </div>
                        <div class="tw-card-body p-4 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3"
                                style="border-color: var(--slate-100) !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 44px; height: 44px; background: rgba(99, 102, 241, 0.08); color: var(--primary-indigo);">
                                        <i class="bi bi-activity fs-4 text-indigo"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1"
                                            style="font-family: 'Outfit'; font-weight: 800; font-size: 1.15rem; color: var(--slate-900);">
                                            Break Summary</h6>
                                        <p class="text-muted mb-0 small" style="font-weight: 600;">Combined over-break
                                            statistics</p>
                                    </div>
                                </div>
                                <div class="tw-badge rounded-full py-2 px-3 text-brand-700 border border-brand-100"
                                    style="background: rgba(99, 102, 241, 0.05); font-weight: 800;">
                                    Break Feed
                                </div>
                            </div>

                            <div class="row pt-2 g-3">
                                <!-- Incidents Box -->
                                <div class="col-6">
                                    <div class="p-3 text-center rounded-3"
                                        style="background: rgba(239, 68, 68, 0.04); border: 1px solid rgba(239, 68, 68, 0.1);">
                                        <span class="d-block text-muted small fw-bold text-uppercase mb-2"
                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--slate-500) !important;">Over-Break
                                            Days</span>
                                        <div class="fs-1 fw-extrabold text-danger" id="break-exceeded-days"
                                            style="font-family: 'Outfit'; font-weight: 900; line-height: 1; font-size: 2.2rem !important;">
                                            0 Days</div>
                                    </div>
                                </div>
                                <!-- Over-Break Durations Box -->
                                <div class="col-6">
                                    <div class="p-3 text-center rounded-3"
                                        style="background: rgba(245, 158, 11, 0.04); border: 1px solid rgba(245, 158, 11, 0.1);">
                                        <span class="d-block text-muted small fw-bold text-uppercase mb-2"
                                            style="font-size: 0.75rem; letter-spacing: 0.05em; color: var(--slate-500) !important;">Extra
                                            Break Duration</span>
                                        <div class="fs-1 fw-extrabold" id="break-exceeded-time"
                                            style="font-family: 'Outfit'; font-weight: 900; line-height: 1; color: var(--warning-amber); font-size: 2.2rem !important;">
                                            00m 00s</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Employee Attendance Summary -->
        <div id="attendance-summary-container" class="mb-4 animate-fade-in" style="display: none;">
            <div class="tw-card border-0"
                style="border-radius: 16px; border: 1px solid var(--slate-200); background: #ffffff; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02); position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #10b981 0%, #3b82f6 100%);"></div>
                
                <div class="tw-card-body p-4">
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--slate-100) !important;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                            style="width: 44px; height: 44px; background: rgba(16, 185, 129, 0.08); color: #10b981;">
                            <i class="bi bi-person-lines-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1" style="font-family: 'Outfit'; font-weight: 800; font-size: 1.15rem; color: var(--slate-900);">
                                Employee Attendance Summary</h6>
                            <p class="text-muted mb-0 small" style="font-weight: 600;">Personalized statistics for the selected period</p>
                        </div>
                    </div>

                    <div class="row g-4 align-items-center">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <!-- Stat items -->
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-neutral" title="Total Scheduled Working Days">
                                        <div class="saas-stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Total Days</span>
                                            <div class="saas-stat-value" id="summary-total-days">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-success" title="Total On-Time Days">
                                        <div class="saas-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">On-Time</span>
                                            <div class="saas-stat-value" id="summary-ontime-days">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-warning" title="Total Late Days">
                                        <div class="saas-stat-icon"><i class="bi bi-clock-history"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Late Days</span>
                                            <div class="saas-stat-value" id="summary-late-days">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-danger" title="Total Absent Days">
                                        <div class="saas-stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Absent Days</span>
                                            <div class="saas-stat-value" id="summary-absent-days">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-halfday" title="Total Half Days">
                                        <div class="saas-stat-icon"><i class="bi bi-star-half"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Half Days</span>
                                            <div class="saas-stat-value" id="summary-half-days">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-brand" title="Total Working Hours Logged">
                                        <div class="saas-stat-icon"><i class="bi bi-stopwatch-fill"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Total Hours</span>
                                            <div class="saas-stat-value" id="summary-worked-hours">0h</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-neutral" title="Total Break Time Spent">
                                        <div class="saas-stat-icon"><i class="bi bi-cup-hot-fill"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Total Break</span>
                                            <div class="saas-stat-value" id="summary-break-time">0h 0m</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="saas-stat-card variant-neutral" title="Overall Attendance Percentage">
                                        <div class="saas-stat-icon"><i class="bi bi-percent"></i></div>
                                        <div class="saas-stat-content">
                                            <span class="saas-stat-label">Attendance %</span>
                                            <div class="saas-stat-value" id="summary-percentage">0%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="p-4 rounded-3 h-100 d-flex flex-column justify-content-center align-items-center" style="background: #f8fafc; border: 1px solid #e2e8f0; min-height: 220px;">
                                <h6 class="fw-bold text-muted small mb-3 text-uppercase" style="letter-spacing: 0.05em;">Attendance Breakdown</h6>
                                <div style="position: relative; height: 160px; width: 100%; display: flex; justify-content: center;">
                                    <canvas id="attendanceSummaryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Table Container -->
        <div class="logs-table-card">
            <div class="tw-card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-family: 'Outfit'; font-weight: 800;">Attendance Logs
                    </h5>
                    <p class="text-muted small mb-0">Viewing shift records and check-in details</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button id="download-pdf-btn" class="tw-btn-secondary text-sm fw-bold d-inline-flex align-items-center gap-2"
                        style="border-radius: 10px; padding: 8px 18px; background: rgba(99, 102, 241, 0.08); color: var(--primary-indigo); border: 2px solid rgba(99, 102, 241, 0.15); transition: all 0.2s;"
                        onmouseover="this.style.background='rgba(99, 102, 241, 0.15)'"
                        onmouseout="this.style.background='rgba(99, 102, 241, 0.08)'">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                    </button>
                    <div class="text-end">
                        <span class="d-block small live-feed-label mb-1">Live Feed</span>
                        <span class="tw-badge live-status-active animate-pulse">ACTIVE</span>
                    </div>
                </div>
            </div>
            <div class="tw-card-body p-1">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="attendance-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Worked Hours / Break Details</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals (Manual, Edit, Details) -->
    <div class="modal fade" id="ajaxModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div id="ajaxModalContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small uppercase fw-bold">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" role="dialog" data-id="">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-0 bg-light p-4">
                    <h5 class="modal-title fw-extrabold text-dark"><i
                            class="bi bi-info-circle-fill me-2 text-primary"></i>Attendance Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="attendance-details-content">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small uppercase fw-bold">Loading details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function formatConciseTime(timeStr) {
            if (!timeStr) return '00s';
            const match = timeStr.match(/(\d+)\s*hours?\s*(\d+)\s*minutes?\s*(\d+)\s*seconds?/i);
            if (match) {
                const h = parseInt(match[1]);
                const m = parseInt(match[2]);
                const s = parseInt(match[3]);
                let formatted = '';
                if (h > 0) formatted += h + 'h ';
                if (m > 0 || h > 0) formatted += m + 'm ';
                formatted += s + 's';
                return formatted;
            }
            return timeStr;
        }

        $(document).ready(function () {
            // Default date to today
            const today = moment().format('YYYY-MM-DD');
            $('#filter-date').val(today + ' - ' + today);

            // Initialize Multi-Select Select2
            $('#filter-multi-shift').select2({
                placeholder: "All Shifts",
                allowClear: true,
                width: '100%'
            });

            /* ---------- DATATABLE CONFIG ---------- */
            const table = $('#attendance-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('admin.attendance.logs.data') }}",
                    data: d => {
                        d.department_id = $('#filter-department').val();
                        d.employee_id = $('#filter-employee').val();
                        d.multi_shift_ids = $('#filter-multi-shift').val();
                        d.status = $('#filter-status').val();
                        d.date_range = $('#filter-date').val();
                    },
                    dataSrc: json => {
                        // Stat updates with smooth transitions
                        const animateVal = (id, val) => {
                            $({ countNum: $(id).text() }).animate({ countNum: val }, {
                                duration: 800,
                                step: function () { $(id).text(Math.ceil(this.countNum)); },
                                complete: function () { $(id).text(this.countNum); }
                            });
                        };

                        animateVal('#stat-total-staff', json.total_staff);
                        animateVal('#stat-present', json.count_present);
                        animateVal('#stat-absent', json.count_absent);
                        animateVal('#stat-late', json.count_late);
                        animateVal('#stat-halfday', json.count_halfday);
                        $('#stat-hours').text((json.total_worked_hours || 0) + 'h');

                        // Dynamic Break Exceeded Report Dual-Box Integration
                        const empIdVal = $('#filter-employee').val();
                        if (empIdVal) {
                            $('#break-exceeded-time').text(formatConciseTime(json.total_exceeded_formatted));
                            $('#break-exceeded-days').text((json.total_exceeded_days || 0) + ' ' + ((json.total_exceeded_days === 1) ? 'Day' : 'Days'));

                            const $listContainer = $('#exceeded-days-logs-list');
                            $listContainer.empty();
                            if (json.exceeded_days && json.exceeded_days.length > 0) {
                                json.exceeded_days.forEach(day => {
                                    $listContainer.append(`
                                              <div class="d-flex justify-content-between align-items-center py-3 px-3 mb-2 rounded-3 animate-fade-in transition-all" style="background: #fff5f5; border: 1px solid #ffe3e3; transition: all 0.2s;">
                                                  <div class="d-flex align-items-center" style="min-width: 0;">
                                                      <div class="d-flex align-items-center justify-content-center rounded-3 text-white shadow-sm" style="width: 38px; height: 38px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); flex-shrink: 0; min-width: 38px;">
                                                          <i class="bi bi-clock-history fs-5"></i>
                                                      </div>
                                                      <div style="margin-left: 12px; min-width: 0;">
                                                          <span class="fw-bold text-dark d-block" style="font-size: 0.92rem; font-family: 'Outfit'; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${day.date}</span>
                                                          <span class="text-muted fw-semibold d-block" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Threshold Exceeded</span>
                                                      </div>
                                                  </div>
                                                  <span class="tw-badge-danger fw-extrabold" style="background: #ffffff; color: #e11d48; border: 1.5px solid #ffa3a3; border-radius: 6px; padding: 6px 12px; font-size: 0.82rem; box-shadow: var(--shadow-sm); flex-shrink: 0; margin-left: 12px;">
                                                     Exceeded by ${formatConciseTime(day.time)}
                                                  </span>
                                              </div>
                                          `);
                                });
                            } else {
                                $listContainer.append(`
                                          <div class="text-center py-5 animate-fade-in">
                                              <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px; background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                                  <i class="bi bi-patch-check-fill fs-3 animate-bounce"></i>
                                              </div>
                                              <h6 class="fw-bold mb-1" style="font-size: 1.05rem; color: #15803d;">Fully Compliant Status</h6>
                                              <p class="text-muted mb-0 small fw-bold">All breaks are within corporate policy limits.</p>
                                          </div>
                                      `);
                            }
                            $('#break-exceeded-summary-container').slideDown(400);

                            // --- Populate Attendance Summary ---
                            const totalDays = (json.count_present || 0) + (json.count_absent || 0) + (json.count_halfday || 0) + (json.count_late || 0);
                            const attendancePct = totalDays > 0 ? Math.round(((json.count_present || 0) + (json.count_late || 0) + (json.count_halfday || 0)) / totalDays * 100) : 0;
                            
                            $('#summary-total-days').text(totalDays);
                            $('#summary-ontime-days').text(json.count_present || 0);
                            $('#summary-late-days').text(json.count_late || 0);
                            $('#summary-absent-days').text(json.count_absent || 0);
                            $('#summary-half-days').text(json.count_halfday || 0);
                            $('#summary-worked-hours').text((json.total_worked_hours || 0) + 'h');
                            $('#summary-break-time').text(json.total_break_formatted || '0h 0m');
                            $('#summary-percentage').text(attendancePct + '%');
                            
                            // --- Update Chart ---
                            if (window.attendanceChartInstance) {
                                window.attendanceChartInstance.destroy();
                            }
                            
                            if (totalDays > 0) {
                                const ctx = document.getElementById('attendanceSummaryChart').getContext('2d');
                                window.attendanceChartInstance = new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['On-Time', 'Late', 'Absent', 'Half Day'],
                                        datasets: [{
                                            data: [
                                                json.count_present || 0,
                                                json.count_late || 0,
                                                json.count_absent || 0,
                                                json.count_halfday || 0
                                            ],
                                            backgroundColor: [
                                                '#16a34a', // green
                                                '#0ea5e9', // blue
                                                '#dc2626', // red
                                                '#f59e0b'  // yellow
                                            ],
                                            borderWidth: 0,
                                            hoverOffset: 4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '75%',
                                        plugins: {
                                            legend: {
                                                display: false
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return ' ' + context.label + ': ' + context.raw + ' Days';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                            
                            $('#attendance-summary-container').slideDown(400);
                        } else {
                            $('#break-exceeded-summary-container').slideUp(400);
                            $('#attendance-summary-container').slideUp(400);
                        }

                        return json.data;
                    }
                },
                columns: [
                    {
                        data: 'shift_date',
                        name: 'shift_date',
                        render: d => d !== '-' ? moment(d).format('DD MMM, YYYY') : '-'
                    },
                    {
                        data: 'employee_name',
                        name: 'employees.name',
                        render: function (data, type, row) {
                            const initials = data.split(' ').map(n => n[0]).join('').toUpperCase();
                            const avatar = row.profile_pic_url ? `<img src="${row.profile_pic_url}" class="emp-avatar">` : `<div class="emp-avatar">${initials}</div>`;
                            return `
                                    <div class="emp-identity">
                                        ${avatar}
                                        <div class="emp-info">
                                            <span class="name">${data}</span>
                                            <span class="id-badge">${row.employee_id}</span>
                                        </div>
                                    </div>
                                `;
                        }
                    },
                    { data: 'shift_name', name: 'shift_name', orderable: false, searchable: false },
                    { data: 'check_in', name: 'check_in' },
                    { data: 'check_out', name: 'check_out', render: d => d }, // badges handled in controller
                    { data: 'worked_hours', name: 'worked_hours', orderable: false, searchable: false },
                    { data: 'status_badge', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ],
                order: [[0, 'desc']],
                createdRow: function (row, data, dataIndex) {
                    if (data.status_badge.includes('absent')) {
                        $(row).addClass('row-absent');
                    }
                },
                dom: '<"p-4 d-flex justify-content-between align-items-center"lf>rt<"p-4 d-flex justify-content-between align-items-center"ip>',
                language: {
                    search: "",
                    searchPlaceholder: "Query logs...",
                    lengthMenu: "Show _MENU_ logs"
                },
                pageLength: 20
            });

            // Styling Search Input
            $('.dataTables_filter input').addClass('saas-filter-input').css('width', '250px');

            /* ---------- FILTER ACTIONS ---------- */
            $('#filter-department').change(function () {
                const deptId = $(this).val();
                const $empSelect = $('#filter-employee');

                // Reset employee select
                $empSelect.html('<option value="">All Personnel</option>');

                if (deptId) {
                    // Fetch employees for this department
                    $.get(`/admin/department/${deptId}/employees`, function (employees) {
                        employees.forEach(emp => {
                            $empSelect.append(`<option value="${emp.id}">${emp.name}</option>`);
                        });
                    });
                } else {
                    // If no department selected, we might want to reload all accessible employees?
                    // For now, keep it simple or trigger a reload of initial list
                }
            });

            $('#filter-button').click(function () {
                $(this).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
                table.draw();
                setTimeout(() => $(this).html('<i class="bi bi-filter-right me-1"></i> Apply'), 600);
            });

            $('#reset-filters').click(() => {
                $('#filter-department').val('').trigger('change');
                $('#filter-employee').val('').trigger('change');
                $('#filter-status').val('').trigger('change');
                $('#filter-multi-shift').val(null).trigger('change');
                $('#filter-date').val('');
                table.draw();
            });

            /* Timeline Range Selector */
            $('#filter-date').daterangepicker({
                autoUpdateInput: false,
                locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
            }).on('apply.daterangepicker', (e, p) =>
                $(e.target).val(`${p.startDate.format('YYYY-MM-DD')} - ${p.endDate.format('YYYY-MM-DD')}`)
            ).on('cancel.daterangepicker', e => $(e.target).val(''));

            /* ---------- DOWNLOAD COMPLIANCE PDF REPORT ---------- */
            $('#download-pdf-btn').click(function () {
                const $btn = $(this);
                const originalHtml = $btn.html();

                $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');
                $btn.prop('disabled', true);

                const params = {
                    department_id: $('#filter-department').val(),
                    employee_id: $('#filter-employee').val(),
                    multi_shift_ids: $('#filter-multi-shift').val(),
                    status: $('#filter-status').val(),
                    date_range: $('#filter-date').val(),
                    export_pdf: 1
                };

                $.ajax({
                    url: "{{ route('admin.attendance.logs.data') }}",
                    method: 'GET',
                    data: params,
                    success: function (res) {
                        try {
                            const filters = {
                                dateRange: $('#filter-date').val() || 'All Time',
                                departmentName: $('#filter-department').val() ? $('#filter-department option:selected').text() : 'All Departments',
                                employeeName: $('#filter-employee').val() ? $('#filter-employee option:selected').text() : null,
                                shiftName: $('#filter-multi-shift').val() && $('#filter-multi-shift').val().length ? $('#filter-multi-shift option:selected').map(function () { return this.text; }).get().join(', ') : 'All Shifts'
                            };

                            buildCompliancePdf(res, filters);
                            toastr.success('Compliance PDF generated successfully!');
                        } catch (e) {
                            console.error(e);
                            toastr.error('Failed to compile PDF report.');
                        } finally {
                            $btn.html(originalHtml);
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        toastr.error('Failed to fetch data for PDF export.');
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                });
            });

            function buildCompliancePdf(data, filters) {
                // Short Worked Hours string helper for lay-man clarity
                function formatWorkedHoursForPdf(hoursStr) {
                    if (!hoursStr) return '-';
                    return hoursStr
                        .replace('Worked: ', '')
                        .replace(' Hours', 'h')
                        .replace('Break Spent: ', 'Spent: ')
                        .replace('Allowed: ', 'Allowed: ');
                }

                const tableBody = [
                    [
                        { text: 'Shift Date', style: 'tableHeader' },
                        { text: 'Emp ID', style: 'tableHeader' },
                        { text: 'Employee Name', style: 'tableHeader' },
                        { text: 'Shift Class', style: 'tableHeader' },
                        { text: 'Check In', style: 'tableHeader' },
                        { text: 'Check Out', style: 'tableHeader' },
                        { text: 'Net Duration / Break Intelligence', style: 'tableHeader' },
                        { text: 'Status Matrix', style: 'tableHeader' }
                    ]
                ];

                data.logs.forEach(log => {
                    tableBody.push([
                        { text: log.shift_date || '-', style: 'tableCell' },
                        { text: log.employee_id || '-', style: 'tableCell' },
                        { text: log.employee_name || '-', style: 'tableCell' },
                        { text: log.shift_name || '-', style: 'tableCell' },
                        { text: log.check_in || '-', style: 'tableCell' },
                        { text: log.check_out || '-', style: 'tableCell' },
                        { text: formatWorkedHoursForPdf(log.worked_hours), style: 'tableCell' },
                        { text: log.status || '-', style: 'tableCell' }
                    ]);
                });

                const tableChronologyBody = [
                    [
                        { text: 'Date of Incident', style: 'chronologyHeader' },
                        { text: 'Duration Exceeded Over Break Limit Policy', style: 'chronologyHeader' }
                    ]
                ];
                if (filters.employeeName && data.stats.exceeded_days && data.stats.exceeded_days.length > 0) {
                    data.stats.exceeded_days.forEach(day => {
                        tableChronologyBody.push([
                            { text: day.date, style: 'bulletCellDate' },
                            { text: `Exceeded by ${formatConciseTime(day.time)}`, style: 'bulletCellTime' }
                        ]);
                    });
                }

                const docContent = [
                    // Title Banner Block (SaaS Left border style)
                    {
                        table: {
                            widths: ['*'],
                            body: [
                                [
                                    {
                                        border: [true, false, false, false],
                                        borderColor: ['#4f46e5', null, null, null],
                                        fillColor: '#f8fafc',
                                        stack: [
                                            { text: 'WORKFORCE ATTENDANCE & COMPLIANCE SUMMARY', style: 'reportTitle', bold: true, fontSize: 13, color: '#0f172a' },
                                            { text: 'HUMAN CAPITAL ANALYTICS AND COMPLIANCE REPORT', fontSize: 7, color: '#4f46e5', bold: true, margin: [0, 2, 0, 0], letterSpacing: 1 }
                                        ],
                                        margin: [15, 10, 15, 10]
                                    }
                                ]
                            ]
                        },
                        layout: {
                            hLineWidth: function () { return 0; },
                            vLineWidth: function (i) { return i === 0 ? 3.5 : 0; },
                            vLineColor: function () { return '#4f46e5'; }
                        },
                        margin: [0, 0, 0, 20]
                    },

                    // Metadata box
                    {
                        table: {
                            widths: ['*', '*'],
                            body: [
                                [
                                    { text: [{ text: 'Timeframe Range:  ', bold: true, color: '#4f46e5' }, filters.dateRange], style: 'metaText' },
                                    { text: [{ text: 'Department Scope:  ', bold: true, color: '#4f46e5' }, filters.departmentName], style: 'metaText', alignment: 'right' }
                                ],
                                [
                                    { text: [{ text: 'Shift Profile Match:  ', bold: true, color: '#4f46e5' }, filters.shiftName], style: 'metaText' },
                                    { text: [{ text: 'Generated At:  ', bold: true, color: '#4f46e5' }, moment().format('DD-MMM-YYYY hh:mm A')], style: 'metaText', alignment: 'right' }
                                ]
                            ]
                        },
                        layout: {
                            hLineWidth: function (i) { return (i === 1) ? 0.75 : 0; },
                            vLineWidth: function () { return 0; },
                            hLineColor: function () { return '#f1f5f9'; },
                            paddingTop: function () { return 6; },
                            paddingBottom: function () { return 6; }
                        },
                        margin: [0, 0, 0, 20]
                    },

                    // Dual Core Stats Section
                    {
                        columns: [
                            {
                                width: '*',
                                stack: [
                                    { text: 'WORKFORCE PERFORMANCE SCORECARD', style: 'sectionHeader' },
                                    {
                                        table: {
                                            widths: ['*', 'auto'],
                                            body: [
                                                [{ text: 'Worked Hours Cumulative Sum', style: 'fieldLabel' }, { text: `${data.stats.total_worked_hours || 0} hrs`, style: 'fieldValue' }],
                                                [{ text: 'Late Attendances Logged', style: 'fieldLabel' }, { text: `${data.stats.count_late || 0} times (Delay: ${data.stats.total_late_minutes || 0}m)`, style: 'fieldValue' }],
                                                [{ text: 'Total Present Days', style: 'fieldLabel' }, { text: `${data.stats.count_present || 0} Days`, style: 'fieldValuePresent' }],
                                                [{ text: 'Total Absent Days', style: 'fieldLabel' }, { text: `${data.stats.count_absent || 0} Days`, style: 'fieldValueAbsent' }],
                                                [{ text: 'Half Day Drift Incidents', style: 'fieldLabel' }, { text: `${data.stats.count_halfday || 0} times`, style: 'fieldValueWarning' }]
                                            ]
                                        },
                                        layout: {
                                            hLineWidth: function () { return 0.5; },
                                            vLineWidth: function () { return 0; },
                                            hLineColor: function () { return '#f1f5f9'; },
                                            paddingTop: function () { return 5; },
                                            paddingBottom: function () { return 5; }
                                        }
                                    }
                                ]
                            },
                            {
                                width: 300,
                                margin: [25, 0, 0, 0],
                                stack: filters.employeeName ? [
                                    { text: 'BREAK COMPLIANCE SUMMARY', style: 'sectionHeader' },
                                    {
                                        table: {
                                            widths: ['*', 'auto'],
                                            body: [
                                                [{ text: 'Audited Personnel Target', style: 'fieldLabel' }, { text: filters.employeeName, style: 'fieldValueBold' }],
                                                [{ text: 'Limit Exceeded Incidents', style: 'fieldLabel' }, { text: `${data.stats.total_exceeded_days || 0} times`, style: 'fieldValueWarning' }],
                                                [{ text: 'Accumulated Over-Break Limit', style: 'fieldLabel' }, { text: formatConciseTime(data.stats.total_exceeded_formatted), style: 'fieldValueWarning' }]
                                            ]
                                        },
                                        layout: {
                                            hLineWidth: function () { return 0.5; },
                                            vLineWidth: function () { return 0; },
                                            hLineColor: function () { return '#f1f5f9'; },
                                            paddingTop: function () { return 5; },
                                            paddingBottom: function () { return 5; }
                                        }
                                    }
                                ] : [
                                    { text: 'COMPLIANCE AUDIT LAB', style: 'sectionHeader' },
                                    {
                                        text: 'No individual employee was targeted for compliance auditing. Select a personnel filter to preview daily logs and totals for break compliance assessment.',
                                        style: 'emptyDisclaimer'
                                    }
                                ]
                            }
                        ],
                        margin: [0, 0, 0, 20]
                    },

                    // Chronology Table (Only visible when targeted employee and violations exist)
                    (filters.employeeName && data.stats.exceeded_days && data.stats.exceeded_days.length > 0) ? {
                        stack: [
                            { text: 'DAILY BREAK LIMIT VIOLATION LOGS', style: 'sectionHeader' },
                            {
                                table: {
                                    widths: ['35%', '65%'],
                                    body: tableChronologyBody
                                },
                                layout: {
                                    hLineWidth: function (i, node) { return (i === 0 || i === node.table.body.length) ? 1 : 0.5; },
                                    vLineWidth: function () { return 0; },
                                    hLineColor: function (i, node) { return i === 0 ? '#4f46e5' : '#f1f5f9'; },
                                    fillColor: function (rowIndex) {
                                        if (rowIndex === 0) return '#f8fafc';
                                        return null;
                                    },
                                    paddingTop: function () { return 5; },
                                    paddingBottom: function () { return 5; }
                                },
                                margin: [0, 0, 0, 20]
                            }
                        ]
                    } : null,

                    // Main Database Table
                    { text: 'WORKFORCE OPERATIONAL RAW RECORDS GRID', style: 'sectionHeader', margin: [0, 10, 0, 8] },
                    {
                        table: {
                            headerRows: 1,
                            widths: [65, 55, 110, 110, 60, 60, 180, 80],
                            body: tableBody
                        },
                        layout: {
                            hLineWidth: function (i, node) { return (i === 0 || i === node.table.body.length) ? 1.25 : 0.5; },
                            vLineWidth: function () { return 0; },
                            hLineColor: function (i, node) { return i === 0 ? '#4f46e5' : '#f1f5f9'; },
                            fillColor: function (rowIndex) {
                                if (rowIndex === 0) return '#f8fafc';
                                return (rowIndex % 2 === 0) ? '#f8fafc' : '#ffffff';
                            },
                            paddingLeft: function () { return 8; },
                            paddingRight: function () { return 8; },
                            paddingTop: function () { return 6; },
                            paddingBottom: function () { return 6; }
                        }
                    }
                ];

                const docDefinition = {
                    pageSize: 'A4',
                    pageOrientation: 'landscape',
                    pageMargins: [35, 45, 35, 45],
                    footer: function (currentPage, pageCount) {
                        return {
                            text: `Page ${currentPage} of ${pageCount}`,
                            alignment: 'right',
                            fontSize: 8,
                            margin: [0, 0, 35, 0],
                            color: '#64748b',
                            bold: true
                        };
                    },
                    content: docContent.filter(Boolean),
                    styles: {
                        metaText: { fontSize: 8, color: '#334155', bold: true },
                        sectionHeader: { fontSize: 9, bold: true, color: '#0f172a', margin: [0, 8, 0, 5], letterSpacing: 0.5 },
                        tableHeader: { fontSize: 8, bold: true, color: '#4f46e5', alignment: 'left' },
                        chronologyHeader: { fontSize: 8, bold: true, color: '#4f46e5', alignment: 'left' },
                        tableCell: { fontSize: 7.5, color: '#1e293b' },
                        bulletCellDate: { fontSize: 7.5, color: '#1e293b', bold: true },
                        bulletCellTime: { fontSize: 7.5, color: '#e11d48', bold: true },
                        fieldLabel: { fontSize: 8, color: '#475569', margin: [0, 2, 0, 2] },
                        fieldValue: { fontSize: 8, color: '#0f172a', bold: true, margin: [0, 2, 0, 2] },
                        fieldValuePresent: { fontSize: 8, color: '#15803d', bold: true, margin: [0, 2, 0, 2] },
                        fieldValueAbsent: { fontSize: 8, color: '#b45309', bold: true, margin: [0, 2, 0, 2] },
                        fieldValueBold: { fontSize: 8, color: '#4f46e5', bold: true, margin: [0, 2, 0, 2] },
                        fieldValueWarning: { fontSize: 8, color: '#e11d48', bold: true, margin: [0, 2, 0, 2] },
                        emptyDisclaimer: { fontSize: 8, italic: true, color: '#64748b', margin: [10, 10, 10, 10] }
                    },
                    defaultStyle: { font: 'Roboto' }
                };

                pdfMake.createPdf(docDefinition).download(`AMS_Attendance_Compliance_Report_${moment().format('YYYYMMDD')}.pdf`);
            }

            /* ---------- VIEW DETAILS MODAL ---------- */
            $(document).on('click', '.view-details', function () {
                const id = $(this).data('id');
                const $content = $('#attendance-details-content');

                // Clear old content & show premium loader
                $content.html(`
                        <div class="modal-loader-wrapper">
                            <div class="saas-spinner"></div>
                            <div class="loader-text animate-pulse">Analyzing Operational Signals...</div>
                        </div>
                    `);

                $('#attendanceDetailsModal').attr('data-id', id).modal('show');

                // Load new data
                $content.load(`/admin/attendance/details/${id}`, function (response, status, xhr) {
                    if (status === "error") {
                        $content.html(`
                                <div class="p-5 text-center">
                                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="fw-bold">Intelligence Failure</h5>
                                    <p class="text-muted">Could not retrieve operational signals. Please try again.</p>
                                </div>
                            `);
                    }
                });
            });

            /* ---------- ACTIONS (Manual Add / Edit) ---------- */
            $('#btnAddManualAttendance').click(() => {
                $('#ajaxModal').modal('show');
                $('#ajaxModalContent').load("{{ route('admin.attendance.manual.create') }}");
            });

            $('#btnImportAttendance').click(() => {
                $('#ajaxModal').modal('show');
                $('#ajaxModalContent').load("{{ route('admin.attendance.import.modal') }}");
            });

            $(document).on('click', '.edit-attendance', function () {
                const id = $(this).data('id');
                $('#ajaxModal').modal('show');
                $('#ajaxModalContent').load(`/admin/attendance/manual/edit/${id}`);
            });

            /* ---------- BREAK ACTIONS (Approve / Reject) ---------- */
            $(document).on('click', '.approve', function () {
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
                                    // Refresh modal content instead of full reload for better UX
                                    const attendanceId = $('#attendanceDetailsModal').attr('data-id');
                                    $('#attendance-details-content').load(`/admin/attendance/details/${attendanceId}`);
                                    // Also refresh main table
                                    table.draw(false);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.reject', function () {
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
                                    // Refresh modal content instead of full reload
                                    const attendanceId = $('#attendanceDetailsModal').attr('data-id');
                                    $('#attendance-details-content').load(`/admin/attendance/details/${attendanceId}`);
                                    // Also refresh main table
                                    table.draw(false);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush