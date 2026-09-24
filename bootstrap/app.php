<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\ApplySelectedBusinessLocale;
use App\Http\Middleware\ApplyTenantLocale;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureUserIsActive::class,
            AddSecurityHeaders::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'tenant' => ResolveTenantContext::class,
            'tenant.locale' => ApplyTenantLocale::class,
            'selected.locale' => ApplySelectedBusinessLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $renderAccessDenied = function (Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors.access-denied', [
                'message' => $exception->getMessage() ?: 'You do not have permission to access this page.',
            ], 403);
        };

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($renderAccessDenied) {
            return $renderAccessDenied($exception, $request);
        });

        $exceptions->render(function (HttpException $exception, Request $request) use ($renderAccessDenied) {
            return $exception->getStatusCode() === 403 ? $renderAccessDenied($exception, $request) : null;
        });
    })->create();
