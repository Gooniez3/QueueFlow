<?php

namespace Tests\Unit\Data;

use App\Data\QueueStaffEntryData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class QueueStaffEntryDataTest extends TestCase
{
    public function test_maps_staff_entry_response_and_nullable_fields(): void
    {
        $entry = QueueStaffEntryData::fromArray([
            'entryId' => 301,
            'queueId' => 91,
            'serviceId' => 31,
            'userId' => null,
            'counterId' => null,
            'ticketSequence' => 23,
            'ticketNumber' => 'A023',
            'status' => 'CALLED',
            'joinedAt' => '2030-04-15T10:30:00+08:00',
            'calledAt' => '2030-04-15T10:40:00+08:00',
            'servingAt' => null,
            'completedAt' => null,
            'cancelledAt' => null,
        ]);

        $this->assertSame(301, $entry->entryId);
        $this->assertSame(91, $entry->queueId);
        $this->assertSame(31, $entry->serviceId);
        $this->assertNull($entry->userId);
        $this->assertNull($entry->counterId);
        $this->assertSame(23, $entry->ticketSequence);
        $this->assertSame('A023', $entry->ticketNumber);
        $this->assertSame('CALLED', $entry->status);
        $this->assertInstanceOf(CarbonImmutable::class, $entry->joinedAt);
        $this->assertInstanceOf(CarbonImmutable::class, $entry->calledAt);
        $this->assertSame('+08:00', $entry->calledAt?->format('P'));
        $this->assertNull($entry->servingAt);
        $this->assertNull($entry->completedAt);
        $this->assertNull($entry->cancelledAt);
    }
}
