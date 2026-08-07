<?php

// app/Http/Middleware/IsEmployee.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsEmployee
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->hasAnyRole('employee', 'admin', 'manager', 'team-lead', 'supervisor', 'hr')) {
            return $next($request);
        }

        abort(403, 'Unauthorized');
    }
}


