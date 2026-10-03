<?php

namespace Tests\Unit\Data;

use App\Data\TodayQueueData;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TodayQueueDataTest extends TestCase
{
    #[DataProvider('queueStatuses')]
    public function test_maps_service_specific_queue_and_supported_statuses(string $status): void
    {
        $queue = TodayQueueData::fromArray([
            'id' => 91,
            'branchId' => 21,
            'serviceId' => 31,
            'name' => 'General Consultation',
            'businessDate' => '2030-04-15',
            'ticketPrefix' => 'A',
            'status' => $status,
        ]);

        $this->assertSame(91, $queue->id);
        $this->assertSame(21, $queue->branchId);
        $this->assertSame(31, $queue->serviceId);
        $this->assertSame('General Consultation', $queue->name);
        $this->assertSame('2030-04-15', $queue->businessDate);
        $this->assertSame('A', $queue->ticketPrefix);
        $this->assertSame($status, $queue->status);
    }

    public function test_maps_shared_queue_with_null_service_id(): void
    {
        $queue = TodayQueueData::fromArray([
            'id' => 92,
            'branchId' => 21,
            'serviceId' => null,
            'name' => 'Walk-in Queue',
            'businessDate' => '2030-04-15',
            'ticketPrefix' => 'W',
            'status' => 'OPEN',
        ]);

        $this->assertNull($queue->serviceId);
    }

    /** @return array<string, array{string}> */
    public static function queueStatuses(): array
    {
        return [
            'open' => ['OPEN'],
            'paused' => ['PAUSED'],
            'closed' => ['CLOSED'],
        ];
    }
}
