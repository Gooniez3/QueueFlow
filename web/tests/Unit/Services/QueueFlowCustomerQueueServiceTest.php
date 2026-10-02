<?php

namespace Tests\Unit\Services;

use App\Data\CustomerQueueTicketData;
use App\Data\GuestQueueOwnershipData;
use App\Data\QueueEntryData;
use App\Data\QueuePositionData;
use App\Data\TodayQueueData;
use App\Exceptions\GuestQueueOwnershipException;
use App\Exceptions\QueueFlowApiException;
use App\Services\GuestQueueOwnershipStore;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowCustomerQueueService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class QueueFlowCustomerQueueServiceTest extends TestCase
{
    public function test_returns_service_specific_queue_without_shared_lookup(): void
    {
        $queue = $this->todayQueue(serviceId: 31);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21, 31)
            ->andReturn($queue);
        $apiClient->shouldNotReceive('todayQueue')->with(10, 21, null);
        $service = $this->customerService($apiClient);

        $result = $service->applicableQueue(10, 21, 31);

        $this->assertSame($queue, $result);
    }

    public function test_falls_back_to_shared_queue_after_service_specific_404(): void
    {
        $notFound = new QueueFlowApiException('Queue not found for today', 404);
        $sharedQueue = $this->todayQueue();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21, 31)
            ->ordered()
            ->andThrow($notFound);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21)
            ->ordered()
            ->andReturn($sharedQueue);
        $service = $this->customerService($apiClient);

        $result = $service->applicableQueue(10, 21, 31);

        $this->assertSame($sharedQueue, $result);
    }

    #[DataProvider('nonNotFoundDiscoveryFailures')]
    public function test_preserves_non_404_discovery_failure_without_shared_fallback(?int $status): void
    {
        $failure = new QueueFlowApiException('Downstream failure', $status);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21, 31)
            ->andThrow($failure);
        $apiClient->shouldNotReceive('todayQueue')->with(10, 21, null);
        $service = $this->customerService($apiClient);

        try {
            $service->applicableQueue(10, 21, 31);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    public function test_preserves_connection_failure_without_shared_fallback(): void
    {
        $connection = new ConnectionException('Connection refused');
        $failure = new QueueFlowApiException(
            'Unable to connect to the QueueFlow API.',
            previous: $connection,
        );
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21, 31)
            ->andThrow($failure);
        $apiClient->shouldNotReceive('todayQueue')->with(10, 21, null);
        $service = $this->customerService($apiClient);

        try {
            $service->applicableQueue(10, 21, 31);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($failure, $exception);
            $this->assertSame($connection, $exception->getPrevious());
        }
    }

    public function test_preserves_non_queue_404_without_shared_fallback(): void
    {
        $failure = new QueueFlowApiException('Service not found with id: 31', 404);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21, 31)
            ->andThrow($failure);
        $apiClient->shouldNotReceive('todayQueue')->with(10, 21, null);
        $service = $this->customerService($apiClient);

        try {
            $service->applicableQueue(10, 21, 31);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    public function test_preserves_shared_404_when_no_queue_is_available(): void
    {
        $serviceNotFound = new QueueFlowApiException('Queue not found for today', 404);
        $sharedNotFound = new QueueFlowApiException('Queue not found for today', 404);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21, 31)
            ->andThrow($serviceNotFound);
        $apiClient->shouldReceive('todayQueue')
            ->once()
            ->with(10, 21)
            ->andThrow($sharedNotFound);
        $service = $this->customerService($apiClient);

        try {
            $service->applicableQueue(10, 21, 31);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($sharedNotFound, $exception);
            $this->assertSame(404, $exception->status);
        }
    }

    public function test_guest_join_generates_idempotency_key_and_stores_exact_ownership(): void
    {
        $idempotencyKey = '11111111-1111-4111-8111-111111111111';
        Str::createUuidsUsing(fn () => Uuid::fromString($idempotencyKey));
        $entry = $this->queueEntry();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('joinQueue')
            ->once()
            ->with(91, 31, null, $idempotencyKey)
            ->andReturn($entry);
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('store')
            ->once()
            ->with(Mockery::on(
                fn (GuestQueueOwnershipData $ownership): bool => $ownership->businessId === 10
                    && $ownership->branchId === 21
                    && $ownership->serviceId === 31
                    && $ownership->queueId === 91
                    && $ownership->entryId === 301
                    && $ownership->ticketNumber === 'A023'
                    && $ownership->guestToken() === 'inert-guest-token',
            ));
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        try {
            $result = $service->joinGuest(10, 21, 31, $this->todayQueue(serviceId: 31));
        } finally {
            Str::createUuidsNormally();
        }

        $this->assertInstanceOf(CustomerQueueTicketData::class, $result);
        $this->assertSame(10, $result->businessId);
        $this->assertSame(21, $result->branchId);
        $this->assertSame(31, $result->serviceId);
        $this->assertSame(91, $result->queueId);
        $this->assertSame(301, $result->entryId);
        $this->assertSame('A023', $result->ticketNumber);
        $this->assertSame('WAITING', $result->status);
    }

    public function test_shared_queue_join_sends_customer_selected_service(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('joinQueue')
            ->once()
            ->with(91, 31, null, '22222222-2222-4222-8222-222222222222')
            ->andReturn($this->queueEntry());
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('store')->once();
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        $service->joinGuest(
            10,
            21,
            31,
            $this->todayQueue(),
            '22222222-2222-4222-8222-222222222222',
        );
    }

    public function test_service_specific_join_reuses_caller_idempotency_key_unchanged(): void
    {
        $idempotencyKey = '33333333-3333-4333-8333-333333333333';
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('joinQueue')
            ->once()
            ->with(91, 31, null, $idempotencyKey)
            ->andReturn($this->queueEntry());
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('store')->once();
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        $service->joinGuest(
            10,
            21,
            31,
            $this->todayQueue(serviceId: 31),
            $idempotencyKey,
        );
    }

    #[DataProvider('preservedApiFailures')]
    public function test_join_failure_is_preserved_without_storing_ownership(int $status): void
    {
        $failure = new QueueFlowApiException('Join failed', $status);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('joinQueue')
            ->once()
            ->andThrow($failure);
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldNotReceive('store');
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        try {
            $service->joinGuest(
                10,
                21,
                31,
                $this->todayQueue(serviceId: 31),
                '44444444-4444-4444-8444-444444444444',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    #[DataProvider('missingGuestTokens')]
    public function test_missing_guest_token_fails_safely_without_storing_ownership(?string $guestToken): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('joinQueue')
            ->once()
            ->andReturn($this->queueEntry(guestToken: $guestToken));
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldNotReceive('store');
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        try {
            $service->joinGuest(
                10,
                21,
                31,
                $this->todayQueue(serviceId: 31),
                '55555555-5555-4555-8555-555555555555',
            );

            $this->fail('Expected GuestQueueOwnershipException was not thrown.');
        } catch (GuestQueueOwnershipException $exception) {
            $this->assertSame(
                'Guest queue ownership credentials were not returned.',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString('inert-guest-token', $exception->getMessage());
        }
    }

    public function test_owned_position_uses_exact_guest_token_and_returns_position(): void
    {
        $position = $this->queuePosition();
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('find')
            ->once()
            ->with(91, 301)
            ->andReturn($this->ownership());
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('queuePosition')
            ->once()
            ->with(91, 301, null, 'inert-guest-token')
            ->andReturn($position);
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        $result = $service->position(91, 301);

        $this->assertSame($position, $result);
    }

    public function test_missing_position_ownership_fails_locally_without_api_request(): void
    {
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('find')
            ->once()
            ->with(91, 301)
            ->andReturnNull();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldNotReceive('queuePosition');
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        $this->expectException(GuestQueueOwnershipException::class);
        $this->expectExceptionMessage('This browser session does not own this queue entry.');

        $service->position(91, 301);
    }

    public function test_owned_cancellation_uses_guest_token_and_retains_original_ownership(): void
    {
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('find')
            ->once()
            ->with(91, 301)
            ->andReturn($this->ownership());
        $ownershipStore->shouldNotReceive('store');
        $ownershipStore->shouldNotReceive('remove');
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('cancelQueueEntry')
            ->once()
            ->with(91, 301, null, 'inert-guest-token')
            ->andReturn($this->queueEntry(status: 'CANCELLED', guestToken: null));
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        $result = $service->cancel(91, 301);

        $this->assertSame('CANCELLED', $result->status);
        $this->assertSame('A023', $result->ticketNumber);
    }

    public function test_missing_cancellation_ownership_fails_locally_without_api_request(): void
    {
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('find')
            ->once()
            ->with(91, 301)
            ->andReturnNull();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldNotReceive('cancelQueueEntry');
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        $this->expectException(GuestQueueOwnershipException::class);
        $this->expectExceptionMessage('This browser session does not own this queue entry.');

        $service->cancel(91, 301);
    }

    #[DataProvider('preservedApiFailures')]
    public function test_cancellation_api_failures_remain_distinguishable(int $status): void
    {
        $failure = new QueueFlowApiException('Cancellation failed', $status);
        $ownershipStore = Mockery::mock(GuestQueueOwnershipStore::class);
        $ownershipStore->shouldReceive('find')->once()->andReturn($this->ownership());
        $ownershipStore->shouldNotReceive('store');
        $ownershipStore->shouldNotReceive('remove');
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('cancelQueueEntry')
            ->once()
            ->andThrow($failure);
        $service = new QueueFlowCustomerQueueService($apiClient, $ownershipStore);

        try {
            $service->cancel(91, 301);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($failure, $exception);
            $this->assertSame($status, $exception->status);
        }
    }

    /** @return array<string, array{?int}> */
    public static function nonNotFoundDiscoveryFailures(): array
    {
        return [
            'bad request' => [400],
            'unauthenticated' => [401],
            'forbidden' => [403],
            'conflict' => [409],
            'server failure' => [500],
        ];
    }

    /** @return array<string, array{int}> */
    public static function preservedApiFailures(): array
    {
        return [
            'forbidden' => [403],
            'conflict' => [409],
            'server failure' => [500],
        ];
    }

    /** @return array<string, array{?string}> */
    public static function missingGuestTokens(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
        ];
    }

    private function customerService(QueueFlowApiClient $apiClient): QueueFlowCustomerQueueService
    {
        return new QueueFlowCustomerQueueService(
            $apiClient,
            Mockery::mock(GuestQueueOwnershipStore::class),
        );
    }

    private function todayQueue(?int $serviceId = null): TodayQueueData
    {
        return new TodayQueueData(
            id: 91,
            branchId: 21,
            serviceId: $serviceId,
            name: $serviceId === null ? 'Walk-in Queue' : 'General Consultation',
            businessDate: '2030-04-15',
            ticketPrefix: 'A',
            status: 'OPEN',
        );
    }

    private function queueEntry(
        string $status = 'WAITING',
        #[\SensitiveParameter] ?string $guestToken = 'inert-guest-token',
    ): QueueEntryData {
        return QueueEntryData::fromArray([
            'id' => 301,
            'queueId' => 91,
            'serviceId' => 31,
            'userId' => null,
            'ticketSequence' => 23,
            'ticketNumber' => 'A023',
            'status' => $status,
            'joinedAt' => '2030-04-15T10:30:00+08:00',
            'guestToken' => $guestToken,
        ]);
    }

    private function ownership(): GuestQueueOwnershipData
    {
        return new GuestQueueOwnershipData(
            businessId: 10,
            branchId: 21,
            serviceId: 31,
            queueId: 91,
            entryId: 301,
            ticketNumber: 'A023',
            guestToken: 'inert-guest-token',
        );
    }

    private function queuePosition(): QueuePositionData
    {
        return new QueuePositionData(
            entryId: 301,
            queueId: 91,
            serviceId: 31,
            ticketSequence: 23,
            ticketNumber: 'A023',
            status: 'WAITING',
            peopleAhead: 2,
            estimatedWaitMinutes: 40,
        );
    }
}
