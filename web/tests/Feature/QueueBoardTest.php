<?php

namespace Tests\Feature;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueueBoardTest extends TestCase
{
    public function test_public_queue_board_uses_public_code_route(): void
    {
        $this->assertSame(
            url('/queues/public-queue-code/board'),
            route('queues.board.show', 'public-queue-code'),
        );
    }

    public function test_public_queue_board_resolves_public_code_first_and_renders_real_open_state(): void
    {
        $this->travelTo('2026-10-06 14:45:00');
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code' => Http::response($this->resolvedQueue()),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response($this->board()),
        ]);

        $response = $this->get(route('queues.board.show', 'public-queue-code'));

        $response
            ->assertOk()
            ->assertViewIs('queue-board.show')
            ->assertSee('QueueFlow Clinic')
            ->assertSee('Downtown Branch')
            ->assertSee('General Consultation')
            ->assertSee('Consultation Queue')
            ->assertSee('OPEN')
            ->assertSee('NOW SERVING')
            ->assertSee('A001')
            ->assertSee('CALLING')
            ->assertSee('A002')
            ->assertSeeInOrder(['UP NEXT', 'A003', 'A004'])
            ->assertSee('2')
            ->assertSee('PEOPLE WAITING')
            ->assertSee('Rendered 2:45 PM')
            ->assertSee('Refresh board')
            ->assertSee('href="'.route('queues.board.show', 'public-queue-code').'"', false)
            ->assertSee('queueflow-realtime-base-url', false)
            ->assertSee('const publicCode = "public-queue-code";', false)
            ->assertSee('api/v1/public/queues/${encodeURIComponent(publicCode)}/events', false)
            ->assertDontSee('/api/v1/public/queues/91/events', false)
            ->assertDontSee('inert-spring-token');

        $requests = Http::recorded();
        $this->assertCount(2, $requests);
        $this->assertSame(
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code',
            $requests[0][0]->url(),
        );
        $this->assertSame(
            'http://localhost:8080/api/v1/queues/91/board',
            $requests[1][0]->url(),
        );

        foreach ($requests as [$request]) {
            $this->assertFalse($request->hasHeader('Authorization'));
        }
    }

    #[DataProvider('nonOpenQueueStatuses')]
    public function test_public_queue_board_renders_non_open_queue_statuses(string $status, string $label): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code' => Http::response(
                $this->resolvedQueue(queueStatus: $status),
            ),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response(
                $this->board(status: $status),
            ),
        ]);

        $this->get(route('queues.board.show', 'public-queue-code'))
            ->assertOk()
            ->assertSee($label);
    }

    public function test_public_queue_board_renders_empty_operational_state_intentionally(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code' => Http::response($this->resolvedQueue()),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response($this->board(
                nowServing: null,
                calling: null,
                waitingCount: 0,
                upcomingTicketNumbers: [],
            )),
        ]);

        $this->get(route('queues.board.show', 'public-queue-code'))
            ->assertOk()
            ->assertSee('NOW SERVING')
            ->assertSee('CALLING')
            ->assertSee('No upcoming tickets')
            ->assertSee('0')
            ->assertSee('PEOPLE WAITING')
            ->assertDontSee('average wait')
            ->assertDontSee('Counter');
    }

    public function test_public_queue_board_renders_shared_queue_without_service_label(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/shared-public-code' => Http::response($this->resolvedQueue(
                publicCode: 'shared-public-code',
                serviceId: null,
                serviceName: null,
                queueName: 'Main Queue',
            )),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response($this->board(name: 'Main Queue')),
        ]);

        $this->get(route('queues.board.show', 'shared-public-code'))
            ->assertOk()
            ->assertSee('Shared branch queue')
            ->assertSee('Main Queue')
            ->assertDontSee('General Consultation');
    }

    public function test_resolver_404_renders_safe_public_not_found_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/missing-public-code' => Http::response([
                'message' => 'Queue not found for public code',
            ], 404),
        ]);

        $this->get(route('queues.board.show', 'missing-public-code'))
            ->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Queue not found for public code');

        Http::assertSentCount(1);
    }

    public function test_board_404_renders_safe_public_not_found_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code' => Http::response($this->resolvedQueue()),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response([
                'message' => 'Queue not found with id: 91',
            ], 404),
        ]);

        $this->get(route('queues.board.show', 'public-queue-code'))
            ->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Queue not found with id: 91');
    }

    public function test_backend_5xx_renders_safe_unavailable_response_without_internal_details(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code' => Http::response([
                'message' => 'Internal Spring database detail',
            ], 500),
        ]);

        $this->get(route('queues.board.show', 'public-queue-code'))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring database detail');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_board_connection_failure_renders_safe_unavailable_response_without_internal_details(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/public/queues/resolve/public-queue-code' => Http::response($this->resolvedQueue()),
            'http://localhost:8080/api/v1/queues/91/board' => Http::failedConnection(
                'Connection refused with backend URL',
            ),
        ]);

        $this->get(route('queues.board.show', 'public-queue-code'))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Connection refused')
            ->assertDontSee('Unable to connect to the QueueFlow API.');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === null,
        );
    }

    /** @return array<string, array{string, string}> */
    public static function nonOpenQueueStatuses(): array
    {
        return [
            'paused' => ['PAUSED', 'PAUSED'],
            'closed' => ['CLOSED', 'CLOSED'],
        ];
    }

    /** @return array<string, mixed> */
    private function resolvedQueue(
        string $publicCode = 'public-queue-code',
        ?int $serviceId = 501,
        ?string $serviceName = 'General Consultation',
        string $queueName = 'Consultation Queue',
        string $queueStatus = 'OPEN',
    ): array {
        return [
            'publicCode' => $publicCode,
            'businessId' => 10,
            'businessName' => 'QueueFlow Clinic',
            'branchId' => 101,
            'branchName' => 'Downtown Branch',
            'serviceId' => $serviceId,
            'serviceName' => $serviceName,
            'queueId' => 91,
            'queueName' => $queueName,
            'queueStatus' => $queueStatus,
        ];
    }

    /**
     * @param  list<string>  $upcomingTicketNumbers
     * @return array<string, mixed>
     */
    private function board(
        string $name = 'Consultation Queue',
        string $status = 'OPEN',
        ?string $nowServing = 'A001',
        ?string $calling = 'A002',
        int $waitingCount = 2,
        array $upcomingTicketNumbers = ['A003', 'A004'],
    ): array {
        return [
            'queueId' => 91,
            'name' => $name,
            'status' => $status,
            'nowServing' => $nowServing,
            'calling' => $calling,
            'waitingCount' => $waitingCount,
            'upcomingTicketNumbers' => $upcomingTicketNumbers,
        ];
    }
}
