<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

// File-based cache and compiled views are needed before Laravel can finish
// booting. Keep this safeguard here in addition to the container entrypoint so
// alternate web/worker commands cannot start with missing runtime directories.
$basePath = dirname(__DIR__);
foreach ([
    $basePath.'/storage/framework/cache/data',
    $basePath.'/storage/framework/sessions',
    $basePath.'/storage/framework/testing',
    $basePath.'/storage/framework/views',
    $basePath.'/bootstrap/cache',
] as $runtimeDirectory) {
    if (! is_dir($runtimeDirectory)) {
        @mkdir($runtimeDirectory, 0775, true);
    }
}

// use App\Http\Middleware\RoleMiddleware;
return Application::configure(basePath: $basePath)
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
        'voice-calls/anonymous/session',
    ]);
    $middleware->web(append: [
        \App\Http\Middleware\EnforcePilotRollout::class,
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

        // Keep browser and API responses safe: diagnostic details stay in server logs.
        $exceptions->render(function (\Throwable $exception, $request) {
            if ($exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            // Some servers convert TokenMismatchException into an HTTP 419
            // before render callbacks run. Never let that become the generic
            // 400 page; always recover through a fresh login session.
            if ($status === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your session expired. Refresh and try again.',
                    ], 419);
                }

                return redirect()
                    ->route('auth.login')
                    ->with('error', 'Your session expired. The login form has been refreshed; please try again.');
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $status >= 500
                        ? 'The service is temporarily unavailable. Please try again shortly.'
                        : 'The requested resource is unavailable.',
                ], $status);
            }

            // Retain the real HTTP code while selecting one of the safe visual fallbacks.
            $viewStatus = in_array($status, [400, 404, 500, 503], true)
                ? $status
                : ($status >= 500 ? 500 : 400);

            return response()->view("errors.{$viewStatus}", [], $status);
        });
    })->create();
