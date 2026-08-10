<!-- App favicon -->
<link rel="shortcut icon" href="{{ URL::asset('assets/images/fiora-favicon.png') }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}" />

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">


{{-- Veltrix theme (expects BS4 grid/components — compat bundle loaded via Vite below) --}}
<link href="{{ URL::asset('assets/css/metismenu.min.css') }}" rel="stylesheet" type="text/css">
<link href="{{ URL::asset('assets/css/icons.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/style.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/css/custom-style.css') }}" rel="stylesheet" type="text/css" />

{{-- Tailwind admin + BS4 structural compat (replaces assets/css/bootstrap.min.css) --}}
@vite(['resources/css/admin.css', 'resources/css/bs4-compat.css'])

{{-- <link href="{{ URL::asset('plugins/sweet-alert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css"> --}}
<link href="{{ asset('plugins/sweetalert.min.css') }}" rel="stylesheet">
<!-- Table css -->
<link href="{{ URL::asset('plugins/RWD-Table-Patterns/dist/css/rwd-table.min.css') }}" rel="stylesheet" type="text/css" media="screen">
{{-- DataTables + Select2 skins live in admin.css (SaaS Tailwind) — no bootstrap4 theme CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<script src="{{ URL::asset('assets/js/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'tw-btn-success',
            cancelButton: 'tw-btn-danger'
        },
        buttonsStyling: true
    })
</script>


{{------ Fire Base Notification Enabale--- --}}
@stack('styles')
