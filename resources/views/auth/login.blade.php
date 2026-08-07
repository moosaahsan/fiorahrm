@extends('layouts.auth')

@section('title', 'HRM Login')

@section('auth-aside')
    <h1 class="text-4xl font-bold mb-4">HRM System</h1>
    <p class="text-indigo-100">Manage your employees, payroll, and attendance in one place.</p>
    <div class="mt-8">
        <span class="inline-block w-12 h-1 bg-white rounded"></span>
    </div>
@endsection

@section('content')
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome Back</h2>
    <p class="text-gray-500 mb-8">Please enter your details.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-4 py-3 mt-1 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            @error('email')
                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required
                class="w-full px-4 py-3 mt-1 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            @error('password')
                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600">
                <span class="ml-2">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
            @endif
        </div>

        <button type="submit"
            class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors shadow-lg">
            LOG IN
        </button>
    </form>
@endsection
