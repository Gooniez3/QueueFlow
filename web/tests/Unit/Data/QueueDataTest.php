<?php

namespace Tests\Unit\Data;

use App\Data\QueueData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class QueueDataTest extends TestCase
{
    public function test_maps_queue_response_and_nullable_fields(): void
    {
        $queue = QueueData::fromArray([
            'id' => 91,
            'branchId' => 21,
            'serviceId' => null,
            'name' => 'Walk-in Queue',
            'businessDate' => '2030-04-15',
            'ticketPrefix' => 'A',
            'nextTicketSequence' => 24,
            'status' => 'OPEN',
            'openedAt' => '2030-04-15T08:00:00+08:00',
            'closedAt' => null,
            'createdAt' => '2030-04-15T08:00:00+08:00',
            'updatedAt' => '2030-04-15T10:30:00+08:00',
        ]);

        $this->assertSame(91, $queue->id);
        $this->assertSame(21, $queue->branchId);
        $this->assertNull($queue->serviceId);
        $this->assertSame('Walk-in Queue', $queue->name);
        $this->assertSame('2030-04-15', $queue->businessDate);
        $this->assertSame('A', $queue->ticketPrefix);
        $this->assertSame(24, $queue->nextTicketSequence);
        $this->assertSame('OPEN', $queue->status);
        $this->assertInstanceOf(CarbonImmutable::class, $queue->openedAt);
        $this->assertSame('+08:00', $queue->openedAt->format('P'));
        $this->assertNull($queue->closedAt);
        $this->assertInstanceOf(CarbonImmutable::class, $queue->createdAt);
        $this->assertInstanceOf(CarbonImmutable::class, $queue->updatedAt);
    }
}
