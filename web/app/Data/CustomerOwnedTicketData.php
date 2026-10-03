<?php

namespace App\Data;

final readonly class CustomerOwnedTicketData
{
    private const array ACTIVE_STATUSES = ['WAITING', 'CALLED', 'SERVING'];

    private const array TERMINAL_STATUSES = ['COMPLETED', 'CANCELLED', 'SKIPPED'];

    public function __construct(
        public GuestQueueOwnershipData $ownership,
        public QueuePositionData $position,
    ) {}

    public function isActive(): bool
    {
        return in_array($this->position->status, self::ACTIVE_STATUSES, true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->position->status, self::TERMINAL_STATUSES, true);
    }
}
