<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'HRM'))</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex items-center justify-center p-6">
        @hasSection('auth-aside')
            <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row">
                <div class="md:w-1/2 bg-indigo-600 p-10 text-white flex flex-col justify-center">
                    @yield('auth-aside')
                </div>
                <div class="md:w-1/2 p-8 md:p-10">
                    @yield('content')
                </div>
            </div>
        @else
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="p-8 md:p-10">
                    @yield('content')
                </div>
            </div>
        @endif
    </div>
</body>

</html>
