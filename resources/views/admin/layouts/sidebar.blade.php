<!-- ========== Left Sidebar Start ========== -->
<div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Main</li>

                <li>
                    <a href="{{ route('dashboard') }}"
                        class="waves-effect {{ request()->is('admin/dashboard*') || request()->routeIs('admin.dashboard') || request()->is('dashboard') ? 'mm active' : '' }}">
                        <i class="ti-home"></i>
                        <span> Dashboard </span>
                    </a>
                </li>

                @canany(['view-employee', 'view-interview'])
                    <li>
                        <a href="javascript:void(0);" class="waves-effect">
                            <i class="ti-id-badge"></i>
                            <span> Workforce
                                <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                            </span>
                        </a>
                        <ul class="submenu">
                            @can('view-employee')
                                <li>
                                    <a href="{{ route('admin.employees.index') }}"
                                        class="{{ request()->is('admin/employees') ? 'mm active' : '' }}">
                                        <i class="dripicons-user-group"></i> Employees
                                    </a>
                                </li>
                            @endcan
                            @can('view-interview')
                                <li>
                                    <a href="{{ route('admin.interviews.index') }}"
                                        class="{{ request()->is('admin/interviews*') ? 'mm active' : '' }}">
                                        <i class="ti-write"></i> Interviews
                                    </a>
                                </li>
                            @endcan
                            @can('manage-job-postings')
                                <li>
                                    <a href="{{ route('admin.job-postings.index') }}"
                                        class="{{ request()->is('admin/job-postings*') ? 'mm active' : '' }}">
                                        <i class="ti-briefcase"></i> Job Postings
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @can('view-team-attendance')
                    <li class="menu-title">Organization</li>
                    
                    @if(auth()->user()->hasRole(['admin', 'administrator']))
                        <li>
                            <a href="{{ route('admin.branches.index') }}"
                                class="waves-effect {{ request()->is('admin/branches*') ? 'mm active' : '' }}">
                                <i class="ti-map-alt"></i>
                                <span> Branches </span>
                            </a>
                        </li>
                    @endif

                    @can('view-department')
                        <li>
                            <a href="{{ route('admin.departments.index') }}"
                                class="waves-effect {{ request()->is('admin/departments*') ? 'mm active' : '' }}">
                                <i class="ti-layers"></i>
                                <span> Departments </span>
                            </a>
                        </li>
                    @endcan

                    @can('view-team')
                        <li>
                            <a href="{{ route('admin.teams.index') }}"
                                class="waves-effect {{ request()->is('admin/teams*') ? 'mm active' : '' }}">
                                <i class="ti-package"></i>
                                <span> Teams </span>
                            </a>
                        </li>
                    @endcan
                @endcan

                {{-- CRM modules hidden from HRM sidebar --}}
                {{-- @can('view-leads')
                    <li class="menu-title">CRM - Leads</li>
                    <li>
                        <a href="/admin/leads" class="waves-effect {{ request()->is('admin/leads*') ? 'mm active' : '' }}">
                            <i class="ti-target"></i> <span> Leads Management </span>
                        </a>
                    </li>
                @endcan

                @can('view-deals')
                    <li class="menu-title">CRM - Sales</li>
                    <li>
                        <a href="/admin/deals" class="waves-effect {{ request()->is('admin/deals*') ? 'mm active' : '' }}">
                            <i class="ti-briefcase"></i> <span> Deals & Pipeline </span>
                        </a>
                    </li>
                @endcan --}}

                <li class="menu-title">Management</li>

                @can('view-shift')
                    <li>
                        <a href="/admin/shift" class="waves-effect {{ request()->is('shift') ? 'mm active' : '' }}">
                            <i class="ti-time"></i> <span> Shifts </span>
                        </a>
                    </li>
                @endcan

                @can('view-attendance-logs')
                    <li>
                        <a href="/admin/attendance/logs"
                            class="waves-effect {{ request()->is('admin/attendance/logs') ? 'mm active' : '' }}">
                            <i class="mdi mdi-calendar-check"></i> <span> Attendance Logs </span>
                        </a>
                    </li>
                @endcan

                @can('view-attendance-sheet')
                    <li>
                        <a href="{{ route('admin.attendance.monthly') }}"
                            class="waves-effect {{ request()->is('admin/attendance-sheet') ? 'mm active' : '' }}">
                            <i class="dripicons-document"></i> <span> Attendance Sheet </span>
                        </a>
                    </li>
                @endcan

                @canany(['view-payroll', 'generate-payroll'])
                    <li>
                        <a href="javascript:void(0);" class="waves-effect {{ request()->is('admin/payroll*') ? 'mm-active' : '' }}">
                            <i class="ti-wallet"></i>
                            <span> Payroll
                                <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                            </span>
                        </a>
                        <ul class="submenu">
                            @can('view-payroll')
                                <li>
                                    <a href="{{ route('admin.payroll.index') }}"
                                        class="{{ request()->is('admin/payroll') ? 'active' : '' }}">
                                        Payroll History
                                    </a>
                                </li>
                            @endcan
                            @can('generate-payroll')
                                <li>
                                    <a href="{{ route('admin.payroll.generate') }}"
                                        class="{{ request()->is('admin/payroll/generate') ? 'active' : '' }}">
                                        Generate Payroll
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @can('view-late-arrivals')
                    <li>
                        <a href="/admin/late-arrivals"
                            class="waves-effect {{ request()->is('admin/late-arrivals') ? 'mm active' : '' }}">
                            <i class="mdi mdi-clock-alert-outline"></i>
                            <span>
                                @if(($lateArrivalsCount ?? 0) > 0)
                                    <span class="tw-badge-danger float-right">{{ $lateArrivalsCount }}</span>
                                @endif
                                Late Arrivals
                            </span>
                        </a>
                    </li>
                @endcan

                @can('view-half-days')
                    <li>
                        <a href="/admin/half-days"
                            class="waves-effect {{ request()->is('admin/half-days') ? 'mm active' : '' }}">
                            <i class="mdi mdi-calendar-remove"></i> <span> Manage Half Days </span>
                        </a>
                    </li>
                @endcan

                @can('view-breaks')
                    <li>
                        <a href="{{ route('admin.breaks.index') }}"
                            class="waves-effect {{ request()->is('admin/breaks') ? 'mm active' : '' }}">
                            <i class="mdi mdi-timetable"></i>
                            <span>
                                @if(($breakRequestsCount ?? 0) > 0)
                                    <span class="tw-badge-danger float-right" id="break-requests-count">{{ $breakRequestsCount }}</span>
                                @endif
                                Breaks & Requests
                            </span>
                        </a>
                    </li>
                @endcan

                @can('view-leaves')
                    <li>
                        <a href="/admin/leaves" class="waves-effect {{ request()->is('admin/leaves') ? 'mm active' : '' }}">
                            <i class="mdi mdi-airplane-takeoff"></i>
                            <span>
                                @if(($pendingLeavesCount ?? 0) > 0)
                                    <span class="tw-badge-danger float-right">{{ $pendingLeavesCount }}</span>
                                @endif
                                Leaves
                            </span>
                        </a>
                    </li>
                @endcan

                @can('view-compensatory-leaves')
                    <li>
                        <a href="{{ route('admin.compensatory_leaves.index') }}"
                            class="waves-effect {{ request()->is('admin/compensatory-leaves*') ? 'mm active' : '' }}">
                            <i class="mdi mdi-calendar-heart"></i>
                            <span>
                                @if(($pendingCplCount ?? 0) > 0)
                                    <span class="tw-badge-danger float-right">{{ $pendingCplCount }}</span>
                                @endif
                                Compensatory Leave
                            </span>
                        </a>
                    </li>
                @endcan

                @can('view-leave-cashouts')
                    <li>
                        <a href="{{ route('admin.leave_cashouts.index') }}"
                            class="waves-effect {{ request()->is('admin/leave-cashouts*') ? 'mm active' : '' }}">
                            <i class="mdi mdi-cash-multiple"></i> <span> Leave Encashment </span>
                        </a>
                    </li>
                @endcan

                @can('view-leave-adjustments')
                    <li>
                        <a href="{{ route('admin.leave_adjustments.index') }}"
                            class="waves-effect {{ request()->is('admin/leave-adjustments*') ? 'mm active' : '' }}">
                            <i class="mdi mdi-shield-check-outline"></i> <span> Leave Policy Audit </span>
                        </a>
                    </li>
                @endcan

                @can('view-holidays')
                    <li>
                        <a href="{{ route('admin.holidays.index') }}"
                            class="waves-effect {{ request()->is('admin/holidays*') ? 'mm active' : '' }}">
                            <i class="mdi mdi-calendar-star"></i> <span> Holidays </span>
                        </a>
                    </li>
                @endcan

                @can('view-activity-logs')
                    <li>
                        <a href="{{ route('admin.activity_logs.index') }}"
                            class="waves-effect {{ request()->is('admin/activity-logs') ? 'mm active' : '' }}">
                            <i class="mdi mdi-history"></i> <span> Activity Logs </span>
                        </a>
                    </li>
                @endcan

                @can('manage-app-settings')
                    <li>
                        <a href="/admin/app_settings"
                            class="waves-effect {{ request()->is('admin/app_settings') ? 'mm active' : '' }}">
                            <i class="ti-settings"></i> <span> App Settings </span>
                        </a>
                    </li>
                @endcan

                @canany(['view-performance-evaluation', 'manage-performance-evaluation'])
                    <li class="menu-title">Performance</li>
                    <li>
                        <a href="javascript:void(0);" class="waves-effect {{ request()->is('admin/performance*') ? 'mm-active' : '' }}">
                            <i class="mdi mdi-chart-bar"></i>
                            <span> Evaluations
                                <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                            </span>
                        </a>
                        <ul class="submenu">
                            @can('view-performance-evaluation')
                                <li>
                                    <a href="{{ route('admin.performance.evaluations.index') }}"
                                        class="{{ request()->is('admin/performance/evaluations*') ? 'active' : '' }}">
                                        <i class="mdi mdi-clipboard-check-outline"></i> Monthly Evaluations
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.performance.eotm.index') }}"
                                        class="{{ request()->is('admin/performance/employee-of-the-month*') ? 'active' : '' }}">
                                        <i class="mdi mdi-trophy-outline"></i> Employee of the Month
                                    </a>
                                </li>
                            @endcan
                            @can('manage-performance-evaluation')
                                <li>
                                    <a href="{{ route('admin.performance.settings.index') }}"
                                        class="{{ request()->is('admin/performance/settings*') ? 'active' : '' }}">
                                        <i class="ti-settings"></i> Evaluation Settings
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @can('manage-employee-settings')
                    <li class="menu-title">System Optimization</li>
                    <li>
                        <a href="{{ route('admin.settings.employee') }}"
                            class="waves-effect {{ request()->is('admin/settings/employee*') ? 'mm active' : '' }}">
                            <i class="ti-panel"></i> <span> Employee Settings </span>
                        </a>
                    </li>
                @endcan

                @if(auth()->user()->hasRole('admin') || auth()->user()->is_admin)
                    <li class="menu-title">System Security</li>

                    <li>
                        <a href="javascript:void(0);" class="waves-effect">
                            <i class="ti-lock"></i>
                            <span> Access Control
                                <span class="float-right menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                            </span>
                        </a>
                        <ul class="submenu">
                            <li>
                                <a href="{{ route('admin.roles.index') }}"
                                    class="{{ request()->is('admin/roles*') ? 'mm active' : '' }}">
                                    <i class="ti-shield"></i> Roles & Permissions
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.user_management.index') }}"
                                    class="{{ request()->is('admin/user-management*') ? 'mm active' : '' }}">
                                    <i class="ti-user"></i> Admin Management
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.permissions.index') }}"
                                    class="{{ request()->is('admin/permissions*') ? 'mm active' : '' }}">
                                    <i class="ti-key"></i> Permission Slugs
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<!-- Left Sidebar End -->