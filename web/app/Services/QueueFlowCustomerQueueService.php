<?php

namespace App\Services;

use App\Data\CustomerQueueTicketData;
use App\Data\GuestQueueOwnershipData;
use App\Data\QueuePositionData;
use App\Data\TodayQueueData;
use App\Exceptions\GuestQueueOwnershipException;
use App\Exceptions\QueueFlowApiException;
use Illuminate\Support\Str;

class QueueFlowCustomerQueueService
{
    public function __construct(
        private readonly QueueFlowApiClient $apiClient,
        private readonly GuestQueueOwnershipStore $ownershipStore,
    ) {}

    public function applicableQueue(
        int $businessId,
        int $branchId,
        int $serviceId,
    ): TodayQueueData {
        try {
            return $this->apiClient->todayQueue(
                $businessId,
                $branchId,
                $serviceId,
            );
        } catch (QueueFlowApiException $exception) {
            if (! $this->isMissingTodayQueue($exception)) {
                throw $exception;
            }
        }

        return $this->apiClient->todayQueue(
            $businessId,
            $branchId,
        );
    }

    public function joinGuest(
        int $businessId,
        int $branchId,
        int $serviceId,
        TodayQueueData $queue,
        #[\SensitiveParameter] ?string $idempotencyKey = null,
    ): CustomerQueueTicketData {
        $joinKey = $idempotencyKey ?? (string) Str::uuid();
        $entry = $this->apiClient->joinQueue(
            $queue->id,
            $serviceId,
            token: null,
            idempotencyKey: $joinKey,
        );

        if ($entry->guestToken === null || $entry->guestToken === '') {
            throw GuestQueueOwnershipException::missingGuestToken();
        }

        $this->ownershipStore->store(new GuestQueueOwnershipData(
            businessId: $businessId,
            branchId: $branchId,
            serviceId: $serviceId,
            queueId: $entry->queueId,
            entryId: $entry->id,
            ticketNumber: $entry->ticketNumber,
            guestToken: $entry->guestToken,
        ));

        return $this->customerTicket(
            $businessId,
            $branchId,
            $serviceId,
            $entry->queueId,
            $entry->id,
            $entry->ticketNumber,
            $entry->status,
        );
    }

    public function position(int $queueId, int $entryId): QueuePositionData
    {
        $ownership = $this->ownershipStore->find($queueId, $entryId);

        if ($ownership === null) {
            throw GuestQueueOwnershipException::missing();
        }

        return $this->apiClient->queuePosition(
            $queueId,
            $entryId,
            token: null,
            guestToken: $ownership->guestToken(),
        );
    }

    public function cancel(int $queueId, int $entryId): CustomerQueueTicketData
    {
        $ownership = $this->ownershipStore->find($queueId, $entryId);

        if ($ownership === null) {
            throw GuestQueueOwnershipException::missing();
        }

        $entry = $this->apiClient->cancelQueueEntry(
            $queueId,
            $entryId,
            token: null,
            guestToken: $ownership->guestToken(),
        );

        return $this->customerTicket(
            $ownership->businessId,
            $ownership->branchId,
            $entry->serviceId,
            $entry->queueId,
            $entry->id,
            $entry->ticketNumber,
            $entry->status,
        );
    }

    private function customerTicket(
        int $businessId,
        int $branchId,
        int $serviceId,
        int $queueId,
        int $entryId,
        string $ticketNumber,
        string $status,
    ): CustomerQueueTicketData {
        return new CustomerQueueTicketData(
            businessId: $businessId,
            branchId: $branchId,
            serviceId: $serviceId,
            queueId: $queueId,
            entryId: $entryId,
            ticketNumber: $ticketNumber,
            status: $status,
        );
    }

    private function isMissingTodayQueue(QueueFlowApiException $exception): bool
    {
        return $exception->status === 404
            && $exception->getMessage() === 'Queue not found for today';
    }
}
