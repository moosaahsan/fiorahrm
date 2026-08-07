<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); // Breeze default
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            reactivateExpiredSuspensions();

            $user = Auth::user();
            $blockReason = $user->employee ? getEmployeeAccessBlockReason($user->employee) : null;
            if ($blockReason) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => $blockReason,
                ]);
            }

            $request->session()->regenerate();

            $user = Auth::user()->load('roles');

            try {
                $redirect = $user->hasPermissionTo('access-admin-panel') ? route('admin.dashboard') : route('employee.dashboard');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                $redirect = ($user->is_admin || $user->hasAnyRole('admin', 'Super Admin')) ? route('admin.dashboard') : route('employee.dashboard');
            }
            return redirect()->intended($redirect);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
