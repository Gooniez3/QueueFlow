<?php

namespace App\Data;

final readonly class PublicQueueResolveData
{
    public function __construct(
        public string $publicCode,
        public int $businessId,
        public string $businessName,
        public int $branchId,
        public string $branchName,
        public ?int $serviceId,
        public ?string $serviceName,
        public int $queueId,
        public string $queueName,
        public string $queueStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            publicCode: (string) $data['publicCode'],
            businessId: (int) $data['businessId'],
            businessName: (string) $data['businessName'],
            branchId: (int) $data['branchId'],
            branchName: (string) $data['branchName'],
            serviceId: isset($data['serviceId'])
                ? (int) $data['serviceId']
                : null,
            serviceName: isset($data['serviceName'])
                ? (string) $data['serviceName']
                : null,
            queueId: (int) $data['queueId'],
            queueName: (string) $data['queueName'],
            queueStatus: (string) $data['queueStatus'],
        );
    }
}
