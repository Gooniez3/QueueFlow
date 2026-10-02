<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class BranchData
{
    public function __construct(
        public int $id,
        public int $businessId,
        public string $name,
        public string $address,
        public ?float $latitude,
        public ?float $longitude,
        public string $timezone,
        public CarbonImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            businessId: (int) $data['businessId'],
            name: (string) $data['name'],
            address: (string) $data['address'],
            latitude: isset($data['latitude'])
                ? (float) $data['latitude']
                : null,
            longitude: isset($data['longitude'])
                ? (float) $data['longitude']
                : null,
            timezone: (string) $data['timezone'],
            createdAt: CarbonImmutable::parse((string) $data['createdAt']),
        );
    }
}
