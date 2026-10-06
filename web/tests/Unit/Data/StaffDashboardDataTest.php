<?php

namespace Tests\Unit\Data;

use App\Data\QueueStaffEntryData;
use App\Data\StaffDashboardData;
use App\Data\StaffDashboardQueueData;
use App\Data\StaffDashboardServiceData;
use PHPUnit\Framework\TestCase;

class StaffDashboardDataTest extends TestCase
{
    public function test_maps_multiple_queues_and_preserves_spring_dashboard_order_and_states(): void
    {
        $dashboard = StaffDashboardData::fromArray([
            'businessId' => 10,
            'branchId' => 21,
            'businessDate' => '2030-04-15',
            'queues' => [
                $this->queueResponse(
                    queueId: 91,
                    status: 'OPEN',
                    service: [
                        'id' => 31,
                        'name' => 'General Consultation',
                        'durationMinutes' => 20,
                    ],
                    serving: $this->entryResponse(304, 91, 31, 4, 'A004', 'SERVING'),
                    called: $this->entryResponse(303, 91, 31, 3, 'A003', 'CALLED'),
                    waiting: [
                        $this->entryResponse(301, 91, 31, 1, 'A001', 'WAITING'),
                        $this->entryResponse(302, 91, 31, 2, 'A002', 'WAITING'),
                    ],
                ),
                $this->queueResponse(queueId: 92, status: 'PAUSED'),
                $this->queueResponse(queueId: 93, status: 'CLOSED'),
            ],
        ]);

        $this->assertSame(10, $dashboard->businessId);
        $this->assertSame(21, $dashboard->branchId);
        $this->assertSame('2030-04-15', $dashboard->businessDate);
        $this->assertSame([91, 92, 93], array_map(
            static fn (StaffDashboardQueueData $queue): int => $queue->queueId,
            $dashboard->queues,
        ));
        $this->assertSame(['queue-91-public-code', 'queue-92-public-code', 'queue-93-public-code'], array_map(
            static fn (StaffDashboardQueueData $queue): string => $queue->publicCode,
            $dashboard->queues,
        ));
        $this->assertSame(['OPEN', 'PAUSED', 'CLOSED'], array_map(
            static fn (StaffDashboardQueueData $queue): string => $queue->status,
            $dashboard->queues,
        ));

        $openQueue = $dashboard->queues[0];
        $this->assertInstanceOf(StaffDashboardServiceData::class, $openQueue->service);
        $this->assertSame('General Consultation', $openQueue->service->name);
        $this->assertSame(20, $openQueue->service->durationMinutes);
        $this->assertSame(2, $openQueue->counts->waiting);
        $this->assertSame(1, $openQueue->counts->called);
        $this->assertSame(1, $openQueue->counts->serving);
        $this->assertSame(304, $openQueue->serving?->entryId);
        $this->assertSame(303, $openQueue->called?->entryId);
        $this->assertSame([301, 302], array_map(
            static fn (QueueStaffEntryData $entry): int => $entry->entryId,
            $openQueue->waiting,
        ));

        $sharedQueue = $dashboard->queues[1];
        $this->assertNull($sharedQueue->service);
        $this->assertNull($sharedQueue->called);
        $this->assertNull($sharedQueue->serving);
        $this->assertSame([], $sharedQueue->waiting);
    }

    /**
     * @param  array<string, mixed>|null  $service
     * @param  array<string, mixed>|null  $serving
     * @param  array<string, mixed>|null  $called
     * @param  list<array<string, mixed>>  $waiting
     * @return array<string, mixed>
     */
    private function queueResponse(
        int $queueId,
        string $status,
        ?array $service = null,
        ?array $serving = null,
        ?array $called = null,
        array $waiting = [],
    ): array {
        return [
            'queueId' => $queueId,
            'publicCode' => "queue-{$queueId}-public-code",
            'name' => "Queue {$queueId}",
            'status' => $status,
            'ticketPrefix' => 'A',
            'service' => $service,
            'counts' => [
                'waiting' => count($waiting),
                'called' => $called === null ? 0 : 1,
                'serving' => $serving === null ? 0 : 1,
            ],
            'serving' => $serving,
            'called' => $called,
            'waiting' => $waiting,
        ];
    }

    /** @return array<string, mixed> */
    private function entryResponse(
        int $entryId,
        int $queueId,
        int $serviceId,
        int $ticketSequence,
        string $ticketNumber,
        string $status,
    ): array {
        return [
            'entryId' => $entryId,
            'queueId' => $queueId,
            'serviceId' => $serviceId,
            'userId' => null,
            'counterId' => null,
            'ticketSequence' => $ticketSequence,
            'ticketNumber' => $ticketNumber,
            'status' => $status,
            'joinedAt' => '2030-04-15T10:30:00+08:00',
            'calledAt' => in_array($status, ['CALLED', 'SERVING'], true)
                ? '2030-04-15T10:40:00+08:00'
                : null,
            'servingAt' => $status === 'SERVING'
                ? '2030-04-15T10:45:00+08:00'
                : null,
            'completedAt' => null,
            'cancelledAt' => null,
        ];
    }
}
