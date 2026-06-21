<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
// use App\Http\Middleware\RoleMiddleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
          'auth' => \App\Http\Middleware\Authenticate::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'redirect.if.authenticated' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'prevent.back.history' => \App\Http\Middleware\PreventBackHistory::class,
    ]);
    $middleware->validateCsrfTokens(except: [
        'logout',
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh and try again.',
                ], 419);
            }

            return redirect()
                ->route('auth.login')
                ->with('error', 'Your session expired. Please sign in again.');
        });
    })->create();
