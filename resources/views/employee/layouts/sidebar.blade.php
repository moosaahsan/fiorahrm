<!-- ========== Left Sidebar Start ========== -->
<div class="left side-menu">
    <div class="slimscroll-menu" id="remove-scroll">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">My Space</li>

                <li>
                    <a href="{{ route('employee.dashboard') }}"
                        class="waves-effect {{ request()->is('employee/dashboard') ? 'mm active' : '' }}">
                        <i class="ti-home"></i>
                        <span> Dashboard </span>
                    </a>
                </li>
                
                <li class="menu-title">Self Service & Logs</li>

                <li>
                    <a href="{{ route('employee.attendance.logs') }}"
                        class="waves-effect {{ request()->is('employee/attendance/logs') ? 'mm active' : '' }}">
                        <i class="mdi mdi-calendar-check"></i> <span> Attendance Logs </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('employee.timesheet') }}"
                        class="waves-effect {{ request()->is('employee/timesheet') ? 'mm active' : '' }}">
                        <i class="dripicons-document"></i> <span> Attendance Sheet </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('employee.attendance.late_history') }}"
                        class="waves-effect {{ request()->is('employee/attendance/late-history') ? 'mm active' : '' }}">
                        <i class="mdi mdi-clock-alert-outline"></i> <span> Late Arrivals </span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('employee.break.index') }}"
                        class="waves-effect {{ request()->is('employee/breaks') ? 'mm active' : '' }}">
                        <i class="mdi mdi-timetable"></i>
                        <span> Breaks Log</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('employee.leave.index') }}" class="waves-effect {{ request()->is('employee/leave/request') ? 'mm active' : '' }}">
                        <i class="mdi mdi-airplane-takeoff"></i>
                        <span> My Leaves </span>
                    </a>
                </li>

                @can('view-own-performance')
                <li>
                    <a href="{{ route('employee.performance.index') }}" class="waves-effect {{ request()->is('employee/performance*') ? 'mm active' : '' }}">
                        <i class="mdi mdi-clipboard-check-outline"></i>
                        <span> Evaluations </span>
                    </a>
                </li>
                @endcan

            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
<!-- Left Sidebar End -->