<?php

namespace App\Data;

final readonly class CustomerQueueTicketData
{
    public function __construct(
        public int $businessId,
        public int $branchId,
        public int $serviceId,
        public int $queueId,
        public int $entryId,
        public string $ticketNumber,
        public string $status,
    ) {}
}
