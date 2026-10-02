<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CustomerQueueEntryTest extends TestCase
{
    public function test_join_route_is_public_numeric_and_has_no_staff_middleware(): void
    {
        $route = Route::getRoutes()->getByName('queue-entries.store');

        $this->assertNotNull($route);
        $this->assertSame('queues/{queueId}/entries', $route->uri());
        $this->assertSame(['POST'], $route->methods());
        $this->assertSame('[0-9]+', $route->wheres['queueId']);
        $this->assertNotContains('queueflow.staff.auth', $route->gatherMiddleware());
        $this->assertNotContains('queueflow.business.member', $route->gatherMiddleware());
    }

    public function test_non_numeric_queue_route_does_not_match(): void
    {
        $this->post('/queues/not-a-number/entries', $this->joinPayload())
            ->assertNotFound();
    }

    public function test_invalid_structural_input_redirects_without_api_requests(): void
    {
        Http::preventStrayRequests();

        $response = $this->from(route('services.show', [10, 21, 31]))
            ->post(route('queue-entries.store', 91), [
                'businessId' => 10,
                'branchId' => 21,
            ]);

        $response->assertRedirect(route('services.show', [10, 21, 31]))
            ->assertSessionHasErrors('serviceId');
        Http::assertNothingSent();
    }

    #[DataProvider('nestedResourceMismatches')]
    public function test_nested_context_mismatch_never_reaches_queue_join(
        int $returnedBusinessId,
        int $returnedBranchBusinessId,
        int $returnedServiceBranchId,
    ): void {
        $this->fakeApi(
            businessId: $returnedBusinessId,
            branchBusinessId: $returnedBranchBusinessId,
            serviceBranchId: $returnedServiceBranchId,
        );

        $this->post(route('queue-entries.store', 91), $this->joinPayload())
            ->assertNotFound();

        Http::assertNotSent(
            fn (Request $request): bool => $request->method() === 'POST',
        );
    }

    public function test_posted_queue_must_match_current_applicable_queue(): void
    {
        $this->fakeApi();

        $response = $this->post(route('queue-entries.store', 92), $this->joinPayload());

        $response->assertConflict()
            ->assertSee('The queue is no longer accepting joins. Please refresh and try again.');
        Http::assertNotSent(
            fn (Request $request): bool => $request->method() === 'POST',
        );
    }

    public function test_missing_applicable_queue_does_not_join_stale_queue(): void
    {
        $this->fakeApi(queueMissing: true);

        $response = $this->post(route('queue-entries.store', 91), $this->joinPayload());

        $response->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Queue not found for today');
        Http::assertNotSent(
            fn (Request $request): bool => $request->method() === 'POST',
        );
    }

    #[DataProvider('ineligibleServiceStates')]
    public function test_ineligible_service_or_queue_does_not_join(
        bool $serviceActive,
        string $queueStatus,
    ): void {
        $this->fakeApi(serviceActive: $serviceActive, queueStatus: $queueStatus);

        $this->post(route('queue-entries.store', 91), $this->joinPayload())
            ->assertConflict();

        Http::assertNotSent(
            fn (Request $request): bool => $request->method() === 'POST',
        );
    }

    public function test_successful_guest_join_uses_server_key_stores_ownership_and_redirects_without_credentials(): void
    {
        $sentKeys = [];
        $this->fakeApi(joinHandler: function (Request $request) use (&$sentKeys) {
            $sentKeys[] = $request->header('Idempotency-Key')[0] ?? null;

            return Http::response($this->queueEntry(), 201);
        });
        $otherAttemptKey = '22222222-2222-4222-8222-222222222222';

        $response = $this->withSession([
            'queueflow.customer.join_attempts' => [
                '10:21:32:92' => $this->pendingAttempt(32, 92, $otherAttemptKey),
            ],
            'queueflow.auth' => ['token' => 'inert-staff-token'],
            'unrelated' => 'preserved',
        ])->post(route('queue-entries.store', 91), $this->joinPayload());

        $response->assertRedirect(route('tickets.show'))
            ->assertSessionHas('status', 'You have joined the queue.')
            ->assertSessionHas('queueflow.customer.entries.91:301.guestToken', 'raw-guest-secret')
            ->assertSessionMissing('queueflow.customer.join_attempts.10:21:31:91')
            ->assertSessionHas(
                'queueflow.customer.join_attempts.10:21:32:92.idempotencyKey',
                $otherAttemptKey,
            )
            ->assertSessionHas('queueflow.auth.token', 'inert-staff-token')
            ->assertSessionHas('unrelated', 'preserved')
            ->assertDontSee('raw-guest-secret')
            ->assertDontSee((string) $sentKeys[0]);

        $this->assertNotNull($sentKeys[0]);
        $this->assertTrue((bool) preg_match('/^[0-9a-f-]{36}$/', (string) $sentKeys[0]));
        $this->assertSame(route('tickets.show'), $response->headers->get('Location'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/queues/91/entries'
            && ! $request->hasHeader('Authorization')
            && ! $request->hasHeader('X-Guest-Token')
            && $request->data() === ['serviceId' => 31]);
    }

    public function test_connection_failure_retains_attempt_and_retry_reuses_same_key(): void
    {
        $sentKeys = [];
        $joinCalls = 0;
        $this->fakeApi(joinHandler: function (Request $request) use (&$joinCalls, &$sentKeys) {
            $sentKeys[] = $request->header('Idempotency-Key')[0] ?? null;
            $joinCalls++;

            if ($joinCalls === 1) {
                throw new ConnectionException('Internal connection detail');
            }

            return Http::response($this->queueEntry(), 201);
        });

        $firstResponse = $this->post(route('queue-entries.store', 91), $this->joinPayload());

        $firstResponse->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal connection detail')
            ->assertSessionHas('queueflow.customer.join_attempts.10:21:31:91.idempotencyKey');

        $secondResponse = $this->post(route('queue-entries.store', 91), $this->joinPayload());

        $secondResponse->assertRedirect(route('tickets.show'))
            ->assertSessionMissing('queueflow.customer.join_attempts.10:21:31:91');
        $this->assertCount(2, $sentKeys);
        $this->assertSame($sentKeys[0], $sentKeys[1]);
    }

    public function test_server_failure_is_safe_and_retains_pending_attempt(): void
    {
        $this->fakeApi(joinHandler: fn () => Http::response([
            'message' => 'Internal Spring database detail',
        ], 500));

        $response = $this->post(route('queue-entries.store', 91), $this->joinPayload());

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring database detail')
            ->assertSessionHas('queueflow.customer.join_attempts.10:21:31:91.idempotencyKey');
        $responseContent = $response->getContent();
        $this->assertIsString($responseContent);
        $this->assertStringNotContainsString(
            session('queueflow.customer.join_attempts.10:21:31:91.idempotencyKey'),
            $responseContent,
        );
    }

    public function test_missing_guest_credential_is_safe_and_does_not_create_ownership(): void
    {
        $this->fakeApi(joinHandler: fn () => Http::response([
            ...$this->queueEntry(),
            'guestToken' => null,
        ], 201));

        $response = $this->post(route('queue-entries.store', 91), $this->joinPayload());

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Guest queue ownership credentials were not returned.')
            ->assertSessionHas('queueflow.customer.join_attempts.10:21:31:91.idempotencyKey')
            ->assertSessionMissing('queueflow.customer.entries.91:301');
    }

    #[DataProvider('definitiveJoinFailures')]
    public function test_definitive_join_failure_is_safe_and_clears_attempt(
        int $status,
        string $safeMessage,
    ): void {
        $pendingKey = '11111111-1111-4111-8111-111111111111';
        $this->fakeApi(joinHandler: fn () => Http::response([
            'message' => 'Internal Spring rejection detail',
        ], $status));

        $response = $this->withSession([
            'queueflow.customer.join_attempts' => [
                '10:21:31:91' => $this->pendingAttempt(31, 91, $pendingKey),
            ],
        ])->post(route('queue-entries.store', 91), $this->joinPayload());

        $response->assertStatus($status)
            ->assertSee($safeMessage)
            ->assertDontSee('Internal Spring rejection detail')
            ->assertDontSee($pendingKey)
            ->assertSessionMissing('queueflow.customer.join_attempts.10:21:31:91')
            ->assertSessionMissing('queueflow.customer.entries.91:301');
    }

    /** @return array<string, array{int, int, int}> */
    public static function nestedResourceMismatches(): array
    {
        return [
            'business response differs' => [99, 10, 21],
            'branch belongs to another business' => [10, 99, 21],
            'service belongs to another branch' => [10, 10, 99],
        ];
    }

    /** @return array<string, array{bool, string}> */
    public static function ineligibleServiceStates(): array
    {
        return [
            'inactive service' => [false, 'OPEN'],
            'paused queue' => [true, 'PAUSED'],
            'closed queue' => [true, 'CLOSED'],
        ];
    }

    /** @return array<string, array{int, string}> */
    public static function definitiveJoinFailures(): array
    {
        return [
            'bad request' => [400, 'We could not join this queue. Please refresh and try again.'],
            'forbidden' => [403, 'This queue request is not permitted.'],
            'not found' => [404, 'The requested QueueFlow resource was not found.'],
            'conflict' => [409, 'The queue is no longer accepting joins. Please refresh and try again.'],
        ];
    }

    /** @return array<string, int> */
    private function joinPayload(): array
    {
        return ['businessId' => 10, 'branchId' => 21, 'serviceId' => 31];
    }

    /** @return array<string, mixed> */
    private function pendingAttempt(int $serviceId, int $queueId, string $key): array
    {
        return [
            'businessId' => 10,
            'branchId' => 21,
            'serviceId' => $serviceId,
            'queueId' => $queueId,
            'idempotencyKey' => $key,
        ];
    }

    private function fakeApi(
        ?callable $joinHandler = null,
        int $businessId = 10,
        int $branchBusinessId = 10,
        int $serviceBranchId = 21,
        bool $serviceActive = true,
        string $queueStatus = 'OPEN',
        bool $queueMissing = false,
    ): void {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (
            $joinHandler,
            $businessId,
            $branchBusinessId,
            $serviceBranchId,
            $serviceActive,
            $queueStatus,
            $queueMissing,
        ) {
            $url = $request->url();

            if ($url === 'http://localhost:8080/api/v1/businesses/10') {
                return Http::response($this->business($businessId));
            }

            if ($url === 'http://localhost:8080/api/v1/businesses/10/branches/21') {
                return Http::response($this->branch($branchBusinessId));
            }

            if ($url === 'http://localhost:8080/api/v1/businesses/10/branches/21/services/31') {
                return Http::response($this->service($serviceBranchId, $serviceActive));
            }

            if (str_starts_with(
                $url,
                'http://localhost:8080/api/v1/businesses/10/branches/21/queues/today',
            )) {
                return $queueMissing
                    ? Http::response(['message' => 'Queue not found for today'], 404)
                    : Http::response($this->queue($queueStatus));
            }

            if ($url === 'http://localhost:8080/api/v1/queues/91/entries'
                && $request->method() === 'POST'
                && $joinHandler !== null) {
                return $joinHandler($request);
            }

            throw new RuntimeException("Unexpected QueueFlow request: {$request->method()} {$url}");
        });
    }

    /** @return array<string, mixed> */
    private function business(int $id): array
    {
        return [
            'id' => $id,
            'name' => 'Northstar Health',
            'description' => 'Neighbourhood healthcare.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function branch(int $businessId): array
    {
        return [
            'id' => 21,
            'businessId' => $businessId,
            'name' => 'Riverside Clinic',
            'address' => '10 River Road, Singapore',
            'latitude' => null,
            'longitude' => null,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function service(int $branchId, bool $active): array
    {
        return [
            'id' => 31,
            'branchId' => $branchId,
            'name' => 'General Consultation',
            'description' => 'Primary care consultation.',
            'durationMinutes' => 20,
            'active' => $active,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function queue(string $status): array
    {
        return [
            'id' => 91,
            'branchId' => 21,
            'serviceId' => 31,
            'name' => 'General Care',
            'businessDate' => '2026-10-03',
            'ticketPrefix' => 'A',
            'status' => $status,
        ];
    }

    /** @return array<string, mixed> */
    private function queueEntry(): array
    {
        return [
            'id' => 301,
            'queueId' => 91,
            'serviceId' => 31,
            'userId' => null,
            'ticketSequence' => 23,
            'ticketNumber' => 'A023',
            'status' => 'WAITING',
            'joinedAt' => '2026-10-03T10:30:00+08:00',
            'guestToken' => 'raw-guest-secret',
        ];
    }
}
