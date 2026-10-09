<?php

namespace App\Data;

final readonly class PublicDiscoveryData
{
    /**
     * @param  list<PublicDiscoveryServiceData>  $services
     */
    public function __construct(
        public int $businessId,
        public string $businessName,
        public ?string $businessDescription,
        public ?string $category,
        public int $branchId,
        public string $branchName,
        public string $address,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $distanceKm,
        public array $services,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            businessId: (int) $data['businessId'],
            businessName: (string) $data['businessName'],
            businessDescription: isset($data['businessDescription']) ? (string) $data['businessDescription'] : null,
            category: isset($data['category']) ? (string) $data['category'] : null,
            branchId: (int) $data['branchId'],
            branchName: (string) $data['branchName'],
            address: (string) $data['address'],
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            distanceKm: isset($data['distanceKm']) ? (float) $data['distanceKm'] : null,
            services: collect($data['services'] ?? [])
                ->map(fn (array $service): PublicDiscoveryServiceData => PublicDiscoveryServiceData::fromArray($service))
                ->all(),
        );
    }
}
