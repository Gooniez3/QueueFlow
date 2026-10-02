<?php

namespace App\Http\Middleware;

use App\Data\StaffMembershipData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureQueueFlowBusinessMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get('queueflow.auth');
        $businessId = (int) $request->route('businessId');

        $membership = collect($context['memberships'] ?? [])
            ->first(
                static fn (mixed $membership): bool => $membership instanceof StaffMembershipData
                    && $membership->belongsToBusiness($businessId),
            );

        if (! $membership instanceof StaffMembershipData) {
            return response(
                'You are not authorized to manage this business.',
                403,
            );
        }

        $request->attributes->set('queueflow.membership', $membership);

        return $next($request);
    }
}
