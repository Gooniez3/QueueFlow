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

    public function test_identifies_business_membership(): void
    {
        $membership = new StaffMembershipData(
            businessId: 12,
            branchId: 34,
            role: 'MANAGER',
        );

        $this->assertTrue(
            $membership->belongsToBusiness(12),
        );
        $this->assertFalse(
            $membership->belongsToBusiness(99),
        );
    }

    public function test_identifies_branch_membership(): void
    {
        $membership = new StaffMembershipData(
            businessId: 12,
            branchId: 34,
            role: 'STAFF',
        );

        $this->assertTrue(
            $membership->belongsToBranch(34),
        );
        $this->assertFalse(
            $membership->belongsToBranch(99),
        );
    }

    public function test_business_wide_membership_does_not_match_specific_branch(): void
    {
        $membership = new StaffMembershipData(
            businessId: 12,
            branchId: null,
            role: 'OWNER',
        );

        $this->assertTrue(
            $membership->belongsToBusiness(12),
        );
        $this->assertFalse(
            $membership->belongsToBranch(34),
        );
    }
}
