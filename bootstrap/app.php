<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CloudflareWebhookAuth;
use App\Http\Middleware\RequirePaymentMethod;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust reverse proxies (e.g. Cloudflare / Nginx) so Laravel correctly detects HTTPS
        // via X-Forwarded-* headers and doesn't generate http:// URLs (Mixed Content).
        $middleware->trustProxies(at: '*');

        // If session expires / user is logged out, always redirect protected pages to login
        // instead of showing a 404.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Register Spatie permissions middleware aliases (used in routes as: role:Admin, permission:..., etc).
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'cloudflare.webhook' => CloudflareWebhookAuth::class,
            'require.payment' => RequirePaymentMethod::class,
        ]);

        // Apply payment requirement check globally to web routes
        $middleware->web(append: [
            RequirePaymentMethod::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
