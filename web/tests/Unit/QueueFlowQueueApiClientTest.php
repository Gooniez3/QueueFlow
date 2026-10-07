<?php

namespace Tests\Unit;

use App\Data\QueueData;
use App\Data\QueueEntryData;
use App\Data\QueuePositionData;
use App\Data\QueueStaffEntryData;
use App\Data\StaffDashboardData;
use App\Data\TodayQueueData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueueFlowQueueApiClientTest extends TestCase
{
    public function test_it_creates_a_queue_with_bearer_authentication_and_maps_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues' => Http::response(
                $this->queueResponse(),
                201,
            ),
        ]);

        $queue = app(QueueFlowApiClient::class)->createQueue(
            10,
            21,
            'inert-staff-token',
            null,
            'Walk-in Queue',
            'A',
        );

        $this->assertInstanceOf(QueueData::class, $queue);
        $this->assertSame(91, $queue->id);
        $this->assertNull($queue->serviceId);
        $this->assertSame('2030-04-15', $queue->businessDate);
        $this->assertNull($queue->closedAt);
        $this->assertSame('+08:00', $queue->openedAt->format('P'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/21/queues'
            && $request->hasHeader('Authorization', 'Bearer inert-staff-token')
            && $request->data() === [
                'serviceId' => null,
                'name' => 'Walk-in Queue',
                'ticketPrefix' => 'A',
            ]);
    }

    public function test_guest_can_join_without_bearer_authentication_and_receives_guest_token(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries' => Http::response(
                $this->queueEntryResponse(guestToken: 'inert-guest-token'),
                201,
            ),
        ]);

        $entry = app(QueueFlowApiClient::class)->joinQueue(91, 31);

        $this->assertInstanceOf(QueueEntryData::class, $entry);
        $this->assertNull($entry->userId);
        $this->assertSame('inert-guest-token', $entry->guestToken);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/queues/91/entries'
            && ! $request->hasHeader('Authorization')
            && ! $request->hasHeader('X-Guest-Token')
            && ! $request->hasHeader('Idempotency-Key')
            && $request->data() === ['serviceId' => 31]);
    }

    public function test_guest_join_sends_supplied_idempotency_key(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries' => Http::response(
                $this->queueEntryResponse(guestToken: 'inert-guest-token'),
                201,
            ),
        ]);

        app(QueueFlowApiClient::class)->joinQueue(
            91,
            31,
            idempotencyKey: '11111111-1111-4111-8111-111111111111',
        );

        Http::assertSent(fn (Request $request): bool => $request->hasHeader(
            'Idempotency-Key',
            '11111111-1111-4111-8111-111111111111',
        ) && ! $request->hasHeader('Authorization'));
    }

    public function test_registered_user_can_join_with_bearer_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries' => Http::response(
                $this->queueEntryResponse(userId: 42),
                201,
            ),
        ]);

        $entry = app(QueueFlowApiClient::class)->joinQueue(
            91,
            31,
            'inert-customer-token',
            '22222222-2222-4222-8222-222222222222',
        );

        $this->assertSame(42, $entry->userId);
        $this->assertNull($entry->guestToken);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader(
            'Authorization',
            'Bearer inert-customer-token',
        ) && $request->hasHeader(
            'Idempotency-Key',
            '22222222-2222-4222-8222-222222222222',
        ));
    }

    public function test_it_gets_service_specific_today_queue_without_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today?serviceId=31' => Http::response(
                $this->todayQueueResponse(serviceId: 31),
            ),
        ]);

        $queue = app(QueueFlowApiClient::class)->todayQueue(10, 21, 31);

        $this->assertInstanceOf(TodayQueueData::class, $queue);
        $this->assertSame(31, $queue->serviceId);
        $this->assertSame('OPEN', $queue->status);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today?serviceId=31'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_it_gets_shared_today_queue_without_service_query_or_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today' => Http::response(
                $this->todayQueueResponse(),
            ),
        ]);

        $queue = app(QueueFlowApiClient::class)->todayQueue(10, 21);

        $this->assertNull($queue->serviceId);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_today_queue_not_found_is_preserved(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today' => Http::response([
                'message' => 'Queue not found for today',
            ], 404),
        ]);

        try {
            app(QueueFlowApiClient::class)->todayQueue(10, 21);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(404, $exception->status);
            $this->assertSame('Queue not found for today', $exception->getMessage());
        }
    }

    public function test_today_queue_connection_failure_uses_existing_safe_exception(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today' => Http::failedConnection(
                'Connection refused with internal details',
            ),
        ]);

        try {
            app(QueueFlowApiClient::class)->todayQueue(10, 21);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame('Unable to connect to the QueueFlow API.', $exception->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
        }
    }

    public function test_guest_position_uses_guest_token_header_and_maps_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response([
                'entryId' => 301,
                'queueId' => 91,
                'publicCode' => 'public-queue-code',
                'serviceId' => 31,
                'ticketSequence' => 23,
                'ticketNumber' => 'A023',
                'status' => 'WAITING',
                'peopleAhead' => 2,
                'estimatedWaitMinutes' => 40,
            ]),
        ]);

        $position = app(QueueFlowApiClient::class)->queuePosition(
            91,
            301,
            guestToken: 'inert-guest-token',
        );

        $this->assertInstanceOf(QueuePositionData::class, $position);
        $this->assertSame(2, $position->peopleAhead);
        $this->assertSame(40, $position->estimatedWaitMinutes);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/queues/91/entries/301/position'
            && $request->hasHeader('X-Guest-Token', 'inert-guest-token')
            && ! $request->hasHeader('Authorization'));
    }

    public function test_guest_cancel_uses_guest_token_header_and_maps_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries/301/cancel' => Http::response(
                $this->queueEntryResponse(status: 'CANCELLED'),
            ),
        ]);

        $entry = app(QueueFlowApiClient::class)->cancelQueueEntry(
            91,
            301,
            guestToken: 'inert-guest-token',
        );

        $this->assertInstanceOf(QueueEntryData::class, $entry);
        $this->assertSame('CANCELLED', $entry->status);
        $this->assertNull($entry->guestToken);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/queues/91/entries/301/cancel'
            && $request->hasHeader('X-Guest-Token', 'inert-guest-token')
            && ! $request->hasHeader('Authorization'));
    }

    public function test_registered_user_position_uses_bearer_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response([
                'entryId' => 301,
                'queueId' => 91,
                'publicCode' => 'public-queue-code',
                'serviceId' => 31,
                'ticketSequence' => 23,
                'ticketNumber' => 'A023',
                'status' => 'WAITING',
                'peopleAhead' => 2,
                'estimatedWaitMinutes' => 40,
            ]),
        ]);

        $position = app(QueueFlowApiClient::class)->queuePosition(
            91,
            301,
            token: 'inert-customer-token',
        );

        $this->assertSame(301, $position->entryId);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader(
            'Authorization',
            'Bearer inert-customer-token',
        ) && ! $request->hasHeader('X-Guest-Token'));
    }

    public function test_staff_dashboard_uses_bearer_authentication_and_maps_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/staff/dashboard' => Http::response(
                $this->staffDashboardResponse(),
            ),
        ]);

        $dashboard = app(QueueFlowApiClient::class)->staffDashboard(
            10,
            21,
            'inert-staff-token',
        );

        $this->assertInstanceOf(StaffDashboardData::class, $dashboard);
        $this->assertSame('2030-04-15', $dashboard->businessDate);
        $this->assertCount(2, $dashboard->queues);
        $this->assertSame('consultation-queue-public-code', $dashboard->queues[0]->publicCode);
        $this->assertSame([301, 302], array_map(
            static fn (QueueStaffEntryData $entry): int => $entry->entryId,
            $dashboard->queues[0]->waiting,
        ));
        $this->assertNull($dashboard->queues[1]->service);
        $this->assertNull($dashboard->queues[1]->called);
        $this->assertNull($dashboard->queues[1]->serving);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/21/staff/dashboard'
            && $request->hasHeader('Authorization', 'Bearer inert-staff-token'));
    }

    #[DataProvider('staffEntryTransitions')]
    public function test_staff_entry_transitions_use_bearer_authentication(
        string $method,
        string $path,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            "http://localhost:8080/api/v1/queues/91/staff/{$path}" => Http::response(
                $this->staffEntryResponse(),
            ),
        ]);

        $entry = match ($method) {
            'callNextQueueEntry' => app(QueueFlowApiClient::class)->callNextQueueEntry(
                91,
                'inert-staff-token',
                '11111111-1111-4111-8111-111111111111',
            ),
            default => app(QueueFlowApiClient::class)->{$method}(
                91,
                301,
                'inert-staff-token',
                '11111111-1111-4111-8111-111111111111',
            ),
        };

        $this->assertInstanceOf(QueueStaffEntryData::class, $entry);
        $this->assertNull($entry->userId);
        $this->assertNull($entry->counterId);
        $this->assertSame('CALLED', $entry->status);
        $this->assertNull($entry->servingAt);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === "http://localhost:8080/api/v1/queues/91/staff/{$path}"
            && $request->hasHeader('Authorization', 'Bearer inert-staff-token')
            && $request->hasHeader('Idempotency-Key', '11111111-1111-4111-8111-111111111111')
            && $request->data() === []);
    }

    #[DataProvider('queueStateTransitions')]
    public function test_queue_state_transitions_use_bearer_authentication(
        string $method,
        string $path,
        string $status,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            "http://localhost:8080/api/v1/queues/91/staff/{$path}" => Http::response(
                $this->queueResponse(status: $status),
            ),
        ]);

        $queue = app(QueueFlowApiClient::class)->{$method}(
            91,
            'inert-staff-token',
        );

        $this->assertInstanceOf(QueueData::class, $queue);
        $this->assertSame($status, $queue->status);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === "http://localhost:8080/api/v1/queues/91/staff/{$path}"
            && $request->hasHeader('Authorization', 'Bearer inert-staff-token')
            && ! $request->hasHeader('Idempotency-Key'));
    }

    public function test_recall_conflict_remains_distinguishable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/staff/entries/301/recall' => Http::response([
                'message' => 'Only a called entry can be recalled',
            ], 409),
        ]);

        try {
            app(QueueFlowApiClient::class)->recallQueueEntry(
                91,
                301,
                'inert-staff-token',
                '11111111-1111-4111-8111-111111111111',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(409, $exception->status);
            $this->assertSame('Only a called entry can be recalled', $exception->getMessage());
        }
    }

    #[DataProvider('dashboardApiFailures')]
    public function test_staff_dashboard_api_failures_are_preserved(
        int $status,
        string $message,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/staff/dashboard' => Http::response([
                'message' => $message,
            ], $status),
        ]);

        try {
            app(QueueFlowApiClient::class)->staffDashboard(10, 21, 'inert-staff-token');

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($status, $exception->status);
            $this->assertSame($message, $exception->getMessage());
        }
    }

    public function test_staff_dashboard_connection_failure_uses_existing_safe_exception(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/staff/dashboard' => Http::failedConnection(
                'Connection refused with internal details',
            ),
        ]);

        try {
            app(QueueFlowApiClient::class)->staffDashboard(10, 21, 'inert-staff-token');

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame('Unable to connect to the QueueFlow API.', $exception->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
        }
    }

    /**
     * @param  array<string, string>  $validationErrors
     */
    #[DataProvider('apiFailures')]
    public function test_queue_api_failures_are_preserved(
        int $status,
        string $message,
        array $validationErrors,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues' => Http::response([
                'message' => $message,
                'validationErrors' => $validationErrors,
            ], $status),
        ]);

        try {
            app(QueueFlowApiClient::class)->createQueue(
                10,
                21,
                'inert-staff-token',
                null,
                'Walk-in Queue',
                'A',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($status, $exception->status);
            $this->assertSame($message, $exception->getMessage());
            $this->assertSame($validationErrors, $exception->validationErrors);
        }
    }

    public function test_queue_connection_failure_uses_existing_safe_exception(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries' => Http::failedConnection(
                'Connection refused with internal details',
            ),
        ]);

        try {
            app(QueueFlowApiClient::class)->joinQueue(91, 31);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame('Unable to connect to the QueueFlow API.', $exception->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function staffEntryTransitions(): array
    {
        return [
            'call next' => ['callNextQueueEntry', 'call-next'],
            'recall' => ['recallQueueEntry', 'entries/301/recall'],
            'start serving' => ['startServingQueueEntry', 'entries/301/start'],
            'complete' => ['completeQueueEntry', 'entries/301/complete'],
            'skip' => ['skipQueueEntry', 'entries/301/skip'],
        ];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function queueStateTransitions(): array
    {
        return [
            'pause' => ['pauseQueue', 'pause', 'PAUSED'],
            'resume' => ['resumeQueue', 'resume', 'OPEN'],
            'close' => ['closeQueue', 'close', 'CLOSED'],
            'reopen' => ['reopenQueue', 'reopen', 'OPEN'],
        ];
    }

    /**
     * @return array<string, array{int, string, array<string, string>}>
     */
    public static function apiFailures(): array
    {
        return [
            'validation' => [400, 'Request validation failed', ['name' => 'Queue name is required']],
            'unauthenticated' => [401, 'Authentication is required', []],
            'forbidden' => [403, 'Access is denied', []],
            'not found' => [404, 'Branch not found', []],
            'state conflict' => [409, 'Queue already exists for this service today', []],
            'server failure' => [500, 'Internal server error', []],
        ];
    }

    /** @return array<string, array{int, string}> */
    public static function dashboardApiFailures(): array
    {
        return [
            'unauthenticated' => [401, 'Authentication is required'],
            'forbidden' => [403, 'Access is denied'],
            'server failure' => [500, 'Internal server error'],
        ];
    }

    /** @return array<string, mixed> */
    private function queueResponse(string $status = 'OPEN'): array
    {
        return [
            'id' => 91,
            'branchId' => 21,
            'serviceId' => null,
            'name' => 'Walk-in Queue',
            'businessDate' => '2030-04-15',
            'ticketPrefix' => 'A',
            'nextTicketSequence' => 24,
            'status' => $status,
            'openedAt' => '2030-04-15T08:00:00+08:00',
            'closedAt' => $status === 'CLOSED'
                ? '2030-04-15T18:00:00+08:00'
                : null,
            'createdAt' => '2030-04-15T08:00:00+08:00',
            'updatedAt' => '2030-04-15T10:30:00+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function queueEntryResponse(
        ?int $userId = null,
        ?string $guestToken = null,
        string $status = 'WAITING',
    ): array {
        return [
            'id' => 301,
            'queueId' => 91,
            'serviceId' => 31,
            'userId' => $userId,
            'ticketSequence' => 23,
            'ticketNumber' => 'A023',
            'status' => $status,
            'joinedAt' => '2030-04-15T10:30:00+08:00',
            'guestToken' => $guestToken,
        ];
    }

    /** @return array<string, mixed> */
    private function todayQueueResponse(?int $serviceId = null): array
    {
        return [
            'id' => 91,
            'branchId' => 21,
            'serviceId' => $serviceId,
            'name' => $serviceId === null ? 'Walk-in Queue' : 'General Consultation',
            'businessDate' => '2030-04-15',
            'ticketPrefix' => 'A',
            'status' => 'OPEN',
        ];
    }

    /** @return array<string, mixed> */
    private function staffEntryResponse(
        int $entryId = 301,
        int $ticketSequence = 23,
        string $ticketNumber = 'A023',
        string $status = 'CALLED',
    ): array {
        return [
            'entryId' => $entryId,
            'queueId' => 91,
            'serviceId' => 31,
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

    /** @return array<string, mixed> */
    private function staffDashboardResponse(): array
    {
        return [
            'businessId' => 10,
            'branchId' => 21,
            'businessDate' => '2030-04-15',
            'queues' => [
                [
                    'queueId' => 91,
                    'publicCode' => 'consultation-queue-public-code',
                    'name' => 'Consultation Queue',
                    'status' => 'OPEN',
                    'ticketPrefix' => 'A',
                    'service' => [
                        'id' => 31,
                        'name' => 'General Consultation',
                        'durationMinutes' => 20,
                    ],
                    'counts' => [
                        'waiting' => 2,
                        'called' => 1,
                        'serving' => 1,
                    ],
                    'serving' => $this->staffEntryResponse(
                        entryId: 304,
                        ticketSequence: 4,
                        ticketNumber: 'A004',
                        status: 'SERVING',
                    ),
                    'called' => $this->staffEntryResponse(
                        entryId: 303,
                        ticketSequence: 3,
                        ticketNumber: 'A003',
                    ),
                    'waiting' => [
                        $this->staffEntryResponse(
                            entryId: 301,
                            ticketSequence: 1,
                            ticketNumber: 'A001',
                            status: 'WAITING',
                        ),
                        $this->staffEntryResponse(
                            entryId: 302,
                            ticketSequence: 2,
                            ticketNumber: 'A002',
                            status: 'WAITING',
                        ),
                    ],
                ],
                [
                    'queueId' => 92,
                    'publicCode' => 'shared-queue-public-code',
                    'name' => 'Shared Queue',
                    'status' => 'PAUSED',
                    'ticketPrefix' => 'S',
                    'service' => null,
                    'counts' => [
                        'waiting' => 0,
                        'called' => 0,
                        'serving' => 0,
                    ],
                    'serving' => null,
                    'called' => null,
                    'waiting' => [],
                ],
            ],
        ];
    }
}
