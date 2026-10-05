<?php

namespace Tests\Unit\Services;

use App\Data\QueueData;
use App\Data\QueueStaffEntryData;
use App\Data\StaffDashboardData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use App\Services\QueueFlowQueueService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueueFlowQueueServiceTest extends TestCase
{
    public function test_shared_queue_creation_delegates_with_the_server_side_token(): void
    {
        $queue = $this->queueData();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('createQueue')
            ->once()
            ->with(10, 21, 'inert-staff-token', null, 'Walk-in Queue', 'A')
            ->andReturn($queue);
        [$service] = $this->authenticatedService($apiClient);

        $result = $service->createQueue(10, 21, null, 'Walk-in Queue', 'A');

        $this->assertSame($queue, $result);
    }

    public function test_service_specific_queue_creation_passes_the_service_id(): void
    {
        $queue = $this->queueData(serviceId: 31);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('createQueue')
            ->once()
            ->with(10, 21, 'inert-staff-token', 31, 'Consultations', 'C')
            ->andReturn($queue);
        [$service] = $this->authenticatedService($apiClient);

        $result = $service->createQueue(10, 21, 31, 'Consultations', 'C');

        $this->assertSame($queue, $result);
    }

    public function test_staff_dashboard_delegates_with_the_server_side_token(): void
    {
        $dashboard = $this->staffDashboardData();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('staffDashboard')
            ->once()
            ->with(10, 21, 'inert-staff-token')
            ->andReturn($dashboard);
        [$service] = $this->authenticatedService($apiClient);

        $result = $service->staffDashboard(10, 21);

        $this->assertSame($dashboard, $result);
    }

    #[DataProvider('queueEntryActions')]
    public function test_queue_entry_actions_delegate_with_the_server_side_token(
        string $method,
        bool $requiresEntryId,
    ): void {
        $entry = $this->queueStaffEntryData();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $expectation = $apiClient->shouldReceive($method)->once();

        if ($requiresEntryId) {
            $expectation->with(
                91,
                301,
                'inert-staff-token',
                '11111111-1111-4111-8111-111111111111',
            );
        } else {
            $expectation->with(
                91,
                'inert-staff-token',
                '11111111-1111-4111-8111-111111111111',
            );
        }

        $expectation->andReturn($entry);
        [$service] = $this->authenticatedService($apiClient);

        $result = $requiresEntryId
            ? $service->{$method}(91, 301, '11111111-1111-4111-8111-111111111111')
            : $service->{$method}(91, '11111111-1111-4111-8111-111111111111');

        $this->assertSame($entry, $result);
    }

    #[DataProvider('queueStateActions')]
    public function test_queue_state_actions_delegate_with_the_server_side_token(
        string $method,
    ): void {
        $queue = $this->queueData();
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive($method)
            ->once()
            ->with(91, 'inert-staff-token')
            ->andReturn($queue);
        [$service] = $this->authenticatedService($apiClient);

        $result = $service->{$method}(91);

        $this->assertSame($queue, $result);
    }

    public function test_401_clears_only_queueflow_authentication_state(): void
    {
        $apiException = new QueueFlowApiException('Authentication is required.', 401);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('pauseQueue')
            ->once()
            ->with(91, 'inert-staff-token')
            ->andThrow($apiException);
        [$service, $session] = $this->authenticatedService($apiClient);
        $session->put('unrelated', 'preserved');

        try {
            $service->pauseQueue(91);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertNull($session->get('queueflow.auth'));
            $this->assertSame('preserved', $session->get('unrelated'));
        }
    }

    public function test_403_preserves_valid_authentication_state(): void
    {
        $apiException = new QueueFlowApiException('Internal authorization detail.', 403);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('pauseQueue')
            ->once()
            ->with(91, 'inert-staff-token')
            ->andThrow($apiException);
        [$service, $session] = $this->authenticatedService($apiClient);

        try {
            $service->pauseQueue(91);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        }
    }

    public function test_connection_failure_uses_the_existing_safe_error_and_preserves_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/queues' => Http::failedConnection(
                'Connection refused with internal details.',
            ),
        ]);
        [$service, $session] = $this->authenticatedService(
            app(QueueFlowApiClient::class),
        );

        try {
            $service->createQueue(10, 21, null, 'Walk-in Queue', 'A');

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame('Unable to connect to the QueueFlow API.', $exception->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
            $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        }
    }

    public function test_staff_dashboard_401_clears_only_queueflow_authentication_state(): void
    {
        $apiException = new QueueFlowApiException('Authentication is required.', 401);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('staffDashboard')
            ->once()
            ->with(10, 21, 'inert-staff-token')
            ->andThrow($apiException);
        [$service, $session] = $this->authenticatedService($apiClient);
        $session->put('unrelated', 'preserved');

        try {
            $service->staffDashboard(10, 21);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertNull($session->get('queueflow.auth'));
            $this->assertSame('preserved', $session->get('unrelated'));
        }
    }

    public function test_staff_dashboard_403_preserves_valid_authentication_state(): void
    {
        $apiException = new QueueFlowApiException('Internal authorization detail.', 403);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('staffDashboard')
            ->once()
            ->with(10, 21, 'inert-staff-token')
            ->andThrow($apiException);
        [$service, $session] = $this->authenticatedService($apiClient);

        try {
            $service->staffDashboard(10, 21);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        }
    }

    public function test_staff_dashboard_500_preserves_valid_authentication_state(): void
    {
        $apiException = new QueueFlowApiException('Internal upstream detail.', 500);
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('staffDashboard')
            ->once()
            ->with(10, 21, 'inert-staff-token')
            ->andThrow($apiException);
        [$service, $session] = $this->authenticatedService($apiClient);

        try {
            $service->staffDashboard(10, 21);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        }
    }

    public function test_staff_dashboard_connection_failure_is_safe_and_preserves_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10/branches/21/staff/dashboard' => Http::failedConnection(
                'Connection refused with internal details.',
            ),
        ]);
        [$service, $session] = $this->authenticatedService(
            app(QueueFlowApiClient::class),
        );

        try {
            $service->staffDashboard(10, 21);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame('Unable to connect to the QueueFlow API.', $exception->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $exception->getPrevious());
            $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        }
    }

    #[DataProvider('preservedQueueFailures')]
    public function test_queue_failures_remain_distinguishable_for_the_web_layer(
        ?int $status,
        array $validationErrors,
    ): void {
        $apiException = new QueueFlowApiException(
            'Internal downstream detail.',
            $status,
            $validationErrors,
        );
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('createQueue')
            ->once()
            ->with(10, 21, 'inert-staff-token', null, 'Walk-in Queue', 'A')
            ->andThrow($apiException);
        [$service, $session] = $this->authenticatedService($apiClient);

        try {
            $service->createQueue(10, 21, null, 'Walk-in Queue', 'A');

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertSame($status, $exception->status);
            $this->assertSame($validationErrors, $exception->validationErrors);
            $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        }
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function queueEntryActions(): array
    {
        return [
            'call next' => ['callNextQueueEntry', false],
            'recall' => ['recallQueueEntry', true],
            'start serving' => ['startServingQueueEntry', true],
            'complete' => ['completeQueueEntry', true],
            'skip' => ['skipQueueEntry', true],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function queueStateActions(): array
    {
        return [
            'pause' => ['pauseQueue'],
            'resume' => ['resumeQueue'],
            'close' => ['closeQueue'],
            'reopen' => ['reopenQueue'],
        ];
    }

    /**
     * @return array<string, array{?int, array<string, string>}>
     */
    public static function preservedQueueFailures(): array
    {
        return [
            '400 validation' => [400, ['name' => 'Queue name is required']],
            '404 not found' => [404, []],
            '409 conflict' => [409, []],
            '500 server failure' => [500, []],
        ];
    }

    /**
     * @return array{QueueFlowQueueService, Session}
     */
    private function authenticatedService(QueueFlowApiClient $apiClient): array
    {
        $session = app(Session::class);
        $session->start();
        $session->put('queueflow.auth', [
            'token' => 'inert-staff-token',
            'user' => [],
            'memberships' => [],
        ]);

        return [
            new QueueFlowQueueService(
                $apiClient,
                new QueueFlowAuthService($apiClient, $session),
            ),
            $session,
        ];
    }

    private function queueData(?int $serviceId = null): QueueData
    {
        return QueueData::fromArray([
            'id' => 91,
            'branchId' => 21,
            'serviceId' => $serviceId,
            'name' => 'Walk-in Queue',
            'businessDate' => '2030-04-15',
            'ticketPrefix' => 'A',
            'nextTicketSequence' => 1,
            'status' => 'OPEN',
            'openedAt' => '2030-04-15T08:00:00+08:00',
            'closedAt' => null,
            'createdAt' => '2030-04-15T08:00:00+08:00',
            'updatedAt' => '2030-04-15T08:00:00+08:00',
        ]);
    }

    private function queueStaffEntryData(): QueueStaffEntryData
    {
        return QueueStaffEntryData::fromArray([
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
    }

    private function staffDashboardData(): StaffDashboardData
    {
        return StaffDashboardData::fromArray([
            'businessId' => 10,
            'branchId' => 21,
            'businessDate' => '2030-04-15',
            'queues' => [],
        ]);
    }
}
