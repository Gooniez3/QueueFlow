<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\StaffDashboardQueueData;
use App\Data\StaffMembershipData;
use App\Http\Controllers\Controller;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowQueueService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveQueueController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowQueueService $queueService,
    ) {}

    public function gateway(Request $request): View
    {
        $authContext = $request->attributes->get('queueflow.auth');
        $membershipsByBusinessId = [];

        foreach ($authContext['memberships'] as $membership) {
            $membershipsByBusinessId[$membership->businessId][] = $membership;
        }

        $businesses = [];

        foreach ($membershipsByBusinessId as $businessId => $memberships) {
            $branches = $this->accessibleBranches(
                $this->apiClient->branches($businessId),
                $memberships,
            );

            if ($branches === []) {
                continue;
            }

            $businesses[] = [
                'business' => $this->apiClient->business($businessId),
                'branches' => $branches,
            ];
        }

        return view('staff.queues.gateway', [
            'authContext' => $authContext,
            'businesses' => $businesses,
        ]);
    }

    public function index(Request $request, int $businessId, int $branchId): View
    {
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        $dashboard = $this->queueService->staffDashboard($businessId, $branchId);

        abort_if(
            $dashboard->businessId !== $businessId || $dashboard->branchId !== $branchId,
            404,
            'The requested queue dashboard was not found for this branch.',
        );

        $selectedQueue = $this->selectedQueue($request, $dashboard->queues);

        return view('staff.queues.index', [
            'authContext' => $request->attributes->get('queueflow.auth'),
            'business' => $this->apiClient->business($businessId),
            'branch' => $branch,
            'dashboard' => $dashboard,
            'selectedQueue' => $selectedQueue,
        ]);
    }

    /**
     * @param  list<BranchData>  $branches
     * @param  list<StaffMembershipData>  $memberships
     * @return list<BranchData>
     */
    private function accessibleBranches(array $branches, array $memberships): array
    {
        $hasBusinessWideMembership = collect($memberships)
            ->contains(static fn (StaffMembershipData $membership): bool => $membership->branchId === null);

        if ($hasBusinessWideMembership) {
            return $branches;
        }

        $accessibleBranchIds = array_map(
            static fn (StaffMembershipData $membership): ?int => $membership->branchId,
            $memberships,
        );

        return array_values(array_filter(
            $branches,
            static fn (BranchData $branch): bool => in_array($branch->id, $accessibleBranchIds, true),
        ));
    }

    private function ensureBranchBelongsToBusiness(BranchData $branch, int $businessId): void
    {
        abort_if(
            $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );
    }

    /**
     * @param  list<StaffDashboardQueueData>  $queues
     */
    private function selectedQueue(Request $request, array $queues): ?StaffDashboardQueueData
    {
        $requestedQueueId = $request->query('queue');

        if ($requestedQueueId === null) {
            return $queues[0] ?? null;
        }

        abort_unless(
            is_string($requestedQueueId)
                && ctype_digit($requestedQueueId)
                && (int) $requestedQueueId > 0,
            404,
        );

        $selectedQueue = collect($queues)->first(
            static fn (StaffDashboardQueueData $queue): bool => $queue->queueId === (int) $requestedQueueId,
        );

        abort_unless($selectedQueue instanceof StaffDashboardQueueData, 404);

        return $selectedQueue;
    }
}
