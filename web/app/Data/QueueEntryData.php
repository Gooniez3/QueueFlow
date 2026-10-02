<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class QueueEntryData
{
    public function __construct(
        public int $id,
        public int $queueId,
        public int $serviceId,
        public ?int $userId,
        public int $ticketSequence,
        public string $ticketNumber,
        public string $status,
        public CarbonImmutable $joinedAt,
        #[\SensitiveParameter] public ?string $guestToken,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(#[\SensitiveParameter] array $data): self
    {
        return new self(
            id: (int) $data['id'],
            queueId: (int) $data['queueId'],
            serviceId: (int) $data['serviceId'],
            userId: isset($data['userId'])
                ? (int) $data['userId']
                : null,
            ticketSequence: (int) $data['ticketSequence'],
            ticketNumber: (string) $data['ticketNumber'],
            status: (string) $data['status'],
            joinedAt: CarbonImmutable::parse((string) $data['joinedAt']),
            guestToken: isset($data['guestToken'])
                ? (string) $data['guestToken']
                : null,
        );
    }
}
