<?php

namespace App\Data;

final readonly class StaffDashboardQueueData
{
    /**
     * @param  list<QueueStaffEntryData>  $waiting
     */
    public function __construct(
        public int $queueId,
        public string $name,
        public string $status,
        public string $ticketPrefix,
        public ?StaffDashboardServiceData $service,
        public StaffDashboardCountsData $counts,
        public ?QueueStaffEntryData $serving,
        public ?QueueStaffEntryData $called,
        public array $waiting,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            queueId: (int) $data['queueId'],
            name: (string) $data['name'],
            status: (string) $data['status'],
            ticketPrefix: (string) $data['ticketPrefix'],
            service: isset($data['service'])
                ? StaffDashboardServiceData::fromArray($data['service'])
                : null,
            counts: StaffDashboardCountsData::fromArray($data['counts']),
            serving: isset($data['serving'])
                ? QueueStaffEntryData::fromArray($data['serving'])
                : null,
            called: isset($data['called'])
                ? QueueStaffEntryData::fromArray($data['called'])
                : null,
            waiting: array_map(
                static fn (array $entry): QueueStaffEntryData => QueueStaffEntryData::fromArray($entry),
                $data['waiting'],
            ),
        );
    }
}
