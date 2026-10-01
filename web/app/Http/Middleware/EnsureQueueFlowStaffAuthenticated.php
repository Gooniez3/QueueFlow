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
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 401) {
                return redirect()
                    ->route('staff.login')
                    ->with('error', 'Your session has expired. Please sign in again.');
            }

            throw $exception;
        }

        if ($context === null) {
            return redirect()
                ->route('staff.login')
                ->with('error', 'Please sign in to continue.');
        }

        $request->attributes->set('queueflow.auth', $context);

        return $next($request);
    }
}
