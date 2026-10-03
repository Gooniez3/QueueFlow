<?php

namespace App\Data;

final readonly class GuestQueueOwnershipData
{
    public function __construct(
        public int $businessId,
        public int $branchId,
        public ?int $serviceId,
        public int $queueId,
        public int $entryId,
        public string $ticketNumber,
        #[\SensitiveParameter] private string $guestToken,
    ) {}

    public function guestToken(): string
    {
        return $this->guestToken;
    }
}
