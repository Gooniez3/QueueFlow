<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\StaffDashboardQueueData;
use App\Data\StaffMembershipData;
use App\Http\Controllers\Controller;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowQueueService;
use Illuminate\Http\RedirectResponse;
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

    public function pause(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->pauseQueue($queueId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Queue paused successfully.',
        );
    }

    public function resume(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->resumeQueue($queueId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Queue resumed successfully.',
        );
    }

    public function close(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->closeQueue($queueId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Queue closed successfully.',
        );
    }

    public function callNext(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->callNextQueueEntry($queueId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Next ticket called successfully.',
        );
    }

    public function startServing(
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->startServingQueueEntry($queueId, $entryId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Service started successfully.',
        );
    }

    public function recall(
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->recallQueueEntry($queueId, $entryId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Ticket recalled successfully.',
        );
    }

    public function skip(
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->skipQueueEntry($queueId, $entryId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Ticket skipped successfully.',
        );
    }

    public function complete(
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);

        $this->queueService->completeQueueEntry($queueId, $entryId);

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            'Ticket completed successfully.',
        );
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

    private function ensureQueueBelongsToBranch(int $businessId, int $branchId, int $queueId): void
    {
        $branch = $this->apiClient->branch($businessId, $branchId);

        $this->ensureBranchBelongsToBusiness($branch, $businessId);

        $dashboard = $this->queueService->staffDashboard($businessId, $branchId);

        abort_if(
            $dashboard->businessId !== $businessId || $dashboard->branchId !== $branchId,
            404,
            'The requested queue dashboard was not found for this branch.',
        );

        $queueExists = collect($dashboard->queues)->contains(
            static fn (StaffDashboardQueueData $queue): bool => $queue->queueId === $queueId,
        );

        abort_unless(
            $queueExists,
            404,
            'The requested queue was not found for this branch.',
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

    private function redirectToQueue(
        int $businessId,
        int $branchId,
        int $queueId,
        string $message,
    ): RedirectResponse {
        return redirect()
            ->route('staff.live-queues.index', [
                'businessId' => $businessId,
                'branchId' => $branchId,
                'queue' => $queueId,
            ])
            ->with('status', $message);
    }
}