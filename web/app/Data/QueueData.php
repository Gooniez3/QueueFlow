<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class QueueData
{
    public function __construct(
        public int $id,
        public int $branchId,
        public ?int $serviceId,
        public string $name,
        public string $businessDate,
        public string $ticketPrefix,
        public int $nextTicketSequence,
        public string $status,
        public CarbonImmutable $openedAt,
        public ?CarbonImmutable $closedAt,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            branchId: (int) $data['branchId'],
            serviceId: isset($data['serviceId'])
                ? (int) $data['serviceId']
                : null,
            name: (string) $data['name'],
            businessDate: (string) $data['businessDate'],
            ticketPrefix: (string) $data['ticketPrefix'],
            nextTicketSequence: (int) $data['nextTicketSequence'],
            status: (string) $data['status'],
            openedAt: CarbonImmutable::parse((string) $data['openedAt']),
            closedAt: isset($data['closedAt'])
                ? CarbonImmutable::parse((string) $data['closedAt'])
                : null,
            createdAt: CarbonImmutable::parse((string) $data['createdAt']),
            updatedAt: CarbonImmutable::parse((string) $data['updatedAt']),
        );
    }
}
