<?php

namespace Tests\Unit\Data;

use App\Data\QueueEntryData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class QueueEntryDataTest extends TestCase
{
    public function test_maps_guest_queue_entry_response(): void
    {
        $entry = QueueEntryData::fromArray([
            'id' => 301,
            'queueId' => 91,
            'serviceId' => 31,
            'userId' => null,
            'ticketSequence' => 23,
            'ticketNumber' => 'A023',
            'status' => 'WAITING',
            'joinedAt' => '2030-04-15T10:30:00+08:00',
            'guestToken' => 'inert-guest-token',
        ]);

        $this->assertSame(301, $entry->id);
        $this->assertSame(91, $entry->queueId);
        $this->assertSame(31, $entry->serviceId);
        $this->assertNull($entry->userId);
        $this->assertSame(23, $entry->ticketSequence);
        $this->assertSame('A023', $entry->ticketNumber);
        $this->assertSame('WAITING', $entry->status);
        $this->assertInstanceOf(CarbonImmutable::class, $entry->joinedAt);
        $this->assertSame('+08:00', $entry->joinedAt->format('P'));
        $this->assertSame('inert-guest-token', $entry->guestToken);
    }

    public function test_maps_registered_user_entry_without_guest_token(): void
    {
        $entry = QueueEntryData::fromArray([
            'id' => 302,
            'queueId' => 91,
            'serviceId' => 31,
            'userId' => 42,
            'ticketSequence' => 24,
            'ticketNumber' => 'A024',
            'status' => 'CANCELLED',
            'joinedAt' => '2030-04-15T10:35:00+08:00',
            'guestToken' => null,
        ]);

        $this->assertSame(42, $entry->userId);
        $this->assertNull($entry->guestToken);
        $this->assertSame('CANCELLED', $entry->status);
    }
}
