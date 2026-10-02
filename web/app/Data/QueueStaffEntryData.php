<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class QueueStaffEntryData
{
    public function __construct(
        public int $entryId,
        public int $queueId,
        public int $serviceId,
        public ?int $userId,
        public ?int $counterId,
        public int $ticketSequence,
        public string $ticketNumber,
        public string $status,
        public CarbonImmutable $joinedAt,
        public ?CarbonImmutable $calledAt,
        public ?CarbonImmutable $servingAt,
        public ?CarbonImmutable $completedAt,
        public ?CarbonImmutable $cancelledAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entryId: (int) $data['entryId'],
            queueId: (int) $data['queueId'],
            serviceId: (int) $data['serviceId'],
            userId: isset($data['userId'])
                ? (int) $data['userId']
                : null,
            counterId: isset($data['counterId'])
                ? (int) $data['counterId']
                : null,
            ticketSequence: (int) $data['ticketSequence'],
            ticketNumber: (string) $data['ticketNumber'],
            status: (string) $data['status'],
            joinedAt: CarbonImmutable::parse((string) $data['joinedAt']),
            calledAt: isset($data['calledAt'])
                ? CarbonImmutable::parse((string) $data['calledAt'])
                : null,
            servingAt: isset($data['servingAt'])
                ? CarbonImmutable::parse((string) $data['servingAt'])
                : null,
            completedAt: isset($data['completedAt'])
                ? CarbonImmutable::parse((string) $data['completedAt'])
                : null,
            cancelledAt: isset($data['cancelledAt'])
                ? CarbonImmutable::parse((string) $data['cancelledAt'])
                : null,
        );
    }
}
