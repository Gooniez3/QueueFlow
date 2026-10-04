<?php

namespace App\Data;

final readonly class StaffDashboardCountsData
{
    public function __construct(
        public int $waiting,
        public int $called,
        public int $serving,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            waiting: (int) $data['waiting'],
            called: (int) $data['called'],
            serving: (int) $data['serving'],
        );
    }
}
