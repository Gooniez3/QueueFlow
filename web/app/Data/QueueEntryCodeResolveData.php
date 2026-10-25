<?php

namespace App\Data;

final readonly class QueueEntryCodeResolveData
{
    public function __construct(
        public string $entryCode,
        public int $businessId,
        public int $branchId,
        public int $serviceId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entryCode: (string) $data['entryCode'],
            businessId: (int) $data['businessId'],
            branchId: (int) $data['branchId'],
            serviceId: (int) $data['serviceId'],
        );
    }
}
