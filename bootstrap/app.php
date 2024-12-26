<?php

use App\Http\Middleware\AddUlbIdToResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->alias([
        //    'append-ulb' => \App\Http\Middleware\AddUlbIdToResponse::class,
        // ]);

        // $middleware->groups([
        //    'append-ulb' => [
        //       \App\Http\Middleware\AddUlbIdToResponse::class,
        //    ]
        // ]);

        $middleware->alias([
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'append-ulb' => \App\Http\Middleware\AddUlbIdToResponse::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'tc' => \App\Http\Middleware\TCMiddleware::class,
            'office' => \App\Http\Middleware\OfficeMiddleware::class,
            'force-json' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        // $middleware->append(AddUlbIdToResponse::class);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Check if it's an authentication error and the request expects JSON
            if ($e instanceof AuthenticationException && $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'errors' => 'Authentication is required to access this resource.',
                ], 401);
            }
        });
    })->create();
