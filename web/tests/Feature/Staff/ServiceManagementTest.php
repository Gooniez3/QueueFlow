<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    public function test_branch_page_lists_active_and_inactive_services_using_public_gets(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([
                $this->service(501, 'General Consultation', true),
                $this->service(502, 'Follow-up Consultation', false),
            ]),
        ]);

        $response = $this->authenticated()
            ->get('/staff/businesses/10/branches/101');

        $response->assertOk()
            ->assertSee('General Consultation')
            ->assertSee('Follow-up Consultation')
            ->assertSee('Active')
            ->assertSee('Inactive')
            ->assertSee('Create service')
            ->assertSee('Branch of Northstar Health')
            ->assertSee('>2</dd>', false)
            ->assertSee('href="'.route('staff.services.show', [10, 101, 501]).'"', false)
            ->assertSee('href="'.route('staff.services.show', [10, 101, 502]).'"', false)
            ->assertSee('href="'.route('staff.branches.edit', [10, 101]).'"', false)
            ->assertSee('aria-label="Staff page context"', false)
            ->assertSeeInOrder([
                'staff-nav-item staff-nav-item-active',
                '<span>Branches</span>',
            ], false)
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => $request->url() !== 'http://localhost:8080/api/v1/auth/me'
            && ! $request->hasHeader('Authorization'));
    }

    public function test_service_creation_uses_bearer_authentication_and_maps_payload(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response(
                $this->service(),
                201,
            ),
        ]);

        $response = $this->authenticated()
            ->post('/staff/businesses/10/branches/101/services', [
                'name' => 'General Consultation',
                'description' => 'Standard appointment.',
                'durationMinutes' => 20,
            ]);

        $response->assertRedirect(route('staff.services.show', [10, 101, 501]))
            ->assertSessionHas('status', 'Service created successfully.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/101/services'
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token')
            && $request->data() === [
                'name' => 'General Consultation',
                'description' => 'Standard appointment.',
                'durationMinutes' => 20,
            ]);
    }

    public function test_service_form_requires_a_positive_integer_duration(): void
    {
        $this->fake();

        $response = $this->authenticated()
            ->from('/staff/businesses/10/branches/101/services/create')
            ->post('/staff/businesses/10/branches/101/services', [
                'name' => 'General Consultation',
                'durationMinutes' => 0,
            ]);

        $response->assertRedirect('/staff/businesses/10/branches/101/services/create')
            ->assertSessionHasErrors('durationMinutes');
        Http::assertSentCount(1);
    }

    public function test_spring_service_validation_errors_are_mapped_to_fields(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([
                'message' => 'Internal Spring validation detail',
                'validationErrors' => [
                    'name' => 'Name is already in use.',
                    'durationMinutes' => 'Duration must be positive.',
                ],
            ], 400),
        ]);

        $response = $this->authenticated()
            ->from('/staff/businesses/10/branches/101/services/create')
            ->post('/staff/businesses/10/branches/101/services', [
                'name' => 'General Consultation',
                'durationMinutes' => 20,
            ]);

        $response->assertRedirect('/staff/businesses/10/branches/101/services/create')
            ->assertSessionHasErrors([
                'name' => 'Name is already in use.',
                'durationMinutes' => 'Duration must be positive.',
            ])
            ->assertDontSee('Internal Spring validation detail');
    }

    public function test_service_detail_displays_spring_active_state_without_exposing_token(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response(
                $this->service(501, 'General Consultation', false),
            ),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
        ]);

        $response = $this->authenticated()
            ->get('/staff/businesses/10/branches/101/services/501');

        $response->assertOk()
            ->assertSee('General Consultation')
            ->assertSee('Inactive')
            ->assertSee('20 minutes')
            ->assertSee('Standard appointment.')
            ->assertSee('Service at Riverside Clinic')
            ->assertSee('Northstar Health')
            ->assertSee('href="'.route('staff.businesses.show', 10).'"', false)
            ->assertSee('href="'.route('staff.branches.show', [10, 101]).'"', false)
            ->assertSee('href="'.route('staff.services.edit', [10, 101, 501]).'"', false)
            ->assertSee('aria-label="Staff page context"', false)
            ->assertSeeInOrder([
                'staff-nav-item staff-nav-item-active',
                '<span>Services</span>',
            ], false)
            ->assertDontSee('Queue context')
            ->assertSee('Open in live queues')
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee('waiting')
            ->assertDontSee('ticket prefix')
            ->assertDontSee('inert-spring-token');

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'href="'.route('staff.services.edit', [10, 101, 501]).'"'),
        );
        $this->assertSame(2, substr_count($response->getContent(), 'staff-nav-item-active'));
    }

    public function test_service_detail_displays_the_active_state_from_spring(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response($this->service()),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
        ]);

        $this->authenticated()
            ->get(route('staff.services.show', [10, 101, 501]))
            ->assertOk()
            ->assertSee('ACTIVE')
            ->assertSee('>Active<', false)
            ->assertDontSee('INACTIVE');
    }

    public function test_service_detail_matches_and_renders_its_real_dashboard_queue(): void
    {
        $queue = $this->dashboardQueue(serviceId: 501);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response($this->service()),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response($this->dashboard([$queue])),
        ]);

        $response = $this->authenticated()
            ->get(route('staff.services.show', [10, 101, 501]))
            ->assertOk()
            ->assertSee('Consultation Queue')
            ->assertSee('QUEUE PREFIX')
            ->assertSee('WAITING NOW')
            ->assertSee('QUEUE STATUS')
            ->assertSee('>A<', false)
            ->assertSee('>2</dd>', false)
            ->assertSee('>1</dd>', false)
            ->assertSee('NOW SERVING')
            ->assertSee('A004')
            ->assertSee('CALLED')
            ->assertSee('A003')
            ->assertSee('href="'.route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 91,
            ]).'"', false);

        $this->assertSame(2, substr_count(
            $response->getContent(),
            'href="'.route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 91,
            ]).'"',
        ));
    }

    public function test_service_detail_does_not_match_a_shared_branch_queue(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response($this->service()),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response($this->dashboard([
                $this->dashboardQueue(serviceId: null),
            ])),
        ]);

        $response = $this->authenticated()
            ->get(route('staff.services.show', [10, 101, 501]))
            ->assertOk()
            ->assertSee('No live queue today')
            ->assertSee('No service-specific queue is open for this service today.')
            ->assertDontSee('A004');

        $response->assertDontSee('href="'.route('staff.live-queues.index', [
            'businessId' => 10,
            'branchId' => 101,
            'queue' => 91,
        ]).'"', false);
    }

    public function test_service_detail_keeps_service_data_when_dashboard_is_unavailable(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response($this->service()),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response([
                'message' => 'Internal dashboard failure',
            ], 500),
        ]);

        $this->authenticated()
            ->get(route('staff.services.show', [10, 101, 501]))
            ->assertOk()
            ->assertSee('General Consultation')
            ->assertSee('Queue data unavailable')
            ->assertDontSee('Internal dashboard failure')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_service_detail_rejects_a_response_for_another_branch(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response([
                ...$this->service(),
                'branchId' => 999,
            ]),
        ]);

        $this->authenticated()
            ->get('/staff/businesses/10/branches/101/services/501')
            ->assertNotFound();
    }

    public function test_service_creation_rejects_a_branch_from_another_business_before_mutation(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response([
                ...$this->branch(),
                'businessId' => 99,
            ]),
        ]);

        $this->authenticated()
            ->post('/staff/businesses/10/branches/101/services', [
                'name' => 'General Consultation',
                'durationMinutes' => 20,
            ])
            ->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/101/services');
        Http::assertSentCount(2);
    }

    public function test_service_detail_rejects_staff_without_business_membership(): void
    {
        $otherMembership = ['businessId' => 99, 'branchId' => null, 'role' => 'STAFF'];
        $this->fake([], $otherMembership);

        $this->authenticated($otherMembership)
            ->get(route('staff.services.show', [10, 101, 501]))
            ->assertForbidden()
            ->assertSee('You are not authorized to manage this business.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Http::assertSentCount(1);
    }

    public function test_service_detail_upstream_failure_is_safe(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);

        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response([
                'message' => 'Internal Spring service failure',
            ], 500),
        ]);

        $this->authenticated()
            ->get(route('staff.services.show', [10, 101, 501]))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring service failure')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_service_edit_page_loads_duration_and_inactive_state_without_exposing_the_token(): void
    {
        $membership = $this->membership('MANAGER', 101);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response(
                $this->service(501, 'General Consultation', false),
            ),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
        ], $membership);

        $this->authenticated($membership)
            ->get(route('staff.services.edit', [10, 101, 501]))
            ->assertOk()
            ->assertSee('Edit service')
            ->assertSee('value="General Consultation"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="0" selected', false)
            ->assertSee('action="'.route('staff.services.update', [10, 101, 501]).'"', false)
            ->assertDontSee('inert-spring-token');
    }

    public function test_service_update_can_deactivate_service_and_normalizes_blank_description(): void
    {
        $membership = $this->membership('OWNER');
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::sequence()
                ->push($this->service())
                ->push([
                    ...$this->service(501, 'Updated Consultation', false),
                    'description' => null,
                    'durationMinutes' => 30,
                ]),
        ], $membership);

        $response = $this->authenticated($membership)
            ->put(route('staff.services.update', [10, 101, 501]), [
                'name' => 'Updated Consultation',
                'description' => '   ',
                'durationMinutes' => 30,
                'active' => '0',
            ]);

        $response->assertRedirect(route('staff.services.show', [10, 101, 501]))
            ->assertSessionHas('status', 'Service updated successfully.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches/101/services/501'
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token')
            && $request->data() === [
                'name' => 'Updated Consultation',
                'description' => null,
                'durationMinutes' => 30,
                'active' => false,
            ]);
    }

    public function test_service_update_can_reactivate_service(): void
    {
        $membership = $this->membership('MANAGER', 101);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::sequence()
                ->push($this->service(501, 'General Consultation', false))
                ->push($this->service()),
        ], $membership);

        $this->authenticated($membership)
            ->put(route('staff.services.update', [10, 101, 501]), [
                'name' => 'General Consultation',
                'description' => 'Standard appointment.',
                'durationMinutes' => 20,
                'active' => '1',
            ])
            ->assertRedirect(route('staff.services.show', [10, 101, 501]));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->data()['active'] === true);
    }

    public function test_service_update_rejects_invalid_hierarchy_before_mutation(): void
    {
        $membership = $this->membership('OWNER');
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::response([
                ...$this->service(),
                'branchId' => 999,
            ]),
        ], $membership);

        $this->authenticated($membership)
            ->put(route('staff.services.update', [10, 101, 501]), [
                'name' => 'Updated Consultation',
                'durationMinutes' => 30,
                'active' => '1',
            ])
            ->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    }

    public function test_service_update_validation_prevents_the_spring_mutation(): void
    {
        $membership = $this->membership('OWNER');
        $this->fake([], $membership);

        $this->authenticated($membership)
            ->from(route('staff.services.edit', [10, 101, 501]))
            ->put(route('staff.services.update', [10, 101, 501]), [
                'name' => '',
                'durationMinutes' => 0,
            ])
            ->assertRedirect(route('staff.services.edit', [10, 101, 501]))
            ->assertSessionHasErrors(['name', 'durationMinutes', 'active']);

        Http::assertSentCount(1);
    }

    public function test_service_update_403_is_safe_and_preserves_authentication(): void
    {
        $membership = $this->membership('STAFF', 101);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::sequence()
                ->push($this->service())
                ->push(['message' => 'Internal Spring authorization detail'], 403),
        ], $membership);

        $this->authenticated($membership)
            ->put(route('staff.services.update', [10, 101, 501]), [
                'name' => 'General Consultation',
                'durationMinutes' => 20,
                'active' => '1',
            ])
            ->assertForbidden()
            ->assertSee('You are not authorized to access this staff resource.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token')
            ->assertDontSee('Internal Spring authorization detail');
    }

    public function test_service_update_connection_failure_is_safe_and_preserves_authentication(): void
    {
        $membership = $this->membership('OWNER');
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101/services/501' => Http::sequence()
                ->push($this->service())
                ->pushFailedConnection('Internal network detail'),
        ], $membership);

        $this->authenticated($membership)
            ->put(route('staff.services.update', [10, 101, 501]), [
                'name' => 'General Consultation',
                'durationMinutes' => 20,
                'active' => '1',
            ])
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token')
            ->assertDontSee('Internal network detail');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === null,
        );
    }

    /**
     * @param  array<string, mixed>  $responses
     */
    private function fake(array $responses = [], ?array $membership = null): void
    {
        $membership ??= $this->membership();

        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => [$membership],
            ]),
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response($this->dashboard()),
            ...$responses,
        ]);
    }

    /** @param array<string, mixed>|null $membership */
    private function authenticated(?array $membership = null): static
    {
        $membership ??= $this->membership();

        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => [$membership],
            ],
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
    private function membership(string $role = 'STAFF', ?int $branchId = null): array
    {
        return ['businessId' => 10, 'branchId' => $branchId, 'role' => $role];
    }

    /** @return array<string, mixed> */
    private function business(): array
    {
        return [
            'id' => 10,
            'name' => 'Northstar Health',
            'description' => 'Community health services.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function branch(): array
    {
        return [
            'id' => 101,
            'businessId' => 10,
            'name' => 'Riverside Clinic',
            'address' => '1 River Road',
            'latitude' => null,
            'longitude' => null,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function service(
        int $id = 501,
        string $name = 'General Consultation',
        bool $active = true,
    ): array {
        return [
            'id' => $id,
            'branchId' => 101,
            'name' => $name,
            'description' => 'Standard appointment.',
            'durationMinutes' => 20,
            'active' => $active,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function dashboard(array $queues = []): array
    {
        return [
            'businessId' => 10,
            'branchId' => 101,
            'businessDate' => '2026-10-04',
            'queues' => $queues,
        ];
    }

    /** @return array<string, mixed> */
    private function dashboardQueue(?int $serviceId): array
    {
        return [
            'queueId' => 91,
            'publicCode' => 'consultation-queue-public-code',
            'name' => $serviceId === null ? 'Shared Queue' : 'Consultation Queue',
            'status' => 'OPEN',
            'ticketPrefix' => 'A',
            'service' => $serviceId === null ? null : [
                'id' => $serviceId,
                'name' => 'General Consultation',
                'durationMinutes' => 20,
            ],
            'counts' => ['waiting' => 2, 'called' => 1, 'serving' => 1],
            'serving' => $this->dashboardEntry(304, 'A004', 'SERVING'),
            'called' => $this->dashboardEntry(303, 'A003', 'CALLED'),
            'waiting' => [
                $this->dashboardEntry(301, 'A001', 'WAITING'),
                $this->dashboardEntry(302, 'A002', 'WAITING'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dashboardEntry(int $entryId, string $ticketNumber, string $status): array
    {
        return [
            'entryId' => $entryId,
            'queueId' => 91,
            'serviceId' => 501,
            'userId' => null,
            'counterId' => null,
            'ticketSequence' => $entryId - 300,
            'ticketNumber' => $ticketNumber,
            'status' => $status,
            'joinedAt' => '2026-10-04T10:30:00+08:00',
            'calledAt' => in_array($status, ['CALLED', 'SERVING'], true) ? '2026-10-04T10:40:00+08:00' : null,
            'servingAt' => $status === 'SERVING' ? '2026-10-04T10:45:00+08:00' : null,
            'completedAt' => null,
            'cancelledAt' => null,
        ];
    }
}
