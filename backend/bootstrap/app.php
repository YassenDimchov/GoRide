<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Middleware\EnsureUserNotSuspended;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'not_suspended' => EnsureUserNotSuspended::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (\Throwable $e, Request $request) {

            if ($request->is('api/*') || $request->expectsJson()) {

                if ($e instanceof AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json(['message' => 'Resource not found.'], 404);
                }

                if ($e instanceof NotFoundHttpException) {
                    $msg = $e->getMessage();

                    if (str_contains($msg, 'No query results for model')) {
                        return response()->json(['message' => 'Resource not found.'], 404);
                    }

                    return response()->json(['message' => 'Endpoint not found.'], 404);
                }

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], 422);
                }

                return response()->json([
                    'message' => 'Server error.',
                    
                ], 500);
            }

            return null;
        });
    })
    ->create();
