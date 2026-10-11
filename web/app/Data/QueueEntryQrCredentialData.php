<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class QueueEntryQrCredentialData
{
    public function __construct(
        #[\SensitiveParameter] public string $credential,
        public CarbonImmutable $expiresAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            credential: (string) $data['credential'],
            expiresAt: CarbonImmutable::parse((string) $data['expiresAt']),
        );
    }
}
