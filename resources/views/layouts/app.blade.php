<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <title>Attendance Management System</title>

    <meta content="Admin Dashboard" name="description" />
    <meta content="Themesbrand" name="author" />

    {{-- Custom Head (CSS, Favicon, etc) --}}
    @include('layouts.head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <!-- Critical Dependencies (jQuery first) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

    <!-- Date Range Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- CropperJS CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet" />

    <!-- CropperJS JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Vite & Pusher Setup (CDN Fallback) -->
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script>
        window.pusherConfig = {
            key: "{{ config('broadcasting.connections.pusher.key') ?: env('PUSHER_APP_KEY') }}",
            cluster: "{{ config('broadcasting.connections.pusher.options.cluster') ?: env('PUSHER_APP_CLUSTER') }}"
        };

        if (window.pusherConfig.key) {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: window.pusherConfig.key,
                cluster: window.pusherConfig.cluster || 'ap2',
                forceTLS: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }
            });
            console.log('Laravel Echo initialized via CDN with Private Channel Support');
        }
    </script>
    {{-- @vite(['resources/js/app.js']) --}}
    @yield('css')
</head>

<body class="legacy-layout-page">
    @include('includes.partials.break-timer-script');
    <div id="wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        {{-- Optional Page Heading --}}
        @isset($header)
            <header class="bg-white shadow">
                <div class="container py-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    @include('layouts.settings')
                    @yield('content')
                </div>
            </div>
            @include('layouts.footer')
        </div>
        <!-- Toast Container -->
        <div id="toastContainer" class="position-fixed top-0 right-0 p-3" style="z-index: 9999;"></div>

        @include('layouts.footer-script')

        {{-- Core JS from CDN --}}
        {{--
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>--}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

        {{-- Page-specific JS --}}
        @stack('scripts')
    </div>
</body>

</html>