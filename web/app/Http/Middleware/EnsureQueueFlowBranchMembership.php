<?php

namespace App\Http\Middleware;

use App\Data\StaffMembershipData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureQueueFlowBranchMembership
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = (int) $request->route('businessId');
        $branchId = (int) $request->route('branchId');
        $context = $request->attributes->get('queueflow.auth', []);

        $hasAccess = collect($context['memberships'] ?? [])
            ->contains(static fn (mixed $membership): bool => $membership instanceof StaffMembershipData
                && $membership->belongsToBusiness($businessId)
                && ($membership->branchId === null || $membership->branchId === $branchId)
            );

        if (! $hasAccess) {
            return response(
                'You are not authorized to access this branch.',
                403,
            );
        }

        return $next($request);
    }
}
