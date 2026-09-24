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
        
        $userRole = $this->normalizeRole($user->role->role ?? null);

        foreach ($roles as $role) {
            if ($this->normalizeRole($role) === $userRole) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized apse.');
    }

    private function normalizeRole(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        $role = strtolower(trim(preg_replace('/\s+/', ' ', $role)));

        return match ($role) {
            'stationadmin', 'station-admin' => 'stationadmin',
            default => $role,
        };
    }
}
