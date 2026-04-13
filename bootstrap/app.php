<?php

use App\Http\Middleware\ActivityLogMiddleware;
use App\Http\Middleware\DynamicBaseUrlMiddleware;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TouchRealtimeVersion;
use App\Http\Middleware\VendorPortalMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            DynamicBaseUrlMiddleware::class,
        ]);

        $middleware->web(append: [
            TouchRealtimeVersion::class,
            AuthenticateSession::class,
            ActivityLogMiddleware::class,
            EnsurePasswordIsChanged::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'vendor.portal' => VendorPortalMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedException|AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'code' => 'FORBIDDEN',
                    'message' => 'You are not authorized to perform this action.',
                ], 403);
            }
        });

        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'code' => 'NOT_FOUND',
                    'message' => 'Requested resource was not found.',
                ], 404);
            }
        });
    })->create();
