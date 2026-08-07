<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

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

        $user = Auth::user();
        
        try {
            if ($user->hasPermissionTo('access-admin-panel') || $user->is_admin) {
                return redirect()->intended(route('admin.dashboard'));
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            if ($user->is_admin || $user->hasAnyRole('admin', 'Super Admin')) {
                return redirect()->intended(route('admin.dashboard'));
            }
        }

        return redirect()->intended(route('employee.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
