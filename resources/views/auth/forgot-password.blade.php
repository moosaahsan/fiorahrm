@extends('layouts.auth')

@section('title', 'Reset Password - HRM')

@section('content')
    <div class="bg-indigo-600 -mx-8 -mt-8 md:-mx-10 md:-mt-10 mb-8 p-8 text-white text-center">
        <h2 class="text-2xl font-bold">Reset Your Password</h2>
        <p class="text-indigo-100 mt-2 text-sm">
            {{ __('Enter your email and we\'ll send you a link to get back into your account.') }}
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-6">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                placeholder="e.g. employee@company.com"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm">

            @error('email')
                <p class="text-red-500 text-xs mt-2 italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col gap-4">
            <button type="submit"
                class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition-colors shadow-lg uppercase tracking-wide">
                {{ __('Send Reset Link') }}
            </button>

            <a href="{{ route('login') }}"
                class="text-center text-sm text-gray-500 hover:text-indigo-600 font-medium transition-colors">
                &larr; Back to Login
            </a>
        </div>
    </form>
@endsection
