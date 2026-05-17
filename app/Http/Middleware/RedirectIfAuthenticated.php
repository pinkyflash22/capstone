<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $id = Auth::user()->user_id;
            $userRole = $user->role->role;

            // if (!$user->phone_verified_at) {
            //         return response()->json([
            //             'message' => 'Please verify your phone first'
            //         ], 403);
            //     }
            switch ($userRole) {
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'station admin':
                    return redirect()->route('station-admin.dashboard');
                case 'chief':
                    return redirect()->route('station-admin.dashboard');
                case 'firefighter':
                    return redirect()->route('firefighter.dashboard');
                case 'citizen':
                    return redirect()->route('citizen.dashboard');
                case 'barangay':
                    return redirect()->route('barangay.dashboard');
                case 'barangay captain':
                    return redirect()->route('barangay.dashboard');
                default:
                    return redirect()->route('citizen.dashboard');
            }
        }

        return $next($request);
    }
}
