<?php

namespace Tests\Unit\Data;

use App\Data\AuthUserData;
use Tests\TestCase;

class AuthUserDataTest extends TestCase
{
    public function test_maps_auth_user_response_with_phone(): void
    {
        $user = AuthUserData::fromArray([
            'id' => 42,
            'email' => 'alex@example.com',
            'firstName' => 'Alex',
            'lastName' => 'Rivera',
            'phone' => '+65 6123 4567',
        ]);

        $this->assertSame(42, $user->id);
        $this->assertSame('alex@example.com', $user->email);
        $this->assertSame('Alex', $user->firstName);
        $this->assertSame('Rivera', $user->lastName);
        $this->assertSame('+65 6123 4567', $user->phone);
    }

    public function test_maps_null_phone(): void
    {
        $user = AuthUserData::fromArray([
            'id' => 43,
            'email' => 'sam@example.com',
            'firstName' => 'Sam',
            'lastName' => 'Tan',
            'phone' => null,
        ]);

        $this->assertNull($user->phone);
    }
}
