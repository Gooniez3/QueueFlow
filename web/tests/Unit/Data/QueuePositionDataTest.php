<?php

namespace Tests\Unit\Data;

use App\Data\QueuePositionData;
use Tests\TestCase;

class QueuePositionDataTest extends TestCase
{
    public function test_maps_queue_position_response(): void
    {
        $position = QueuePositionData::fromArray([
            'entryId' => 301,
            'queueId' => 91,
            'publicCode' => 'public-queue-code',
            'serviceId' => 31,
            'ticketSequence' => 23,
            'ticketNumber' => 'A023',
            'status' => 'WAITING',
            'peopleAhead' => 2,
            'estimatedWaitMinutes' => 40,
        ]);

        $this->assertSame(301, $position->entryId);
        $this->assertSame(91, $position->queueId);
        $this->assertSame('public-queue-code', $position->publicCode);
        $this->assertSame(31, $position->serviceId);
        $this->assertSame(23, $position->ticketSequence);
        $this->assertSame('A023', $position->ticketNumber);
        $this->assertSame('WAITING', $position->status);
        $this->assertSame(2, $position->peopleAhead);
        $this->assertSame(40, $position->estimatedWaitMinutes);
    }
}
