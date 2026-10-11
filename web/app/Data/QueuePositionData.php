<?php

namespace App\Data;

final readonly class QueuePositionData
{
    public function __construct(
        public int $entryId,
        public int $queueId,
        public string $publicCode,
        public int $serviceId,
        public int $ticketSequence,
        public string $ticketNumber,
        public string $status,
        public int $peopleAhead,
        public int $estimatedWaitMinutes,
        public ?string $businessName = null,
        public ?string $branchName = null,
        public ?string $serviceName = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entryId: (int) $data['entryId'],
            queueId: (int) $data['queueId'],
            publicCode: (string) $data['publicCode'],
            serviceId: (int) $data['serviceId'],
            ticketSequence: (int) $data['ticketSequence'],
            ticketNumber: (string) $data['ticketNumber'],
            status: (string) $data['status'],
            peopleAhead: (int) $data['peopleAhead'],
            estimatedWaitMinutes: (int) $data['estimatedWaitMinutes'],
            businessName: isset($data['businessName']) ? (string) $data['businessName'] : null,
            branchName: isset($data['branchName']) ? (string) $data['branchName'] : null,
            serviceName: isset($data['serviceName']) ? (string) $data['serviceName'] : null,
        );
    }
}
