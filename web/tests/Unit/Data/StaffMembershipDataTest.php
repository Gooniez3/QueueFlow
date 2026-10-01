<?php

namespace Tests\Unit\Data;

use App\Data\StaffMembershipData;
use Tests\TestCase;

class StaffMembershipDataTest extends TestCase
{
    public function test_maps_branch_membership_response(): void
    {
        $membership = StaffMembershipData::fromArray([
            'businessId' => 10,
            'branchId' => 20,
            'role' => 'MANAGER',
        ]);

        $this->assertSame(10, $membership->businessId);
        $this->assertSame(20, $membership->branchId);
        $this->assertSame('MANAGER', $membership->role);
    }

    public function test_maps_null_branch_id(): void
    {
        $membership = StaffMembershipData::fromArray([
            'businessId' => 10,
            'branchId' => null,
            'role' => 'OWNER',
        ]);

        $this->assertNull($membership->branchId);
    }
}
