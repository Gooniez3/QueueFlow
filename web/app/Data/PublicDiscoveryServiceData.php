<?php

namespace App\Data;

final readonly class PublicDiscoveryServiceData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $durationMinutes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['serviceId'],
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            durationMinutes: (int) $data['durationMinutes'],
        );
    }
}
