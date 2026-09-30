<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // This is an API-only app with no web "login" route, so the
        // framework's default guest-redirect (route('login')) would crash
        // with a RouteNotFoundException for any unauthenticated request that
        // doesn't explicitly ask for JSON. Every real request from the
        // frontend does, but there's no reason to leave that landmine for
        // anything else that hits the API — just never redirect.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Without this, an expired/invalid/blacklisted token falls through
        // to Laravel's default exception rendering as a raw 500 instead of a
        // clean 401 — which the frontend's axios interceptor (and its
        // refresh-and-retry logic) specifically checks for. JWTException is
        // the common base of TokenExpiredException/TokenInvalidException/
        // TokenBlacklistedException, so one handler covers all of them.
        $exceptions->render(function (JWTException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Your session has expired. Please log in again.'], 401);
            }
        });
    })->create();
