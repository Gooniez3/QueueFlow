<?php

namespace Tests\Unit\Data;

use App\Data\ServiceData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ServiceDataTest extends TestCase
{
    public function test_maps_service_response_and_preserves_created_at_offset(): void
    {
        $service = ServiceData::fromArray([
            'id' => 31,
            'branchId' => 21,
            'name' => 'General Consultation',
            'description' => 'Standard medical consultation',
            'durationMinutes' => 20,
            'active' => true,
            'createdAt' => '2030-04-15T10:30:00+08:00',
        ]);

        $this->assertSame(31, $service->id);
        $this->assertSame(21, $service->branchId);
        $this->assertSame('General Consultation', $service->name);
        $this->assertSame('Standard medical consultation', $service->description);
        $this->assertSame(20, $service->durationMinutes);
        $this->assertTrue($service->active);
        $this->assertInstanceOf(CarbonImmutable::class, $service->createdAt);
        $this->assertSame('2030-04-15T10:30:00+08:00', $service->createdAt->format('Y-m-d\TH:i:sP'));
    }

    public function test_maps_null_description_and_inactive_state(): void
    {
        $service = ServiceData::fromArray([
            'id' => 32,
            'branchId' => 21,
            'name' => 'Walk-in Support',
            'description' => null,
            'durationMinutes' => 10,
            'active' => false,
            'createdAt' => '2030-04-15T10:30:00Z',
        ]);

        $this->assertNull($service->description);
        $this->assertFalse($service->active);
    }
}
