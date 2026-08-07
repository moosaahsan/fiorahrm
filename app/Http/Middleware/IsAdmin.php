<?php

// app/Http/Middleware/IsAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Permission-based: Any role with 'access-admin-panel' permission can enter.
        // try-catch prevents crash if permission hasn't been seeded yet.
        try {
            if ($user->hasPermissionTo('access-admin-panel') || $user->is_admin) {
                return $next($request);
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Fallback: if permission not seeded yet, allow known admin roles
            if ($user->is_admin || $user->hasAnyRole('admin', 'Super Admin')) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized');
    }
}

