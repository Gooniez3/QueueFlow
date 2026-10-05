<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueueOpeningTest extends TestCase
{
    public function test_guest_cannot_open_queue_form(): void
    {
        $this->get(route('staff.live-queues.create', [10, 101]))
            ->assertRedirect(route('staff.login'));
    }

    public function test_guest_cannot_submit_queue_creation(): void
    {
        $this->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertRedirect(route('staff.login'));
    }

    public function test_form_uses_real_branch_services_and_never_renders_the_bearer_token(): void
    {
        $this->fake([
            $this->branchUrl() => Http::response($this->branch()),
            $this->servicesUrl() => Http::response([
                $this->service(501, 'Selling drink'),
                $this->service(502, 'Event service', active: false),
            ]),
            $this->businessUrl() => Http::response($this->business()),
        ]);

        $response = $this->authenticated()
            ->get(route('staff.live-queues.create', [10, 101]));

        $response->assertOk()
            ->assertSee('Open a queue')
            ->assertSee('Service-specific queue')
            ->assertSee('Shared branch queue')
            ->assertSee('Selling drink')
            ->assertSee('Event service (inactive)')
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === $this->servicesUrl()
            && ! $request->hasHeader('Authorization'));
    }

    public function test_form_rejects_branch_response_from_another_business_before_loading_services(): void
    {
        $this->fake([
            $this->branchUrl() => Http::response([
                ...$this->branch(),
                'businessId' => 99,
            ]),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.create', [10, 101]))
            ->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => $request->url() === $this->servicesUrl());
    }

    public function test_service_specific_queue_is_delegated_and_selected_after_creation(): void
    {
        $this->fakeQueueCreation($this->queue());

        $response = $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload());

        $response->assertRedirect(route('staff.live-queues.index', [
            'businessId' => 10,
            'branchId' => 101,
            'queue' => 91,
        ]))->assertSessionHas('status', 'Queue opened successfully.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === $this->queuesUrl()
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token')
            && $request->data() === [
                'serviceId' => 501,
                'name' => 'Selling drink queue',
                'ticketPrefix' => 'D',
            ]);
    }

    public function test_shared_queue_is_delegated_with_null_service_id(): void
    {
        $this->fakeQueueCreation($this->queue(serviceId: null));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), [
                'queueType' => 'shared',
                'serviceId' => '',
                'name' => 'Singapore walk-ins',
                'ticketPrefix' => 'S',
            ])
            ->assertRedirect(route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 91,
            ]));

        Http::assertSent(fn (Request $request): bool => $request->url() === $this->queuesUrl()
            && $request->data()['serviceId'] === null);
    }

    public function test_submission_rejects_branch_from_another_business_before_queue_mutation(): void
    {
        $this->fake([
            $this->branchUrl() => Http::response([
                ...$this->branch(),
                'businessId' => 99,
            ]),
        ]);

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => $request->url() === $this->queuesUrl());
    }

    public function test_validation_requires_queue_type_service_name_and_prefix_before_spring_mutation(): void
    {
        $this->fake();

        $this->authenticated()
            ->from(route('staff.live-queues.create', [10, 101]))
            ->post(route('staff.live-queues.store', [10, 101]), [
                'queueType' => 'service',
            ])
            ->assertRedirect(route('staff.live-queues.create', [10, 101]))
            ->assertSessionHasErrors(['serviceId', 'name', 'ticketPrefix']);

        Http::assertNotSent(fn (Request $request): bool => $request->url() === $this->queuesUrl());
    }

    public function test_spring_validation_errors_are_safely_mapped_to_queue_fields(): void
    {
        $this->fakeQueueCreation(Http::response([
            'message' => 'Internal validation detail',
            'validationErrors' => ['ticketPrefix' => 'Ticket prefix is required'],
        ], 400));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertRedirect(route('staff.live-queues.create', [10, 101]))
            ->assertSessionHasErrors(['ticketPrefix'])
            ->assertSessionDoesntHaveErrors(['queue']);
    }

    public function test_creation_401_clears_authentication_and_redirects_to_login(): void
    {
        $this->fakeQueueCreation(Http::response(['message' => 'Internal token detail'], 401));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertRedirect(route('staff.login'))
            ->assertSessionHas('error', 'Your session has expired. Please sign in again.')
            ->assertSessionMissing('queueflow.auth');
    }

    public function test_creation_403_preserves_authentication_and_hides_backend_detail(): void
    {
        $this->fakeQueueCreation(Http::response(['message' => 'Internal membership detail'], 403));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertForbidden()
            ->assertSee('You are not authorized to access this staff resource.')
            ->assertDontSee('Internal membership detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    public function test_creation_404_uses_safe_not_found_behavior(): void
    {
        $this->fakeQueueCreation(Http::response(['message' => 'Internal missing service detail'], 404));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Internal missing service detail');
    }

    public function test_creation_409_returns_to_form_with_safe_conflict_feedback(): void
    {
        $this->fakeQueueCreation(Http::response(['message' => 'Internal duplicate index detail'], 409));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertRedirect(route('staff.live-queues.create', [10, 101]))
            ->assertSessionHasErrors([
                'queue' => 'The queue could not be opened because today\'s queue state changed or a queue already exists.',
            ])
            ->assertSessionHasInput('name', 'Selling drink queue')
            ->assertSessionMissing('status');
    }

    public function test_creation_500_is_safe_and_preserves_authentication(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fakeQueueCreation(Http::response(['message' => 'Internal database detail'], 500));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal database detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_creation_connection_failure_is_safe(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fakeQueueCreation(Http::failedConnection('Internal connection detail'));

        $this->authenticated()
            ->post(route('staff.live-queues.store', [10, 101]), $this->serviceQueuePayload())
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal connection detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    private function fakeQueueCreation(mixed $queueResponse): void
    {
        $this->fake([
            $this->branchUrl() => Http::response($this->branch()),
            $this->queuesUrl() => $queueResponse,
        ]);
    }

    /** @param array<string, mixed> $responses */
    private function fake(array $responses = []): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => [$this->membership()],
            ]),
            ...$responses,
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

    /** @return array<string, mixed> */
    private function serviceQueuePayload(): array
    {
        return [
            'queueType' => 'service',
            'serviceId' => 501,
            'name' => 'Selling drink queue',
            'ticketPrefix' => 'D',
        ];
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
    private function business(): array
    {
        return [
            'id' => 10,
            'name' => 'RedBull',
            'description' => 'Drinks business.',
            'createdAt' => '2026-10-04T09:00:00+08:00',
        ];
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
    private function service(int $id, string $name, bool $active = true): array
    {
        return [
            'id' => $id,
            'branchId' => 101,
            'name' => $name,
            'description' => null,
            'durationMinutes' => 10,
            'active' => $active,
            'createdAt' => '2026-10-04T09:00:00+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function queue(?int $serviceId = 501): array
    {
        return [
            'id' => 91,
            'branchId' => 101,
            'serviceId' => $serviceId,
            'name' => 'Selling drink queue',
            'businessDate' => '2026-10-04',
            'ticketPrefix' => 'D',
            'nextTicketSequence' => 1,
            'status' => 'OPEN',
            'openedAt' => '2026-10-04T09:00:00+08:00',
            'closedAt' => null,
            'createdAt' => '2026-10-04T09:00:00+08:00',
            'updatedAt' => '2026-10-04T09:00:00+08:00',
        ];
    }

    private function branchUrl(): string
    {
        return 'http://localhost:8080/api/v1/businesses/10/branches/101';
    }

    private function businessUrl(): string
    {
        return 'http://localhost:8080/api/v1/businesses/10';
    }

    private function servicesUrl(): string
    {
        return 'http://localhost:8080/api/v1/businesses/10/branches/101/services';
    }

    private function queuesUrl(): string
    {
        return 'http://localhost:8080/api/v1/businesses/10/branches/101/queues';
    }
}
