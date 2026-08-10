<!-- Top Bar Start -->
<div class="topbar">

    <!-- LOGO -->
    <div class="topbar-left">
        <a href="/" class="logo">
            <span>
                <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="max-height: 58px; max-width: 100%; width: auto; object-fit: contain;">
            </span>
            <i>
                <img src="{{ asset('assets/images/fiora-favicon.png') }}" alt="favicon" style="max-height: 40px; max-width: 100%; width: auto; object-fit: contain;">
            </i>
        </a>
    </div>

    <nav class="navbar-custom">
        <ul class="navbar-right d-flex list-inline float-right mb-0">
            <li class="dropdown notification-list d-flex align-items-center">
                <div class="header-date-box">
                    <i class="far fa-calendar-alt"></i>
                    <span class="clockStyle">{{ date("d M Y") }}</span>
                </div>
            </li>
            @if(auth()->user()->hasRole(['admin', 'hr', 'administrator']))
                <li class="dropdown notification-list d-flex align-items-center ml-3">
                    <div class="branch-switcher-container">
                        <select class="branch-select-premium" id="global-branch-switcher">
                            <option value="">Global Overview (All)</option>
                            @php
                                $branches = \App\Models\Branch::accessible()->where('is_active', true)->orderBy('name')->get();
                                $activeBranchId = session('active_branch_id');
                            @endphp
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $activeBranchId == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </li>
            @endif

            <!-- Full screen -->
            <li class="dropdown notification-list d-none d-md-block">
                <a class="nav-link waves-effect" href="#" id="btn-fullscreen">
                    <i class="mdi mdi-fullscreen noti-icon"></i>
                </a>
            </li>

            <!-- User Dropdown -->
            <li class="dropdown notification-list">
                @php
                    use Illuminate\Support\Facades\Storage;

                    $user = auth()->user();
                    $isAdmin = $user->hasAnyRole(['admin', 'hr', 'administrator']);
                    $fallbackAvatar = "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=6366f1&color=fff&bold=true&format=svg";

                    $profilePic = ($user->profile_pic && Storage::disk('public')->exists($user->profile_pic))
                        ? Storage::disk('public')->url($user->profile_pic)
                        : $fallbackAvatar;

                    $linkedEmployee = $user->employee ?? \App\Models\Employee::where('user_id', $user->id)->first();
                    $employeeProfileUrl = $linkedEmployee ? route('admin.employees.show', $linkedEmployee->id) : route('admin.profile.edit');
                @endphp

                <div class="dropdown notification-list nav-pro-img d-inline-flex align-items-center">
                    <a href="{{ $employeeProfileUrl }}" target="_blank" title="View Profile" class="d-inline-flex align-items-center mr-1" style="height: 70px; text-decoration: none;">
                        <img src="{{ $profilePic }}" alt="user" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;"
                            onerror="this.onerror=null; this.src='{{ $fallbackAvatar }}';">
                    </a>

                    <a class="dropdown-toggle nav-link arrow-none waves-effect nav-user pl-0 d-inline-flex align-items-center" data-toggle="dropdown" href="#"
                        role="button" aria-haspopup="false" aria-expanded="false" style="height: 70px;">
                        <span class="d-none d-md-inline-block ml-1">{{ $user->name }} <i
                                class="mdi mdi-chevron-down"></i></span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                        <!-- Profile -->
                        <a class="dropdown-item" href="{{ route('admin.profile.edit') }}">
                            <i class="mdi mdi-account-circle m-r-5"></i> Profile
                        </a>

                        <!-- Settings or Policies -->
                        @if($isAdmin)
                            <a class="dropdown-item d-block" href="{{ route('admin.settings') }}">
                                <span class="badge badge-success float-right">11</span>
                                <i class="mdi mdi-settings m-r-5"></i> Settings
                            </a>
                        @else
                            <a class="dropdown-item d-block" href="#"><i class="mdi mdi-book-edit m-r-5"></i> Company
                                Policies</a>
                            <a class="dropdown-item d-block" href="#"><i class="mdi mdi-book-edit m-r-5"></i> Leave
                                Policies</a>
                        @endif

                        <!-- Lock screen -->
                        <a class="dropdown-item" href="#"><i class="mdi mdi-lock-open-outline m-r-5"></i> Lock
                            screen</a>

                        <div class="dropdown-divider"></div>

                        <!-- Logout -->
                        <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="mdi mdi-power text-danger"></i> Logout
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </li>
        </ul>

        <ul class="list-inline menu-left mb-0">
            <li class="float-left">
                <button class="button-menu-mobile open-left waves-effect">
                    <i class="mdi mdi-menu"></i>
                </button>
            </li>
        </ul>
    </nav>
</div>
<script>
    $(document).ready(function () {
        // Manual Toggle Fix
        $('.button-menu-mobile').on('click', function (e) {
            e.preventDefault();
            $('body').toggleClass('enlarged');
        });

        $('#global-branch-switcher').on('change', function () {
            const branchId = $(this).val();

            // Show loading state
            $(this).prop('disabled', true).css('opacity', '0.6');

            $.ajax({
                url: "{{ route('admin.branches.set_context') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    branch_id: branchId
                },
                success: function (response) {
                    if (response.success) {
                        window.location.reload(); // Reload to apply scope
                    }
                },
                error: function () {
                    alert('Failed to switch branch context.');
                    $(this).prop('disabled', false).css('opacity', '1');
                }
            });
        });

        // Real-Time Notifications Integration
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};

        function initAdminEcho() {
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo not ready in Admin Header, retrying...');
                setTimeout(initAdminEcho, 500);
                return;
            }

            if (isAdmin) {
                console.log('Admin Listener: Subscribing to private-admin-notifications...');
                window.Echo.private('admin-notifications')
                    .listen('.leave.applied', (event) => {
                        console.log('ADMIN EVENT RECEIVED:', event);
                        const leave = event.leave;
                        const employeeName = event.employeeName;

                        toastr.info(
                            `<strong>${employeeName}</strong> has applied for <strong>${leave.leave_type.replace('_', ' ').toUpperCase()}</strong> leave.`,
                            'New Leave Application',
                            {
                                timeOut: 0, // Stay until closed
                                closeButton: true,
                                progressBar: true,
                                onclick: function () {
                                    window.location.href = '{{ route("admin.leaves.index") }}';
                                }
                            }
                        );

                        // Update notification badge if it exists
                        $('.notif-badge').show();
                        // Play a subtle sound if possible? Toastr doesn't support sound out of the box, but we'll stick to visual.
                    });
            }
        }

        initAdminEcho();
    });
</script>