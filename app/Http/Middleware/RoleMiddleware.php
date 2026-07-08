<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('auth.login');  
        }

        $user = Auth::user();
        $userRole = Role::roleNameForId((int) ($user->role_id ?? 0));
        $allowedRoles = array_map(fn ($role) => strtolower((string) $role), $roles);
        
        if (!$userRole || !in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Unauthorized Assess');
        }

        return $next($request);
    }
}
