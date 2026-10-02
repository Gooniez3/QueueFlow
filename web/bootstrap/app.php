<?php

use App\Exceptions\QueueFlowApiException;
use App\Http\Middleware\EnsureQueueFlowBusinessMembership;
use App\Http\Middleware\EnsureQueueFlowStaffAuthenticated;
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
        $middleware->alias([
            'queueflow.business.member' => EnsureQueueFlowBusinessMembership::class,
            'queueflow.staff.auth' => EnsureQueueFlowStaffAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportWhen(
            fn (QueueFlowApiException $exception): bool => (
                request()->is('staff', 'staff/*')
                && in_array(
                    $exception->status,
                    [401, 403, 404],
                    true,
                )
            ) || (
                request()->routeIs('home', 'businesses.show', 'branches.show', 'services.show')
                && $exception->status === 404
            ),
        );

        $exceptions->render(function (QueueFlowApiException $exception, Request $request) {
            if (! $request->is('staff', 'staff/*')) {
                return null;
            }

            return match ($exception->status) {
                401 => redirect()
                    ->route('staff.login')
                    ->with('error', 'Your session has expired. Please sign in again.'),
                403 => response(
                    'You are not authorized to access this staff resource.',
                    403,
                ),
                404 => response(
                    'The requested QueueFlow resource was not found.',
                    404,
                ),
                default => response(
                    'QueueFlow is temporarily unavailable. Please try again later.',
                    503,
                ),
            };
        });

        $exceptions->render(function (QueueFlowApiException $exception, Request $request) {
            if (! $request->routeIs('home', 'businesses.show', 'branches.show', 'services.show')) {
                return null;
            }

            return match ($exception->status) {
                404 => response(
                    'The requested QueueFlow resource was not found.',
                    404,
                ),
                default => response(
                    'QueueFlow is temporarily unavailable. Please try again later.',
                    503,
                ),
            };
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
