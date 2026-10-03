<?php

use App\Exceptions\GuestQueueOwnershipException;
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
        $customerError = static fn (
            string $title,
            string $message,
            int $status,
            string $backUrl,
            string $backLabel,
        ) => response()->view('errors.customer', [
            'title' => $title,
            'message' => $message,
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
        ], $status);

        $exceptions->dontReportWhen(
            fn (Throwable $exception): bool => $exception instanceof QueueFlowApiException && (
                (
                    request()->is('staff', 'staff/*')
                    && in_array(
                        $exception->status,
                        [401, 403, 404],
                        true,
                    )
                ) || (
                    request()->routeIs('home', 'businesses.show', 'branches.show', 'services.show')
                    && $exception->status === 404
                ) || (
                    request()->routeIs('queue-entries.store')
                    && in_array($exception->status, [400, 403, 404, 409], true)
                ) || (
                    request()->routeIs('queue-entries.show')
                    && in_array($exception->status, [403, 404], true)
                ) || (
                    request()->routeIs('queue-entries.cancel')
                    && in_array($exception->status, [403, 404, 409], true)
                )
            ),
        );

        $exceptions->dontReportWhen(
            fn (Throwable $exception): bool => $exception instanceof GuestQueueOwnershipException
                && request()->routeIs('queue-entries.show', 'queue-entries.cancel'),
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

        $exceptions->render(function (GuestQueueOwnershipException $exception, Request $request) use ($customerError) {
            if ($request->routeIs('queue-entries.show', 'queue-entries.cancel')) {
                return $customerError(
                    'Ticket unavailable',
                    'This ticket is not available in this browser/session.',
                    404,
                    route('tickets.show'),
                    'Back to My Tickets',
                );
            }

            if (! $request->routeIs('queue-entries.store')) {
                return null;
            }

            return $customerError(
                'Unable to join queue',
                'QueueFlow is temporarily unavailable. Please try again later.',
                503,
                route('home'),
                'Back to discovery',
            );
        });

        $exceptions->render(function (QueueFlowApiException $exception, Request $request) use ($customerError) {
            if ($request->routeIs('queue-entries.cancel')) {
                $ticketUrl = route('queue-entries.show', [
                    $request->route('queueId'),
                    $request->route('entryId'),
                ]);

                return match ($exception->status) {
                    403 => $customerError(
                        'Ticket could not be verified',
                        'We could not verify this ticket for this browser/session.',
                        403,
                        route('tickets.show'),
                        'Back to My Tickets',
                    ),
                    404 => $customerError(
                        'Ticket unavailable',
                        'This ticket is no longer available.',
                        404,
                        route('tickets.show'),
                        'Back to My Tickets',
                    ),
                    409 => $customerError(
                        'Cancellation unavailable',
                        'This ticket can no longer be cancelled. Refresh its status.',
                        409,
                        $ticketUrl,
                        'Refresh ticket status',
                    ),
                    default => $customerError(
                        'QueueFlow is temporarily unavailable',
                        'QueueFlow is temporarily unavailable. Please try again later.',
                        503,
                        $ticketUrl,
                        'Return to ticket',
                    ),
                };
            }

            if ($request->routeIs('queue-entries.show')) {
                return match ($exception->status) {
                    403 => $customerError(
                        'Ticket could not be verified',
                        'We could not verify this ticket for this browser/session.',
                        403,
                        route('tickets.show'),
                        'Back to My Tickets',
                    ),
                    404 => $customerError(
                        'Ticket unavailable',
                        'This ticket is no longer available.',
                        404,
                        route('tickets.show'),
                        'Back to My Tickets',
                    ),
                    default => $customerError(
                        'QueueFlow is temporarily unavailable',
                        'QueueFlow is temporarily unavailable. Please try again later.',
                        503,
                        route('tickets.show'),
                        'Back to My Tickets',
                    ),
                };
            }

            if (! $request->routeIs('home', 'businesses.show', 'branches.show', 'services.show')) {
                return null;
            }

            return match ($exception->status) {
                404 => $customerError(
                    'Page unavailable',
                    'The requested QueueFlow resource was not found.',
                    404,
                    route('home'),
                    'Back to discovery',
                ),
                default => $customerError(
                    'QueueFlow is temporarily unavailable',
                    'QueueFlow is temporarily unavailable. Please try again later.',
                    503,
                    route('home'),
                    'Back to discovery',
                ),
            };
        });

        $exceptions->render(function (QueueFlowApiException $exception, Request $request) use ($customerError) {
            if (! $request->routeIs('queue-entries.store')) {
                return null;
            }

            return match ($exception->status) {
                400 => $customerError(
                    'Unable to join queue',
                    'We could not join this queue. Please refresh and try again.',
                    400,
                    route('home'),
                    'Back to discovery',
                ),
                403 => $customerError(
                    'Queue request unavailable',
                    'This queue request is not permitted.',
                    403,
                    route('home'),
                    'Back to discovery',
                ),
                404 => $customerError(
                    'Queue unavailable',
                    'The requested QueueFlow resource was not found.',
                    404,
                    route('home'),
                    'Back to discovery',
                ),
                409 => $customerError(
                    'Queue no longer available',
                    'The queue is no longer accepting joins. Please refresh and try again.',
                    409,
                    route('home'),
                    'Back to discovery',
                ),
                default => $customerError(
                    'QueueFlow is temporarily unavailable',
                    'QueueFlow is temporarily unavailable. Please try again later.',
                    503,
                    route('home'),
                    'Back to discovery',
                ),
            };
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
