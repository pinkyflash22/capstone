<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('auth.login');
        }

        $user = Auth::user();
        
        // Check if user has role relationship
        if (!$user->role) {
            abort(403, 'Unauthorized access. User role not found.');
        }
        
        $userRole = strtolower(trim((string) ($user->role->role ?? '')));

        foreach ($roles as $role) {
            if (strtolower(trim((string) $role)) === $userRole) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized apse.');
    }
}
