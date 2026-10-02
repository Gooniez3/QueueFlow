<?php

namespace Tests\Unit\Data;

use App\Data\BranchData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class BranchDataTest extends TestCase
{
    public function test_maps_branch_response_and_preserves_created_at_offset(): void
    {
        $branch = BranchData::fromArray([
            'id' => 21,
            'businessId' => 10,
            'name' => 'Riverside Clinic',
            'address' => '10 River Road, Singapore',
            'latitude' => 1.3521,
            'longitude' => 103.8198,
            'createdAt' => '2030-04-15T10:30:00+08:00',
        ]);

        $this->assertSame(21, $branch->id);
        $this->assertSame(10, $branch->businessId);
        $this->assertSame('Riverside Clinic', $branch->name);
        $this->assertSame('10 River Road, Singapore', $branch->address);
        $this->assertSame(1.3521, $branch->latitude);
        $this->assertSame(103.8198, $branch->longitude);
        $this->assertInstanceOf(CarbonImmutable::class, $branch->createdAt);
        $this->assertSame('2030-04-15T10:30:00+08:00', $branch->createdAt->format('Y-m-d\TH:i:sP'));
    }

    public function test_maps_null_coordinates(): void
    {
        $branch = BranchData::fromArray([
            'id' => 22,
            'businessId' => 10,
            'name' => 'Mobile Clinic',
            'address' => 'Service area assigned daily',
            'latitude' => null,
            'longitude' => null,
            'createdAt' => '2030-04-15T10:30:00Z',
        ]);

        $this->assertNull($branch->latitude);
        $this->assertNull($branch->longitude);
    }
}
