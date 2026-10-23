<?php

use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'device' => AuthenticateDevice::class,
        ]);

        $middleware->encryptCookies(except: [
            AuthenticateDevice::COOKIE,
        ]);

        $middleware->validateCsrfTokens(except: [
            'device/telemetry',
            'device/heartbeat',
            'device/questionnaire',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['token']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson() || ($request->ajax() && ! $request->isMethod('GET')),
        );
    })->create();
