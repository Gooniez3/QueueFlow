<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class BusinessData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public CarbonImmutable $createdAt,
        public ?string $category = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            description: isset($data['description'])
                ? (string) $data['description']
                : null,
            createdAt: CarbonImmutable::parse($data['createdAt']),
            category: isset($data['category']) ? (string) $data['category'] : null,
        );
    }
}
