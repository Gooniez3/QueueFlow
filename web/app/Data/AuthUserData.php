<?php

namespace App\Data;

final readonly class AuthUserData
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $phone,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            email: (string) $data['email'],
            firstName: (string) $data['firstName'],
            lastName: (string) $data['lastName'],
            phone: isset($data['phone'])
                ? (string) $data['phone']
                : null,
        );
    }
}
