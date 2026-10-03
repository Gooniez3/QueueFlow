<?php

namespace App\Data;

final readonly class StaffDashboardServiceData
{
    public function __construct(
        public int $id,
        public string $name,
        public int $durationMinutes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            durationMinutes: (int) $data['durationMinutes'],
        );
    }
}
