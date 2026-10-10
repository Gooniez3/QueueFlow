<?php

namespace App\Data;

final readonly class QueueEntryQrVerificationData
{
    public function __construct(
        public int $entryId,
        public int $queueId,
        public int $businessId,
        public int $branchId,
        public string $branchName,
        public int $serviceId,
        public string $serviceName,
        public string $ticketNumber,
        public string $status,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            entryId: (int) $data['entryId'],
            queueId: (int) $data['queueId'],
            businessId: (int) $data['businessId'],
            branchId: (int) $data['branchId'],
            branchName: (string) $data['branchName'],
            serviceId: (int) $data['serviceId'],
            serviceName: (string) $data['serviceName'],
            ticketNumber: (string) $data['ticketNumber'],
            status: (string) $data['status'],
        );
    }
}
