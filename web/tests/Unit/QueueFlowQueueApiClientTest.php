<?php

namespace Tests\Unit;

use App\Data\QueueData;
use App\Data\QueueEntryData;
use App\Data\QueuePositionData;
use App\Data\QueueStaffEntryData;
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
            && $request->data() === ['serviceId' => 31]);
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
        );

        $this->assertSame(42, $entry->userId);
        $this->assertNull($entry->guestToken);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader(
            'Authorization',
            'Bearer inert-customer-token',
        ));
    }

    public function test_guest_position_uses_guest_token_header_and_maps_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response([
                'entryId' => 301,
                'queueId' => 91,
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
            ),
            default => app(QueueFlowApiClient::class)->{$method}(
                91,
                301,
                'inert-staff-token',
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
            && $request->hasHeader('Authorization', 'Bearer inert-staff-token'));
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
    private function staffEntryResponse(): array
    {
        return [
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
        ];
    }
}
