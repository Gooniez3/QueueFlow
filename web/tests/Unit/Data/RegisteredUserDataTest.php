<?php

namespace Tests\Unit\Data;

use App\Data\RegisteredUserData;
use Tests\TestCase;

class RegisteredUserDataTest extends TestCase
{
    public function test_maps_register_response_with_null_phone(): void
    {
        $user = RegisteredUserData::fromArray([
            'id' => 42,
            'email' => 'alex@example.com',
            'firstName' => 'Alex',
            'lastName' => 'Rivera',
            'phone' => null,
        ]);

        $this->assertSame(42, $user->id);
        $this->assertSame('alex@example.com', $user->email);
        $this->assertSame('Alex', $user->firstName);
        $this->assertSame('Rivera', $user->lastName);
        $this->assertNull($user->phone);
    }
}
