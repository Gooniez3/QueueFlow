<?php

namespace App\Http\Controllers;

use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\ServiceData;
use App\Exceptions\QueueFlowApiException;
use App\Http\Requests\StoreCustomerQueueEntryRequest;
use App\Services\GuestQueueJoinAttemptStore;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use Illuminate\Http\RedirectResponse;

class CustomerQueueEntryController extends Controller
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowCustomerQueueService $customerQueueService,
        private readonly GuestQueueJoinAttemptStore $joinAttemptStore,
    ) {}

    public function store(
        StoreCustomerQueueEntryRequest $request,
        int $queueId,
    ): RedirectResponse {
        $businessId = $request->integer('businessId');
        $branchId = $request->integer('branchId');
        $serviceId = $request->integer('serviceId');

        $business = $this->apiClient->business($businessId);
        $branch = $this->apiClient->branch($businessId, $branchId);
        $service = $this->apiClient->service($businessId, $branchId, $serviceId);

        $this->ensureNestedResourcesMatch(
            $business,
            $branch,
            $service,
            $businessId,
            $branchId,
            $serviceId,
        );

        $queue = $this->customerQueueService->applicableQueue(
            $businessId,
            $branchId,
            $serviceId,
        );

        abort_if(
            ! $service->active || $queue->status !== 'OPEN' || $queue->id !== $queueId,
            409,
            'The queue is no longer accepting joins. Please refresh and try again.',
        );

        $idempotencyKey = $this->joinAttemptStore->idempotencyKey(
            $businessId,
            $branchId,
            $serviceId,
            $queueId,
        );

        try {
            $ticket = $this->customerQueueService->joinGuest(
                $businessId,
                $branchId,
                $serviceId,
                $queue,
                $idempotencyKey,
            );
        } catch (QueueFlowApiException $exception) {
            if (in_array($exception->status, [400, 403, 404, 409], true)) {
                $this->joinAttemptStore->forget(
                    $businessId,
                    $branchId,
                    $serviceId,
                    $queueId,
                );
            }

            throw $exception;
        }

        $this->joinAttemptStore->forget(
            $businessId,
            $branchId,
            $serviceId,
            $queueId,
        );

        return redirect()
            ->route('queue-entries.show', [$ticket->queueId, $ticket->entryId])
            ->with('status', 'You have joined the queue.');
    }

    private function ensureNestedResourcesMatch(
        BusinessData $business,
        BranchData $branch,
        ServiceData $service,
        int $businessId,
        int $branchId,
        int $serviceId,
    ): void {
        abort_if(
            $business->id !== $businessId,
            404,
            'The requested business was not found.',
        );

        abort_if(
            $branch->id !== $branchId || $branch->businessId !== $businessId,
            404,
            'The requested branch was not found for this business.',
        );

        abort_if(
            $service->id !== $serviceId || $service->branchId !== $branchId,
            404,
            'The requested service was not found for this branch.',
        );
    }
}
