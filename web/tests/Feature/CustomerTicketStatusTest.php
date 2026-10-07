<?php

namespace Tests\Feature;

use App\Data\QueuePositionData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowCustomerQueueService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerTicketStatusTest extends TestCase
{
    public function test_my_tickets_renders_empty_state_without_mock_ticket(): void
    {
        $response = $this->get(route('tickets.show'));

        $response->assertOk()
            ->assertViewIs('tickets.index')
            ->assertSee('Tickets')
            ->assertSee('No tickets yet')
            ->assertSee('href="'.route('home').'"', false)
            ->assertDontSee('A023')
            ->assertDontSee('Mock preview');
    }

    public function test_my_tickets_lists_multiple_owned_tickets_with_credential_free_links(): void
    {
        $this->fakePositions([
            301 => [91, 'A023', 'WAITING'],
            401 => [92, 'B014', 'CALLED'],
        ]);

        $response = $this->withSession($this->ownershipSession([
            $this->ownership(91, 301, 'A023', 'first-raw-guest-token'),
            $this->ownership(92, 401, 'B014', 'second-raw-guest-token'),
        ]))->get(route('tickets.show'));

        $response->assertOk()
            ->assertViewIs('tickets.index')
            ->assertSeeInOrder(['A023', 'B014'])
            ->assertSee('href="'.route('queue-entries.show', [91, 301]).'"', false)
            ->assertSee('href="'.route('queue-entries.show', [92, 401]).'"', false)
            ->assertDontSee('first-raw-guest-token')
            ->assertDontSee('second-raw-guest-token')
            ->assertDontSee('Central branch')
            ->assertDontSee('guestToken')
            ->assertDontSee('Idempotency-Key')
            ->assertDontSee('queueflow.customer.join_attempts');
    }

    public function test_my_tickets_classifies_live_spring_statuses_and_retains_terminal_ownership(): void
    {
        $this->fakePositions([
            301 => [91, 'A001', 'WAITING'],
            302 => [92, 'A002', 'CALLED'],
            303 => [93, 'A003', 'SERVING'],
            304 => [94, 'A004', 'COMPLETED'],
            305 => [95, 'A005', 'CANCELLED'],
            306 => [96, 'A006', 'SKIPPED'],
        ]);
        $entries = [
            $this->ownership(91, 301, 'A001', 'waiting-token'),
            $this->ownership(92, 302, 'A002', 'called-token'),
            $this->ownership(93, 303, 'A003', 'serving-token'),
            $this->ownership(94, 304, 'A004', 'completed-token'),
            $this->ownership(95, 305, 'A005', 'cancelled-token'),
            $this->ownership(96, 306, 'A006', 'skipped-token'),
        ];

        $response = $this->withSession($this->ownershipSession($entries))
            ->get(route('tickets.show'));

        $response->assertOk()
            ->assertViewHas('activeTickets', fn (array $tickets): bool => count($tickets) === 3)
            ->assertViewHas('historyTickets', fn (array $tickets): bool => count($tickets) === 3)
            ->assertSeeInOrder([
                'Active tickets',
                'WAITING',
                'A001',
                'CALLED',
                'A002',
                'SERVING',
                'A003',
                'Ticket history',
                'COMPLETED',
                'A004',
                'CANCELLED',
                'A005',
                'SKIPPED',
                'A006',
            ])
            ->assertDontSee('waiting-token')
            ->assertDontSee('called-token')
            ->assertDontSee('serving-token')
            ->assertDontSee('completed-token')
            ->assertDontSee('cancelled-token')
            ->assertDontSee('skipped-token')
            ->assertSessionHas('queueflow.customer.entries.94:304.guestToken', 'completed-token')
            ->assertSessionHas('queueflow.customer.entries.95:305.guestToken', 'cancelled-token')
            ->assertSessionHas('queueflow.customer.entries.96:306.guestToken', 'skipped-token');

        Http::assertSentCount(6);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader(
            'X-Guest-Token',
            'cancelled-token',
        ));
    }

    public function test_ticket_index_position_failure_uses_safe_customer_error_and_preserves_ownership(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('classifiedOwnedTickets')
            ->once()
            ->andThrow(new QueueFlowApiException('Internal Spring position detail', 500));

        $response = $this->withSession($this->ownershipSession())
            ->get(route('tickets.show'));

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring position detail')
            ->assertDontSee('raw-guest-token')
            ->assertSessionHas('queueflow.customer.entries.91:301.guestToken', 'raw-guest-token');
        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_ticket_routes_are_public_numeric_and_credential_free(): void
    {
        $indexRoute = Route::getRoutes()->getByName('tickets.show');
        $detailRoute = Route::getRoutes()->getByName('queue-entries.show');

        $this->assertNotNull($indexRoute);
        $this->assertSame('ticket', $indexRoute->uri());
        $this->assertNotContains('queueflow.staff.auth', $indexRoute->gatherMiddleware());
        $this->assertNotNull($detailRoute);
        $this->assertSame('queues/{queueId}/entries/{entryId}', $detailRoute->uri());
        $this->assertSame('[0-9]+', $detailRoute->wheres['queueId']);
        $this->assertSame('[0-9]+', $detailRoute->wheres['entryId']);
        $this->assertNotContains('queueflow.staff.auth', $detailRoute->gatherMiddleware());
        $this->assertNotContains('queueflow.business.member', $detailRoute->gatherMiddleware());
        $this->assertSame(
            url('/queues/91/entries/301'),
            route('queue-entries.show', [91, 301]),
        );
    }

    public function test_owned_detail_uses_exact_identifiers_and_spring_position_values(): void
    {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->with(91, 301)
            ->andReturn($this->position(peopleAhead: 7, estimatedWaitMinutes: 83));

        $response = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $response->assertOk()
            ->assertViewIs('tickets.show')
            ->assertSeeInOrder(['Ticket number', 'A023', 'Current status'])
            ->assertSee('WAITING')
            ->assertSee('A023')
            ->assertSee("You're in the queue")
            ->assertSee('People ahead')
            ->assertSee('7')
            ->assertSee('Estimated wait')
            ->assertSee('83 minutes')
            ->assertSee('data-demo-qr', false)
            ->assertSee('Saved in this browser')
            ->assertSee('Owned ticket')
            ->assertDontSee('Central branch')
            ->assertDontSee('Ready now')
            ->assertDontSee('raw-guest-token')
            ->assertDontSee('guestToken')
            ->assertDontSee('Idempotency-Key')
            ->assertDontSee('queueflow.customer.join_attempts')
            ->assertDontSee('Authorization')
            ->assertDontSee('Bearer');
    }

    public function test_missing_local_ownership_is_safe_without_position_request(): void
    {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldNotReceive('position');

        $response = $this->get(route('queue-entries.show', [91, 301]));

        $response->assertNotFound()
            ->assertSee('This ticket is not available in this browser/session.')
            ->assertDontSee('This browser session does not own this queue entry.')
            ->assertDontSee('guest token');
    }

    #[DataProvider('knownStatuses')]
    public function test_known_status_renders_safe_customer_wording(
        string $status,
        string $label,
        string $heading,
    ): void {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->with(91, 301)
            ->andReturn($this->position(status: $status));

        $response = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $response->assertOk()
            ->assertSee($label)
            ->assertSee($heading);

        if ($status === 'WAITING') {
            $response->assertSee('People ahead')
                ->assertSee('Estimated wait');
        } else {
            $response->assertDontSee('People ahead')
                ->assertDontSee('Estimated wait')
                ->assertDontSee('Waiting position is not shown for this ticket status.');

            if (in_array($status, ['COMPLETED', 'CANCELLED', 'SKIPPED'], true)) {
                $response->assertDontSee('QueueFlowSseConnection', false);
            }
        }
    }

    public function test_unknown_status_renders_generic_wording_without_raw_status(): void
    {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->andReturn($this->position(status: 'FUTURE_INTERNAL_STATE'));

        $response = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $response->assertOk()
            ->assertSee('STATUS UPDATE')
            ->assertSee('Status unavailable')
            ->assertDontSee('FUTURE_INTERNAL_STATE')
            ->assertDontSee('People ahead')
            ->assertDontSee('Estimated wait');
    }

    public function test_refresh_is_plain_get_to_same_credential_free_detail_url(): void
    {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->andReturn($this->position());
        $detailUrl = route('queue-entries.show', [91, 301]);

        $response = $this->withSession($this->ownershipSession())->get($detailUrl);

        $response->assertOk()
            ->assertSee('Refresh status')
            ->assertSee('href="'.$detailUrl.'"', false)
            ->assertDontSee('setInterval', false)
            ->assertDontSee('WebSocket', false)
            ->assertSee('QueueFlowSseConnection', false)
            ->assertSee('public-queue-code', false)
            ->assertDontSee('/api/v1/public/queues/91/events', false)
            ->assertDontSee('guestToken')
            ->assertDontSee('idempotencyKey');
        $this->assertStringNotContainsString('?', $detailUrl);
    }

    public function test_waiting_ticket_renders_csrf_protected_credential_free_cancel_form(): void
    {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->with(91, 301)
            ->andReturn($this->position(status: 'WAITING'));
        $cancelUrl = route('queue-entries.cancel', [91, 301]);

        $response = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $response->assertOk()
            ->assertSee('Cancel ticket')
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.$cancelUrl.'"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('raw-guest-token')
            ->assertDontSee('guestToken')
            ->assertDontSee('X-Guest-Token')
            ->assertDontSee('Idempotency-Key')
            ->assertDontSee('Authorization')
            ->assertDontSee('Bearer')
            ->assertDontSee('queueflow.customer.join_attempts');
        $this->assertStringNotContainsString('?', $cancelUrl);
    }

    #[DataProvider('nonCancellableStatuses')]
    public function test_non_waiting_ticket_does_not_render_cancel_form(string $status): void
    {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->andReturn($this->position(status: $status));

        $response = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $response->assertOk()
            ->assertDontSee('Cancel ticket')
            ->assertDontSee(route('queue-entries.cancel', [91, 301]));
    }

    #[DataProvider('safeApiFailures')]
    public function test_ticket_api_failure_is_customer_safe(
        ?int $apiStatus,
        int $responseStatus,
        string $safeMessage,
    ): void {
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')
            ->once()
            ->with(91, 301)
            ->andThrow(new QueueFlowApiException(
                'Internal Spring ownership and backend URL detail',
                $apiStatus,
            ));

        $response = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $response->assertStatus($responseStatus)
            ->assertSee($safeMessage)
            ->assertDontSee('Internal Spring ownership and backend URL detail')
            ->assertDontSee('raw-guest-token')
            ->assertSessionHas('queueflow.customer.entries.91:301.guestToken', 'raw-guest-token');
    }

    public function test_customer_navigation_marks_my_tickets_active_on_index_and_detail(): void
    {
        $indexResponse = $this->get(route('tickets.show'));
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')->once()->andReturn($this->position());
        $detailResponse = $this->withSession($this->ownershipSession())
            ->get(route('queue-entries.show', [91, 301]));

        $indexResponse->assertSee('data-active-destination="ticket"', false)
            ->assertSee('Tickets')
            ->assertSee('href="'.route('tickets.show').'"', false);
        $detailResponse->assertSee('data-active-destination="ticket"', false)
            ->assertSee('Tickets')
            ->assertSee('href="'.route('tickets.show').'"', false);
    }

    /** @return array<string, array{string, string, string}> */
    public static function knownStatuses(): array
    {
        return [
            'waiting' => ['WAITING', 'WAITING', "You're in the queue"],
            'called' => ['CALLED', 'CALLED', "It's your turn"],
            'serving' => ['SERVING', 'SERVING', 'Now serving'],
            'completed' => ['COMPLETED', 'COMPLETED', 'Completed'],
            'cancelled' => ['CANCELLED', 'CANCELLED', 'Cancelled'],
            'skipped' => ['SKIPPED', 'SKIPPED', 'Skipped'],
        ];
    }

    /** @return array<string, array{?int, int, string}> */
    public static function safeApiFailures(): array
    {
        return [
            'forbidden' => [403, 403, 'We could not verify this ticket for this browser/session.'],
            'not found' => [404, 404, 'This ticket is no longer available.'],
            'server failure' => [500, 503, 'QueueFlow is temporarily unavailable. Please try again later.'],
            'connection failure' => [null, 503, 'QueueFlow is temporarily unavailable. Please try again later.'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function nonCancellableStatuses(): array
    {
        return [
            'called' => ['CALLED'],
            'serving' => ['SERVING'],
            'completed' => ['COMPLETED'],
            'cancelled' => ['CANCELLED'],
            'skipped' => ['SKIPPED'],
            'unknown' => ['FUTURE_INTERNAL_STATE'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $entries
     * @return array<string, mixed>
     */
    private function ownershipSession(?array $entries = null): array
    {
        $entries ??= [$this->ownership()];
        $stored = [];

        foreach ($entries as $entry) {
            $stored[$entry['queueId'].':'.$entry['entryId']] = $entry;
        }

        return ['queueflow.customer.entries' => $stored];
    }

    /** @return array<string, mixed> */
    private function ownership(
        int $queueId = 91,
        int $entryId = 301,
        string $ticketNumber = 'A023',
        #[\SensitiveParameter] string $guestToken = 'raw-guest-token',
    ): array {
        return [
            'businessId' => 10,
            'branchId' => 21,
            'serviceId' => 31,
            'queueId' => $queueId,
            'entryId' => $entryId,
            'ticketNumber' => $ticketNumber,
            'guestToken' => $guestToken,
        ];
    }

    private function position(
        string $status = 'WAITING',
        int $peopleAhead = 2,
        int $estimatedWaitMinutes = 40,
    ): QueuePositionData {
        return new QueuePositionData(
            entryId: 301,
            queueId: 91,
            publicCode: 'public-queue-code',
            serviceId: 31,
            ticketSequence: 23,
            ticketNumber: 'A023',
            status: $status,
            peopleAhead: $peopleAhead,
            estimatedWaitMinutes: $estimatedWaitMinutes,
        );
    }

    /** @param  array<int, array{int, string, string}>  $tickets */
    private function fakePositions(array $tickets): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) use ($tickets) {
            foreach ($tickets as $entryId => [$queueId, $ticketNumber, $status]) {
                if ($request->url() === "http://localhost:8080/api/v1/queues/{$queueId}/entries/{$entryId}/position") {
                    return Http::response([
                        'entryId' => $entryId,
                        'queueId' => $queueId,
                        'publicCode' => 'public-queue-code',
                        'serviceId' => 31,
                        'ticketSequence' => $entryId,
                        'ticketNumber' => $ticketNumber,
                        'status' => $status,
                        'peopleAhead' => 2,
                        'estimatedWaitMinutes' => 40,
                    ]);
                }
            }

            throw new \RuntimeException("Unexpected QueueFlow request: {$request->method()} {$request->url()}");
        });
    }
}
