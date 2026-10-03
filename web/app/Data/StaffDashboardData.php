<?php

namespace App\Data;

final readonly class StaffDashboardData
{
    /**
     * @param  list<StaffDashboardQueueData>  $queues
     */
    public function __construct(
        public int $businessId,
        public int $branchId,
        public string $businessDate,
        public array $queues,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            businessId: (int) $data['businessId'],
            branchId: (int) $data['branchId'],
            businessDate: (string) $data['businessDate'],
            queues: array_map(
                static fn (array $queue): StaffDashboardQueueData => StaffDashboardQueueData::fromArray($queue),
                $data['queues'],
            ),
        );
    }
}
