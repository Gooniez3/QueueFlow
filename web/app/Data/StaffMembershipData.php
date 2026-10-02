<?php

namespace App\Data;

final readonly class StaffMembershipData
{
    public function __construct(
        public int $businessId,
        public ?int $branchId,
        public string $role,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            businessId: (int) $data['businessId'],
            branchId: isset($data['branchId'])
                ? (int) $data['branchId']
                : null,
            role: (string) $data['role'],
        );
    }

    public function belongsToBusiness(int $businessId): bool
    {
        return $this->businessId === $businessId;
    }

    public function belongsToBranch(int $branchId): bool
    {
        return $this->branchId === $branchId;
    }
}
