<?php

namespace App\Services;

use App\Data\QueueData;
use App\Data\QueueStaffEntryData;

class QueueFlowQueueService
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function createQueue(
        int $businessId,
        int $branchId,
        ?int $serviceId,
        string $name,
        string $ticketPrefix,
    ): QueueData {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueData => $this->apiClient->createQueue(
                $businessId,
                $branchId,
                $token,
                $serviceId,
                $name,
                $ticketPrefix,
            ),
        );
    }

    public function callNextQueueEntry(int $queueId): QueueStaffEntryData
    {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueStaffEntryData => $this->apiClient->callNextQueueEntry(
                $queueId,
                $token,
            ),
        );
    }

    public function startServingQueueEntry(
        int $queueId,
        int $entryId,
    ): QueueStaffEntryData {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueStaffEntryData => $this->apiClient->startServingQueueEntry(
                $queueId,
                $entryId,
                $token,
            ),
        );
    }

    public function completeQueueEntry(
        int $queueId,
        int $entryId,
    ): QueueStaffEntryData {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueStaffEntryData => $this->apiClient->completeQueueEntry(
                $queueId,
                $entryId,
                $token,
            ),
        );
    }

    public function skipQueueEntry(
        int $queueId,
        int $entryId,
    ): QueueStaffEntryData {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueStaffEntryData => $this->apiClient->skipQueueEntry(
                $queueId,
                $entryId,
                $token,
            ),
        );
    }

    public function pauseQueue(int $queueId): QueueData
    {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueData => $this->apiClient->pauseQueue(
                $queueId,
                $token,
            ),
        );
    }

    public function resumeQueue(int $queueId): QueueData
    {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueData => $this->apiClient->resumeQueue(
                $queueId,
                $token,
            ),
        );
    }

    public function closeQueue(int $queueId): QueueData
    {
        return $this->authService->authenticatedRequest(
            fn (#[\SensitiveParameter] string $token): QueueData => $this->apiClient->closeQueue(
                $queueId,
                $token,
            ),
        );
    }
}
