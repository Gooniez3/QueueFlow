<?php

namespace App\Http\Middleware;

use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureQueueFlowStaffAuthenticated
{
    public function __construct(
        private QueueFlowAuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->authService->hasAuthSession()) {
            return redirect()
                ->route('staff.login')
                ->with('error', 'Please sign in to continue.');
        }

        try {
            $context = $this->authService->currentUser();

            if ($context === null) {
                return redirect()
                    ->route('staff.login')
                    ->with('error', 'Please sign in to continue.');
            }

            $request->attributes->set('queueflow.auth', $context);

            return $next($request);
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 401) {
                return redirect()
                    ->route('staff.login')
                    ->with('error', 'Your session has expired. Please sign in again.');
            }

            if ($exception->status === 403) {
                return response(
                    'You are not authorized to access this staff resource.',
                    403,
                );
            }

            if ($exception->status === 404) {
                return response(
                    'The requested QueueFlow resource was not found.',
                    404,
                );
            }

            report($exception);

            return response(
                'QueueFlow is temporarily unavailable. Please try again later.',
                503,
            );
        }
    }
}
