<?php

namespace Tests\Feature;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerHomeTest extends TestCase
{
    public function test_customer_home_uses_real_public_businesses_and_links(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                $this->business(10, 'Northstar Health'),
                $this->business(20, 'Harbour Services'),
            ]),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertViewIs('home')
            ->assertSee('Hi, Guest!')
            ->assertSee('No ticket yet.')
            ->assertSee('Open scanner')
            ->assertSee('LIVE QUEUE')
            ->assertSee('No active ticket')
            ->assertDontSee('href="'.route('businesses.show', 10).'"', false)
            ->assertDontSee('href="'.route('businesses.show', 20).'"', false)
            ->assertSee('data-customer-navigation', false)
            ->assertSee('data-active-destination="home"', false)
            ->assertSee('data-customer-bottom-navigation', false)
            ->assertSee('Home')
            ->assertSee('Places')
            ->assertSee('Tickets')
            ->assertSee('Account')
            ->assertSee('More')
            ->assertSee('href="'.route('account.show').'"', false)
            ->assertSee('href="'.route('more.show').'"', false)
            ->assertDontSee('href="#"', false)
            ->assertDontSee('Explore')
            ->assertDontSee('A023')
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses'
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(1);
    }

    public function test_customer_home_renders_business_empty_state(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('No ticket yet.')
            ->assertSee('No active ticket')
            ->assertSee('Open scanner');

        Http::assertSentCount(1);
    }

    public function test_customer_home_renders_real_owned_ticket_with_live_spring_position(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                $this->business(10, 'Northstar Health'),
            ]),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', 'WAITING', 5, 15),
            ),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response($this->board('A021', 'A022', ['A024'])),
            'http://localhost:8080/api/v1/queues/91/entries/301/qr-credential' => Http::response([
                'credential' => 'opaque-home-credential',
                'expiresAt' => '2026-10-01T10:15:30+08:00',
            ]),
        ]);

        $response = $this->withSession($this->ownershipSession([
            $this->ownership(91, 301, 'A023', 'raw-home-token'),
        ]))->get(route('home'));

        $response->assertOk()
            ->assertSee('Your ticket is active')
            ->assertSee('Northstar Health')
            ->assertSee('Your saved ticket')
            ->assertSee('A023')
            ->assertSee('5')
            ->assertSee('15 min')
            ->assertSee('data:image/svg+xml', false)
            ->assertSee('A021')
            ->assertSee('A022')
            ->assertSee('A024')
            ->assertDontSee('raw-home-token');
    }

    public function test_customer_home_uses_empty_board_values_without_fabricating_numbers(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', 'WAITING'),
            ),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response($this->board(null, null, [])),
            'http://localhost:8080/api/v1/queues/91/entries/301/qr-credential' => Http::response([
                'credential' => 'opaque-home-credential',
                'expiresAt' => '2026-10-01T10:15:30+08:00',
            ]),
        ]);

        $this->withSession($this->ownershipSession([
            $this->ownership(91, 301, 'A023', 'raw-home-token'),
        ]))->get(route('home'))
            ->assertOk()
            ->assertSee('>—</p>', false)
            ->assertDontSee('A021')
            ->assertDontSee('A022')
            ->assertDontSee('A024')
            ->assertDontSee('raw-home-token');
    }

    public function test_customer_home_keeps_ticket_usable_when_qr_issuance_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', 'WAITING'),
            ),
            'http://localhost:8080/api/v1/queues/91/board' => Http::response($this->board('A021', null, [])),
            'http://localhost:8080/api/v1/queues/91/entries/301/qr-credential' => Http::response([
                'message' => 'QR issuance unavailable',
            ], 503),
        ]);

        $this->withSession($this->ownershipSession([
            $this->ownership(91, 301, 'A023', 'raw-home-token'),
        ]))->get(route('home'))
            ->assertOk()
            ->assertSee('A023')
            ->assertSee('QR unavailable')
            ->assertDontSee('raw-home-token')
            ->assertDontSee('QR issuance unavailable');
    }

    #[DataProvider('terminalStatuses')]
    public function test_customer_home_does_not_present_terminal_owned_ticket_as_active(
        string $status,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', $status),
            ),
        ]);

        $response = $this->withSession($this->ownershipSession([
            $this->ownership(91, 301, 'A023', 'terminal-guest-token'),
        ]))->get(route('home'));

        $response->assertOk()
            ->assertSee('No ticket yet.')
            ->assertDontSee('Your ticket is active')
            ->assertDontSee('A023')
            ->assertDontSee('terminal-guest-token')
            ->assertSessionHas('queueflow.customer.entries.91:301.ticketNumber', 'A023');
    }

    public function test_customer_home_selects_waiting_ticket_after_older_cancelled_ownership(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
            'http://localhost:8080/api/v1/queues/90/entries/300/position' => Http::response(
                $this->position(90, 300, 'A022', 'CANCELLED'),
            ),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', 'WAITING'),
            ),
        ]);

        $response = $this->withSession($this->ownershipSession([
            $this->ownership(90, 300, 'A022', 'cancelled-guest-token'),
            $this->ownership(91, 301, 'A023', 'waiting-guest-token'),
        ]))->get(route('home'));

        $response->assertOk()
            ->assertSee('Your ticket is active')
            ->assertSee('A023')
            ->assertDontSee('A022')
            ->assertDontSee('cancelled-guest-token')
            ->assertDontSee('waiting-guest-token');
    }

    public function test_customer_home_selects_multiple_active_tickets_by_real_identifiers(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', 'CALLED'),
            ),
            'http://localhost:8080/api/v1/queues/92/entries/302/position' => Http::response(
                $this->position(92, 302, 'A024', 'WAITING'),
            ),
        ]);

        $response = $this->withSession($this->ownershipSession([
            $this->ownership(92, 302, 'A024', 'second-active-token'),
            $this->ownership(91, 301, 'A023', 'first-active-token'),
        ]))->get(route('home'));

        $response->assertOk()
            ->assertSee('Your ticket is active')
            ->assertSee('A023')
            ->assertDontSee('A024')
            ->assertDontSee('first-active-token')
            ->assertDontSee('second-active-token');
    }

    #[DataProvider('activeStatuses')]
    public function test_customer_home_recognizes_each_active_spring_status(string $status): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
            'http://localhost:8080/api/v1/queues/91/entries/301/position' => Http::response(
                $this->position(91, 301, 'A023', $status),
            ),
        ]);

        $this->withSession($this->ownershipSession([
            $this->ownership(91, 301, 'A023', 'active-guest-token'),
        ]))->get(route('home'))
            ->assertOk()
            ->assertSee('Your ticket is active')
            ->assertSee('A023')
            ->assertDontSee('active-guest-token');
    }

    public function test_customer_home_renders_safe_503_for_spring_5xx(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'message' => 'Internal Spring database detail',
            ], 500),
        ]);

        $response = $this->get(route('home'));

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertSee('data-customer-navigation', false)
            ->assertSee('Back to discovery')
            ->assertDontSee('Internal Spring database detail');
        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_customer_home_renders_safe_503_for_connection_failure(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::failedConnection(
                'Connection refused with backend URL',
            ),
        ]);

        $response = $this->get(route('home'));

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Connection refused')
            ->assertDontSee('Unable to connect to the QueueFlow API.');
        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === null,
        );
    }

    public function test_customer_home_has_the_root_route(): void
    {
        $this->assertSame(url('/'), route('home'));
    }

    /** @return array<string, array{string}> */
    public static function activeStatuses(): array
    {
        return [
            'waiting' => ['WAITING'],
            'called' => ['CALLED'],
            'serving' => ['SERVING'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function terminalStatuses(): array
    {
        return [
            'completed' => ['COMPLETED'],
            'cancelled' => ['CANCELLED'],
            'skipped' => ['SKIPPED'],
        ];
    }

    /** @return array<string, mixed> */
    private function business(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Customer-facing services.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return array<string, mixed>
     */
    private function ownershipSession(array $entries): array
    {
        $stored = [];

        foreach ($entries as $entry) {
            $stored[$entry['queueId'].':'.$entry['entryId']] = $entry;
        }

        return ['queueflow.customer.entries' => $stored];
    }

    /** @return array<string, mixed> */
    private function ownership(
        int $queueId,
        int $entryId,
        string $ticketNumber,
        #[\SensitiveParameter] string $guestToken,
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

    /** @return array<string, mixed> */
    private function position(
        int $queueId,
        int $entryId,
        string $ticketNumber,
        string $status,
        int $peopleAhead = 2,
        int $estimatedWaitMinutes = 40,
    ): array {
        return [
            'entryId' => $entryId,
            'queueId' => $queueId,
            'publicCode' => 'public-queue-code',
            'serviceId' => 31,
            'ticketSequence' => $entryId,
            'ticketNumber' => $ticketNumber,
            'status' => $status,
            'peopleAhead' => $peopleAhead,
            'estimatedWaitMinutes' => $estimatedWaitMinutes,
        ];
    }

    /** @return array<string, mixed> */
    private function board(?string $nowServing, ?string $calling, array $upcoming): array
    {
        return [
            'queueId' => 91,
            'name' => 'General Consultation',
            'status' => 'OPEN',
            'nowServing' => $nowServing,
            'calling' => $calling,
            'waitingCount' => count($upcoming),
            'upcomingTicketNumbers' => $upcoming,
        ];
    }
}
