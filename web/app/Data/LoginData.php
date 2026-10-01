<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class LoginData
{
    /**
     * @param  list<StaffMembershipData>  $memberships
     */
    public function __construct(
        public string $token,
        public string $tokenType,
        public CarbonImmutable $expiresAt,
        public AuthUserData $user,
        public array $memberships,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token: (string) $data['token'],
            tokenType: (string) $data['tokenType'],
            expiresAt: CarbonImmutable::parse((string) $data['expiresAt']),
            user: AuthUserData::fromArray($data['user']),
            memberships: array_map(
                static fn (array $membership): StaffMembershipData => StaffMembershipData::fromArray($membership),
                $data['memberships'],
            ),
        );
    }
}
