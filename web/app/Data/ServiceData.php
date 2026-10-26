<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class ServiceData
{
    public function __construct(
        public int $id,
        public int $branchId,
        public string $name,
        public ?string $description,
        public int $durationMinutes,
        public bool $active,
        public CarbonImmutable $createdAt,
        public string $partySizeMode = 'DISABLED',
        public int $maxPartySize = 20,
        public string $queueEntryCode = '',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            branchId: (int) $data['branchId'],
            name: (string) $data['name'],
            description: isset($data['description'])
                ? (string) $data['description']
                : null,
            durationMinutes: (int) $data['durationMinutes'],
            active: (bool) $data['active'],
            createdAt: CarbonImmutable::parse((string) $data['createdAt']),
            partySizeMode: isset($data['partySizeMode'])
                ? (string) $data['partySizeMode']
                : 'DISABLED',
            maxPartySize: isset($data['maxPartySize'])
                ? (int) $data['maxPartySize']
                : 20,
            queueEntryCode: isset($data['queueEntryCode'])
                ? (string) $data['queueEntryCode']
                : '',
        );
    }
}
