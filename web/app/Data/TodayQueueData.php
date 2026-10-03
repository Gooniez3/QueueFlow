<?php

namespace App\Data;

final readonly class TodayQueueData
{
    public function __construct(
        public int $id,
        public int $branchId,
        public ?int $serviceId,
        public string $name,
        public string $businessDate,
        public string $ticketPrefix,
        public string $status,
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
            status: (string) $data['status'],
        );
    }
}
