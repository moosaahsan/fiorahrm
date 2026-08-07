<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<div class="sidebar shadow-lg" id="sidebarNav">
    <div class="brand">
        <!-- <img src="{{ $dashboardData['app_settings']['app_logo'] ?? '/images/logo.png' }}" alt="Logo"> -->
        <h5>{{ $dashboardData['app_settings']['company_name'] ?? 'All Star Tech' }}</h5>
    </div>

    <div class="nav-container">
        <div class="menu-title">Main Dashboard</div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->is('employee/dashboard*') ? 'active' : '' }}"
                href="{{ route('dashboard') }}">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
                <span class="badge">2</span>
            </a>

            @can('view-attendance')
            <a class="nav-link {{ request()->is('employee/breaks*') ? 'active' : '' }}" href="/employee/breaks">
                <i class="fas fa-mug-hot"></i>
                <span>Breaks</span>
            </a>
            <a class="nav-link {{ request()->is('employee/attendance/logs*') ? 'active' : '' }}"
                href="{{ route('employee.attendance.logs') }}">
                <i class="fas fa-fingerprint"></i>
                <span>My Logs</span>
            </a>
            @endcan
            
            <a class="nav-link {{ request()->is('employee/timesheet*') ? 'active' : '' }}"
                href="{{ route('employee.timesheet') }}">
                <i class="fas fa-calendar-alt"></i>
                <span>Timesheet</span>
            </a>
            <a class="nav-link {{ request()->is('employee/report*') ? 'active' : '' }}"
                href="{{ route('employee.report') }}">
                <i class="fas fa-chart-pie"></i>
                <span>Monthly Report</span>
            </a>
            <a class="nav-link {{ request()->is('employee/attendance/late-history*') ? 'active' : '' }}"
                href="{{ route('employee.attendance.late_history') }}">
                <i class="fas fa-clock"></i>
                <span>Late History</span>
            </a>

            @can('view-attendance')
            <div class="menu-title">Self Service</div>
            <a class="nav-link {{ request()->is('employee/leave/request*') ? 'active' : '' }}"
                href="{{ route('employee.leave.index') }}">
                <i class="fas fa-paper-plane"></i>
                <span>Leave Request</span>
            </a>
            
            <a class="nav-link {{ request()->is('employee/performance*') ? 'active' : '' }}"
                href="{{ route('employee.performance.index') }}">
                <i class="fas fa-chart-line"></i>
                <span>Performance</span>
            </a>
            @endcan
        </nav>
    </div>
</div>

<!-- <div class="topbar shadow-sm" id="topbarNav">
    <button class="toggle-btn" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </button>
    <div style="font-weight: 600; color: var(--text-slate);">
        Welcome, {{ auth()->user()->name ?? 'User' }}
    </div>
</div> -->

<script>
    function toggleMenu() {
        const sidebar = document.getElementById('sidebarNav');
        // const topbar = document.getElementById('topbarNav');
        const content = document.querySelector('.content-page'); // Make sure your main content has this class

        if (window.innerWidth > 991) {
            sidebar.classList.toggle('collapsed');
            // topbar.classList.toggle('expanded');
            if (content) content.classList.toggle('expanded');
        } else {
            sidebar.classList.toggle('show');
        }
    }

    // Close sidebar on mobile when clicking outside (Optional)
    window.onclick = function (event) {
        if (window.innerWidth <= 991) {
            const sidebar = document.getElementById('sidebarNav');
            if (event.target == sidebar) {
                sidebar.classList.remove('show');
            }
        }
    }
</script>