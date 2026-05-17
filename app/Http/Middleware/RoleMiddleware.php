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
        // user not logged in
        if (!Auth::check()) {
            return redirect()->route('auth.login');  
        }

        // $thisRole = Role::all();
        $user = Auth::user();

        // get role name from relationships
        $userRole = $user->role?->role;
    //  $accountId = Auth::user()->account->account_id;
        
        // check if role allowed
        if (!$userRole || !in_array($userRole, $roles)) {
            abort(403, 'Unauthorized Assess');
        }

        return $next($request);
    }
}