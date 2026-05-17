<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            // THIS SHOULD BE 'auth.login' NOT 'login'
            return route('auth.login');  // ← CHANGE THIS IF IT SAYS 'login'
        }
          return null;
    }
}