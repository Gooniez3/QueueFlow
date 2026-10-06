<?php

namespace App\Http\Controllers\Staff;

use App\Data\BranchData;
use App\Data\StaffDashboardQueueData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\QueueEntryMutationRequest;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowQueueService;
use App\Services\StaffCatalogService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveQueueController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowQueueService $queueService,
        private readonly StaffCatalogService $catalogService,
    ) {}

    public function gateway(Request $request): View
    {
        $authContext = $request->attributes->get('queueflow.auth');
        $businesses = $this->catalogService->accessibleBusinesses(
            $authContext['memberships'],
            includeBusinessesWithoutBranches: false,
        );

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
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->pauseQueue($queueId),
            'Queue paused successfully.',
        );
    }

    public function resume(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->resumeQueue($queueId),
            'Queue resumed successfully.',
        );
    }

    public function close(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->closeQueue($queueId),
            'Queue closed successfully.',
        );
    }

    public function reopen(int $businessId, int $branchId, int $queueId): RedirectResponse
    {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->reopenQueue($queueId),
            'Queue reopened successfully.',
        );
    }

    public function callNext(
        QueueEntryMutationRequest $request,
        int $businessId,
        int $branchId,
        int $queueId,
    ): RedirectResponse {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->callNextQueueEntry(
                $queueId,
                $request->idempotencyKey(),
            ),
            'Next ticket called successfully.',
        );
    }

    public function startServing(
        QueueEntryMutationRequest $request,
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->startServingQueueEntry(
                $queueId,
                $entryId,
                $request->idempotencyKey(),
            ),
            'Service started successfully.',
        );
    }

    public function recall(
        QueueEntryMutationRequest $request,
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->recallQueueEntry(
                $queueId,
                $entryId,
                $request->idempotencyKey(),
            ),
            'Ticket recalled successfully.',
        );
    }

    public function skip(
        QueueEntryMutationRequest $request,
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->skipQueueEntry(
                $queueId,
                $entryId,
                $request->idempotencyKey(),
            ),
            'Ticket skipped successfully.',
        );
    }

    public function complete(
        QueueEntryMutationRequest $request,
        int $businessId,
        int $branchId,
        int $queueId,
        int $entryId,
    ): RedirectResponse {
        return $this->performMutation(
            $businessId,
            $branchId,
            $queueId,
            fn () => $this->queueService->completeQueueEntry(
                $queueId,
                $entryId,
                $request->idempotencyKey(),
            ),
            'Ticket completed successfully.',
        );
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

    private function performMutation(
        int $businessId,
        int $branchId,
        int $queueId,
        Closure $operation,
        string $successMessage,
    ): RedirectResponse {
        try {
            $this->ensureQueueBelongsToBranch($businessId, $branchId, $queueId);
            $operation();
        } catch (QueueFlowApiException $exception) {
            if ($exception->status !== 409) {
                throw $exception;
            }

            return redirect()
                ->route('staff.live-queues.index', [
                    'businessId' => $businessId,
                    'branchId' => $branchId,
                    'queue' => $queueId,
                ])
                ->with('error', 'The queue changed before this action completed. The latest state has been refreshed.');
        }

        return $this->redirectToQueue(
            $businessId,
            $branchId,
            $queueId,
            $successMessage,
        );
    }
}
