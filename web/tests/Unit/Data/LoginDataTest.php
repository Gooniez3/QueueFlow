<?php

namespace Tests\Unit\Data;

use App\Data\AuthUserData;
use App\Data\LoginData;
use App\Data\StaffMembershipData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LoginDataTest extends TestCase
{
    public function test_maps_login_response_and_preserves_expiry_offset(): void
    {
        $login = LoginData::fromArray([
            'token' => 'opaque-test-token',
            'tokenType' => 'Bearer',
            'expiresAt' => '2030-04-15T10:30:00+05:45',
            'user' => [
                'id' => 42,
                'email' => 'alex@example.com',
                'firstName' => 'Alex',
                'lastName' => 'Rivera',
                'phone' => null,
            ],
            'memberships' => [
                [
                    'businessId' => 10,
                    'branchId' => 20,
                    'role' => 'STAFF',
                ],
                [
                    'businessId' => 11,
                    'branchId' => null,
                    'role' => 'OWNER',
                ],
            ],
        ]);

        $this->assertSame('opaque-test-token', $login->token);
        $this->assertSame('Bearer', $login->tokenType);
        $this->assertInstanceOf(CarbonImmutable::class, $login->expiresAt);
        $this->assertSame('2030-04-15T10:30:00+05:45', $login->expiresAt->format('Y-m-d\TH:i:sP'));
        $this->assertInstanceOf(AuthUserData::class, $login->user);
        $this->assertSame(42, $login->user->id);
        $this->assertNull($login->user->phone);
        $this->assertCount(2, $login->memberships);
        $this->assertInstanceOf(StaffMembershipData::class, $login->memberships[0]);
        $this->assertSame(20, $login->memberships[0]->branchId);
        $this->assertSame('STAFF', $login->memberships[0]->role);
        $this->assertInstanceOf(StaffMembershipData::class, $login->memberships[1]);
        $this->assertNull($login->memberships[1]->branchId);
        $this->assertSame('OWNER', $login->memberships[1]->role);
    }
}
