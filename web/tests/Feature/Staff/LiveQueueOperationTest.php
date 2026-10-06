<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LiveQueueOperationTest extends TestCase
{
    #[DataProvider('successfulOperations')]
    public function test_operation_delegates_to_spring_and_preserves_selected_queue(
        string $routeName,
        string $springPath,
        string $successMessage,
        ?int $entryId,
        string $responseType,
        string $resultStatus,
        bool $requiresIdempotencyKey,
    ): void {
        $actionUrl = "http://localhost:8080/api/v1/queues/91/staff/{$springPath}";
        $actionResponse = $responseType === 'queue'
            ? $this->queue($resultStatus)
            : $this->entry($resultStatus);

        $this->fakeWorkspace([
            $actionUrl => Http::response($actionResponse),
        ]);

        $response = $this->authenticated()->post(route(
            $routeName,
            $this->operationRouteParameters($entryId),
        ), $this->operationPayload($requiresIdempotencyKey));

        $response->assertRedirect($this->selectedQueueUrl())
            ->assertSessionHas('status', $successMessage);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === $actionUrl
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token')
            && $request->hasHeader('Idempotency-Key') === $requiresIdempotencyKey
            && $request->data() === []);
    }

    public function test_following_redirect_get_refreshes_dashboard_without_replaying_mutation(): void
    {
        $actionUrl = 'http://localhost:8080/api/v1/queues/91/staff/call-next';
        $this->fakeWorkspace([
            $actionUrl => Http::response($this->entry('CALLED')),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.call-next', $this->operationRouteParameters()), [
                'idempotency_key' => '11111111-1111-4111-8111-111111111111',
            ])
            ->assertRedirect($this->selectedQueueUrl());

        $this->get($this->selectedQueueUrl())
            ->assertOk()
            ->assertSee('Next ticket called successfully.');

        Http::assertSentCount(8);
        $this->assertSame(1, collect(Http::recorded())
            ->filter(fn (array $record): bool => $record[0]->url() === $actionUrl)
            ->count());
    }

    public function test_mutation_409_redirects_with_safe_error_and_renders_no_backend_detail(): void
    {
        $actionUrl = 'http://localhost:8080/api/v1/queues/91/staff/pause';
        $this->fakeWorkspace([
            $actionUrl => Http::response([
                'message' => 'Internal stale queue status detail',
            ], 409),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.pause', $this->operationRouteParameters()))
            ->assertRedirect($this->selectedQueueUrl())
            ->assertSessionHas(
                'error',
                'The queue changed before this action completed. The latest state has been refreshed.',
            )
            ->assertSessionMissing('status');

        $this->get($this->selectedQueueUrl())
            ->assertOk()
            ->assertSee('The queue changed before this action completed. The latest state has been refreshed.')
            ->assertDontSee('Internal stale queue status detail');
    }

    public function test_mutation_401_uses_existing_session_expiry_behavior(): void
    {
        $this->fakeWorkspace([
            'http://localhost:8080/api/v1/queues/91/staff/pause' => Http::response([
                'message' => 'Internal expired token detail',
            ], 401),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.pause', $this->operationRouteParameters()))
            ->assertRedirect(route('staff.login'))
            ->assertSessionHas('error', 'Your session has expired. Please sign in again.')
            ->assertSessionMissing('queueflow.auth');
    }

    public function test_mutation_403_preserves_authentication_and_returns_safe_forbidden_response(): void
    {
        $this->fakeWorkspace([
            'http://localhost:8080/api/v1/queues/91/staff/pause' => Http::response([
                'message' => 'Internal branch authorization detail',
            ], 403),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.pause', $this->operationRouteParameters()))
            ->assertForbidden()
            ->assertSee('You are not authorized to access this staff resource.')
            ->assertDontSee('Internal branch authorization detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    public function test_mutation_404_uses_safe_not_found_behavior(): void
    {
        $this->fakeWorkspace([
            'http://localhost:8080/api/v1/queues/91/staff/pause' => Http::response([
                'message' => 'Internal missing queue detail',
            ], 404),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.pause', $this->operationRouteParameters()))
            ->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Internal missing queue detail');
    }

    public function test_mutation_500_is_safe_and_preserves_authentication(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fakeWorkspace([
            'http://localhost:8080/api/v1/queues/91/staff/pause' => Http::response([
                'message' => 'Internal queue failure',
            ], 500),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.pause', $this->operationRouteParameters()))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal queue failure')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_mutation_connection_failure_is_safe(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fakeWorkspace([
            'http://localhost:8080/api/v1/queues/91/staff/pause' => Http::failedConnection(
                'Internal connection detail',
            ),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.pause', $this->operationRouteParameters()))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal connection detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    public function test_paused_queue_shows_resume_and_close_without_pause_or_call_next(): void
    {
        $this->fakeWorkspace(dashboard: $this->dashboard([
            $this->dashboardQueue('PAUSED', waiting: [$this->entry('WAITING')]),
        ]));

        $response = $this->authenticated()->get($this->selectedQueueUrl());

        $response->assertOk()
            ->assertSee('action="'.route('staff.live-queues.resume', $this->operationRouteParameters()).'"', false)
            ->assertSee('action="'.route('staff.live-queues.close', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.pause', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.call-next', $this->operationRouteParameters()).'"', false);
    }

    public function test_closed_queue_has_no_lifecycle_mutation_forms(): void
    {
        $this->fakeWorkspace(dashboard: $this->dashboard([
            $this->dashboardQueue(
                'CLOSED',
                [$this->entry('WAITING')],
                $this->entry('SERVING'),
                $this->entry('CALLED'),
            ),
        ]));

        $response = $this->authenticated()->get($this->selectedQueueUrl());

        $response->assertOk()
            ->assertSee('action="'.route('staff.live-queues.reopen', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.pause', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.resume', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.close', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.call-next', $this->operationRouteParameters()).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.complete', $this->operationRouteParameters(301)).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.start', $this->operationRouteParameters(301)).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.recall', $this->operationRouteParameters(301)).'"', false)
            ->assertDontSee('action="'.route('staff.live-queues.skip', $this->operationRouteParameters(301)).'"', false);
    }

    public function test_protected_entry_mutation_forms_render_idempotency_keys(): void
    {
        $this->fakeWorkspace(dashboard: $this->dashboard([
            $this->dashboardQueue(
                'OPEN',
                [],
                $this->entry('SERVING'),
                $this->entry('CALLED'),
            ),
        ]));

        $response = $this->authenticated()->get($this->selectedQueueUrl());

        $response->assertOk();

        $content = $response->getContent();
        preg_match_all(
            '/name="idempotency_key" value="([0-9a-f-]{36})"/',
            $content,
            $matches,
        );

        $this->assertCount(4, $matches[1]);
        $this->assertCount(4, array_unique($matches[1]));
        foreach ($matches[1] as $idempotencyKey) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                $idempotencyKey,
            );
        }

    }

    public function test_call_next_form_renders_an_idempotency_key(): void
    {
        $this->fakeWorkspace(dashboard: $this->dashboard([
            $this->dashboardQueue('OPEN', waiting: [$this->entry('WAITING')]),
        ]));

        $response = $this->authenticated()->get($this->selectedQueueUrl());

        $response->assertOk();

        preg_match_all(
            '/name="idempotency_key" value="([0-9a-f-]{36})"/',
            $response->getContent(),
            $matches,
        );

        $this->assertCount(1, $matches[1]);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $matches[1][0],
        );
    }

    public function test_duplicate_posts_forward_the_same_supplied_idempotency_key(): void
    {
        $actionUrl = 'http://localhost:8080/api/v1/queues/91/staff/call-next';
        $this->fakeWorkspace([
            $actionUrl => Http::response($this->entry('CALLED')),
        ]);

        $payload = ['idempotency_key' => '11111111-1111-4111-8111-111111111111'];

        $this->authenticated()
            ->post(route('staff.live-queues.call-next', $this->operationRouteParameters()), $payload)
            ->assertRedirect($this->selectedQueueUrl());

        $this->authenticated()
            ->post(route('staff.live-queues.call-next', $this->operationRouteParameters()), $payload)
            ->assertRedirect($this->selectedQueueUrl());

        $mutationRequests = collect(Http::recorded())
            ->map(static fn (array $record): Request => $record[0])
            ->filter(static fn (Request $request): bool => $request->url() === $actionUrl)
            ->values();

        $this->assertCount(2, $mutationRequests);
        $mutationRequests->each(function (Request $request): void {
            $this->assertSame(
                ['11111111-1111-4111-8111-111111111111'],
                $request->header('Idempotency-Key'),
            );
        });
    }

    #[DataProvider('invalidIdempotencyPayloads')]
    public function test_missing_or_invalid_idempotency_key_does_not_contact_spring_mutation(
        array $payload,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => [$this->membership()],
            ]),
        ]);

        $this->authenticated()
            ->from($this->selectedQueueUrl())
            ->post(route('staff.live-queues.call-next', $this->operationRouteParameters()), $payload)
            ->assertRedirect($this->selectedQueueUrl())
            ->assertSessionHasErrors('idempotency_key');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/staff/call-next'));
    }

    /**
     * @return array<string, array{string, string, string, ?int, string, string, bool}>
     */
    public static function successfulOperations(): array
    {
        return [
            'pause' => ['staff.live-queues.pause', 'pause', 'Queue paused successfully.', null, 'queue', 'PAUSED', false],
            'resume' => ['staff.live-queues.resume', 'resume', 'Queue resumed successfully.', null, 'queue', 'OPEN', false],
            'close' => ['staff.live-queues.close', 'close', 'Queue closed successfully.', null, 'queue', 'CLOSED', false],
            'reopen' => ['staff.live-queues.reopen', 'reopen', 'Queue reopened successfully.', null, 'queue', 'OPEN', false],
            'call next' => ['staff.live-queues.call-next', 'call-next', 'Next ticket called successfully.', null, 'entry', 'CALLED', true],
            'start serving' => ['staff.live-queues.start', 'entries/301/start', 'Service started successfully.', 301, 'entry', 'SERVING', true],
            'recall' => ['staff.live-queues.recall', 'entries/301/recall', 'Ticket recalled successfully.', 301, 'entry', 'CALLED', true],
            'skip' => ['staff.live-queues.skip', 'entries/301/skip', 'Ticket skipped successfully.', 301, 'entry', 'SKIPPED', true],
            'complete' => ['staff.live-queues.complete', 'entries/301/complete', 'Ticket completed successfully.', 301, 'entry', 'COMPLETED', true],
        ];
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function invalidIdempotencyPayloads(): array
    {
        return [
            'missing' => [[]],
            'malformed' => [['idempotency_key' => 'not-a-uuid']],
        ];
    }

    /** @param array<string, mixed> $extraResponses */
    private function fakeWorkspace(array $extraResponses = [], ?array $dashboard = null): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => [$this->membership()],
            ]),
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response(
                $dashboard ?? $this->dashboard([$this->dashboardQueue('OPEN')]),
            ),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            ...$extraResponses,
        ]);
    }

    private function authenticated(): static
    {
        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => [$this->membership()],
            ],
        ]);
    }

    /** @return array<string, int> */
    private function operationRouteParameters(?int $entryId = null): array
    {
        return array_filter([
            'businessId' => 10,
            'branchId' => 101,
            'queueId' => 91,
            'entryId' => $entryId,
        ], static fn (?int $value): bool => $value !== null);
    }

    /** @return array<string, string> */
    private function operationPayload(bool $requiresIdempotencyKey): array
    {
        return $requiresIdempotencyKey
            ? ['idempotency_key' => '11111111-1111-4111-8111-111111111111']
            : [];
    }

    private function selectedQueueUrl(): string
    {
        return route('staff.live-queues.index', [
            'businessId' => 10,
            'branchId' => 101,
            'queue' => 91,
        ]);
    }

    /** @return array<string, mixed> */
    private function user(): array
    {
        return [
            'id' => 42,
            'email' => 'alex@example.com',
            'firstName' => 'Alex',
            'lastName' => 'Rivera',
            'phone' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function membership(): array
    {
        return ['businessId' => 10, 'branchId' => null, 'role' => 'MANAGER'];
    }

    /** @return array<string, mixed> */
    private function branch(): array
    {
        return [
            'id' => 101,
            'businessId' => 10,
            'name' => 'Singapore',
            'address' => '1 Marina Boulevard',
            'latitude' => 1.28,
            'longitude' => 103.85,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-10-04T09:00:00+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function business(): array
    {
        return [
            'id' => 10,
            'name' => 'RedBull',
            'description' => null,
            'createdAt' => '2026-10-04T09:00:00+08:00',
        ];
    }

    /** @param list<array<string, mixed>> $queues */
    private function dashboard(array $queues): array
    {
        return [
            'businessId' => 10,
            'branchId' => 101,
            'businessDate' => '2026-10-04',
            'queues' => $queues,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $waiting
     * @return array<string, mixed>
     */
    private function dashboardQueue(
        string $status,
        array $waiting = [],
        ?array $serving = null,
        ?array $called = null,
    ): array {
        return [
            'queueId' => 91,
            'publicCode' => 'selling-drink-queue-public-code',
            'name' => 'Selling drink queue',
            'status' => $status,
            'ticketPrefix' => 'D',
            'service' => [
                'id' => 501,
                'name' => 'Selling drink',
                'durationMinutes' => 10,
            ],
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
    private function queue(string $status): array
    {
        return [
            'id' => 91,
            'branchId' => 101,
            'serviceId' => 501,
            'name' => 'Selling drink queue',
            'businessDate' => '2026-10-04',
            'ticketPrefix' => 'D',
            'nextTicketSequence' => 2,
            'status' => $status,
            'openedAt' => '2026-10-04T09:00:00+08:00',
            'closedAt' => $status === 'CLOSED' ? '2026-10-04T12:00:00+08:00' : null,
            'createdAt' => '2026-10-04T09:00:00+08:00',
            'updatedAt' => '2026-10-04T10:00:00+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function entry(string $status): array
    {
        return [
            'entryId' => 301,
            'queueId' => 91,
            'serviceId' => 501,
            'userId' => null,
            'counterId' => null,
            'ticketSequence' => 1,
            'ticketNumber' => 'D001',
            'status' => $status,
            'joinedAt' => '2026-10-04T09:15:00+08:00',
            'calledAt' => in_array($status, ['CALLED', 'SERVING', 'COMPLETED', 'SKIPPED'], true)
                ? '2026-10-04T09:20:00+08:00'
                : null,
            'servingAt' => in_array($status, ['SERVING', 'COMPLETED'], true)
                ? '2026-10-04T09:25:00+08:00'
                : null,
            'completedAt' => $status === 'COMPLETED' ? '2026-10-04T09:35:00+08:00' : null,
            'cancelledAt' => null,
        ];
    }
}
