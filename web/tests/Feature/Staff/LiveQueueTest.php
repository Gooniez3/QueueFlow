<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveQueueTest extends TestCase
{
    public function test_guest_is_redirected_from_live_queue_gateway_to_login(): void
    {
        $this->get(route('staff.live-queues.gateway'))
            ->assertRedirect(route('staff.login'));
    }

    public function test_guest_is_redirected_from_live_queue_workspace_to_login(): void
    {
        $this->get(route('staff.live-queues.index', [10, 101]))
            ->assertRedirect(route('staff.login'));
    }

    public function test_gateway_lists_only_branches_accessible_through_real_memberships(): void
    {
        $membership = $this->membership(branchId: 101);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 'Riverside Clinic'),
                $this->branch(102, 'Harbour Clinic'),
            ]),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
        ], [$membership]);

        $response = $this->authenticated([$membership])
            ->get(route('staff.live-queues.gateway'));

        $response->assertOk()
            ->assertSee('Choose a branch')
            ->assertSee('Northstar Health')
            ->assertSee('Riverside Clinic')
            ->assertSee('href="'.route('staff.live-queues.index', [10, 101]).'"', false)
            ->assertDontSee('Harbour Clinic')
            ->assertDontSee('inert-spring-token');
    }

    public function test_gateway_renders_truthful_empty_state_without_accessible_branches(): void
    {
        $membership = $this->membership(branchId: 999);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 'Riverside Clinic'),
            ]),
        ], [$membership]);

        $this->authenticated([$membership])
            ->get(route('staff.live-queues.gateway'))
            ->assertOk()
            ->assertSee('No accessible branches')
            ->assertDontSee('Riverside Clinic');
    }

    public function test_workspace_uses_first_queue_and_renders_real_dashboard_state_in_spring_order(): void
    {
        $queues = [
            $this->dashboardQueue(
                queueId: 91,
                name: 'Walk-in Support',
                status: 'OPEN',
                prefix: 'W',
                waiting: [
                    $this->entry(903, 'W003', 'WAITING'),
                    $this->entry(904, 'W004', 'WAITING'),
                ],
                serving: $this->entry(901, 'W001', 'SERVING'),
                called: $this->entry(902, 'W002', 'CALLED'),
            ),
            $this->dashboardQueue(92, 'Consultation Queue', 'PAUSED', 'C', serviceId: 501),
            $this->dashboardQueue(93, 'Closing Queue', 'CLOSED', 'Z'),
        ];
        $this->fakeWorkspace($this->dashboard($queues));

        $response = $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]));

        $response->assertOk()
            ->assertSee('Live queues')
            ->assertSee('Riverside Clinic')
            ->assertSee('Saturday 3 October 2026')
            ->assertSee('Walk-in Support')
            ->assertSee('Consultation Queue')
            ->assertSee('Closing Queue')
            ->assertSee('OPEN')
            ->assertSee('PAUSED')
            ->assertSee('CLOSED')
            ->assertSee('Shared branch queue')
            ->assertSee('W001')
            ->assertSee('W002')
            ->assertSeeInOrder(['W003', 'W004'])
            ->assertSee('Next up')
            ->assertSee('Position 1 of 2 waiting')
            ->assertSee('View board')
            ->assertSee('href="'.route('queues.board.show', 'queue-91-public-code').'"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('href="'.route('queues.board.show', '91').'"', false)
            ->assertDontSee('inert-spring-token');

        $dashboardRequests = collect(Http::recorded())->filter(
            fn (array $exchange): bool => str_ends_with($exchange[0]->url(), '/staff/dashboard'),
        );

        $this->assertCount(1, $dashboardRequests);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/staff/dashboard')
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token'));
    }

    public function test_workspace_with_existing_queues_keeps_open_queue_action_available(): void
    {
        $queues = [
            $this->dashboardQueue(91, 'General Consultation Queue', 'OPEN', 'GC', serviceId: 501),
            $this->dashboardQueue(92, 'Health Screening Queue', 'OPEN', 'HS', serviceId: 502),
        ];
        $this->fakeWorkspace($this->dashboard($queues));

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertOk()
            ->assertSee('General Consultation Queue')
            ->assertSee('Health Screening Queue')
            ->assertSee('href="'.route('staff.live-queues.create', [10, 101]).'"', false)
            ->assertSee('Open queue');
    }

    public function test_explicit_queue_selection_uses_existing_dashboard_data_without_another_queue_request(): void
    {
        $queues = [
            $this->dashboardQueue(91, 'Walk-in Support', 'OPEN', 'W'),
            $this->dashboardQueue(92, 'Consultation Queue', 'PAUSED', 'C', serviceId: 501),
        ];
        $this->fakeWorkspace($this->dashboard($queues));

        $response = $this->authenticated()
            ->get(route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 92,
            ]));

        $response->assertOk()
            ->assertSee('General Consultation')
            ->assertSee('aria-current="true"', false)
            ->assertSee('queue=92', false);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v1/queues/92'));
        $this->assertCount(1, collect(Http::recorded())->filter(
            fn (array $exchange): bool => str_ends_with($exchange[0]->url(), '/staff/dashboard'),
        ));
    }

    public function test_invalid_stale_or_foreign_queue_selection_returns_404(): void
    {
        $this->fakeWorkspace($this->dashboard([
            $this->dashboardQueue(91, 'Walk-in Support', 'OPEN', 'W'),
        ]));

        $this->authenticated()
            ->get(route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 999,
            ]))
            ->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/api/v1/queues/999'));
    }

    public function test_malformed_queue_selection_returns_404(): void
    {
        $this->fakeWorkspace($this->dashboard([
            $this->dashboardQueue(91, 'Walk-in Support', 'OPEN', 'W'),
        ]));

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]).'?queue=not-a-queue')
            ->assertNotFound();
    }

    public function test_workspace_rejects_branch_response_from_another_business(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response([
                ...$this->branch(),
                'businessId' => 99,
            ]),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertNotFound();

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/staff/dashboard'));
    }

    public function test_workspace_renders_truthful_no_queues_state(): void
    {
        $this->fakeWorkspace($this->dashboard());

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertOk()
            ->assertSee('No queues today')
            ->assertSee('No queue has been opened for Riverside Clinic on this business date.')
            ->assertSee('href="'.route('staff.live-queues.create', [10, 101]).'"', false)
            ->assertSee('Open queue')
            ->assertDontSee('Call next');
    }

    public function test_workspace_renders_truthful_empty_operational_states(): void
    {
        $this->fakeWorkspace($this->dashboard([
            $this->dashboardQueue(91, 'Walk-in Support', 'OPEN', 'W'),
        ]));

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertOk()
            ->assertSee('No tickets are currently waiting.')
            ->assertSee('No ticket is currently being served.')
            ->assertSee('No ticket is currently called.')
            ->assertSee('No tickets waiting');
    }

    public function test_refresh_preserves_selection_and_phase_10_5_controls_are_enabled(): void
    {
        $this->fakeWorkspace($this->dashboard([
            $this->dashboardQueue(91, 'Walk-in Support', 'OPEN', 'W'),
            $this->dashboardQueue(
                92,
                'Consultation Queue',
                'OPEN',
                'C',
                [$this->entry(903, 'C003', 'WAITING')],
                $this->entry(901, 'C001', 'SERVING'),
                $this->entry(902, 'C002', 'CALLED'),
                501,
            ),
        ]));

        $response = $this->authenticated()
            ->get(route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 92,
            ]));

        $response->assertOk()
            ->assertSee('href="'.route('staff.live-queues.index', [
                'businessId' => 10,
                'branchId' => 101,
                'queue' => 92,
            ]).'"', false)
            ->assertSee('Pause')
            ->assertSee('Close queue')
            ->assertSee('Complete')
            ->assertSee('Start serving')
            ->assertSee('Recall')
            ->assertSee('Skip')
            ->assertSee(
                'action="'.route('staff.live-queues.pause', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 92,
                ]).'"',
                false,
            )
            ->assertSee(
                'action="'.route('staff.live-queues.close', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 92,
                ]).'"',
                false,
            )
            ->assertSee(
                'action="'.route('staff.live-queues.complete', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 92,
                    'entryId' => 901,
                ]).'"',
                false,
            )
            ->assertSee(
                'action="'.route('staff.live-queues.start', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 92,
                    'entryId' => 902,
                ]).'"',
                false,
            )
            ->assertSee(
                'action="'.route('staff.live-queues.recall', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 92,
                    'entryId' => 902,
                ]).'"',
                false,
            )
            ->assertSee(
                'action="'.route('staff.live-queues.skip', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 92,
                    'entryId' => 902,
                ]).'"',
                false,
            )
            ->assertDontSee('Queue controls unavailable until Phase 10.5')
            ->assertDontSee('Called ticket controls unavailable until Phase 10.5')
            ->assertDontSee('action="'.route('staff.live-queues.call-next', [
                'businessId' => 10,
                'branchId' => 101,
                'queueId' => 92,
            ]).'"', false)
            ->assertDontSee('staff-live-disabled-button');

        $content = $response->getContent();

        foreach (['Pause', 'Close queue', 'Complete', 'Start serving', 'Recall', 'Skip'] as $label) {
            $this->assertEnabledSubmitButton($content, $label);
        }
    }

    public function test_call_next_action_is_enabled_when_waiting_and_no_ticket_is_called(): void
    {
        $this->fakeWorkspace($this->dashboard([
            $this->dashboardQueue(
                91,
                'Walk-in Support',
                'OPEN',
                'W',
                [$this->entry(903, 'W003', 'WAITING')],
            ),
        ]));

        $response = $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]));

        $response->assertOk()
            ->assertSee(
                'action="'.route('staff.live-queues.call-next', [
                    'businessId' => 10,
                    'branchId' => 101,
                    'queueId' => 91,
                ]).'"',
                false,
            )
            ->assertDontSee('staff-live-disabled-button');

        $this->assertEnabledSubmitButton($response->getContent(), 'Call next');
    }

    public function test_live_queues_is_the_only_active_staff_navigation_item(): void
    {
        $this->fakeWorkspace($this->dashboard());

        $response = $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]));

        $response->assertOk()
            ->assertSeeInOrder([
                'staff-nav-item staff-nav-item-active',
                '<span>Live Queues</span>',
            ], false)
            ->assertDontSee('staff-nav-item-disabled staff-nav-item-active', false);

        $this->assertSame(2, substr_count($response->getContent(), 'staff-nav-item-active'));
    }

    public function test_dashboard_401_clears_authentication_and_redirects_to_login(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response([
                'message' => 'Internal expired token detail',
            ], 401),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertRedirect(route('staff.login'))
            ->assertSessionHas('error', 'Your session has expired. Please sign in again.')
            ->assertSessionMissing('queueflow.auth');
    }

    public function test_dashboard_403_preserves_authentication_and_returns_safe_forbidden_response(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response([
                'message' => 'Internal branch authorization detail',
            ], 403),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertForbidden()
            ->assertSee('You are not authorized to access this staff resource.')
            ->assertDontSee('Internal branch authorization detail')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    public function test_dashboard_404_returns_safe_not_found_response(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response([
                'message' => 'Internal missing branch detail',
            ], 404),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Internal missing branch detail');
    }

    public function test_dashboard_invariant_conflict_uses_safe_unavailable_behavior(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response([
                'message' => 'Multiple serving entries violated an internal invariant',
            ], 409),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Multiple serving entries violated an internal invariant')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    public function test_dashboard_failure_returns_safe_503_and_preserves_authentication(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response([
                'message' => 'Internal dashboard failure',
            ], 500),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal dashboard failure')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_dashboard_connection_failure_returns_safe_503(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::failedConnection(),
        ]);

        $this->authenticated()
            ->get(route('staff.live-queues.index', [10, 101]))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
    }

    /** @param array<string, mixed> $dashboard */
    private function fakeWorkspace(array $dashboard): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/staff/dashboard' => Http::response($dashboard),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $responses
     * @param  list<array<string, mixed>>|null  $memberships
     */
    private function fake(array $responses = [], ?array $memberships = null): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response(
                $this->meResponse($memberships ?? [$this->membership()]),
            ),
            ...$responses,
        ]);
    }

    /** @param list<array<string, mixed>>|null $memberships */
    private function authenticated(?array $memberships = null): static
    {
        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => $memberships ?? [$this->membership()],
            ],
        ]);
    }

    /** @param list<array<string, mixed>> $memberships */
    private function meResponse(array $memberships): array
    {
        return ['user' => $this->user(), 'memberships' => $memberships];
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
    private function membership(?int $branchId = null): array
    {
        return ['businessId' => 10, 'branchId' => $branchId, 'role' => 'MANAGER'];
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
    private function branch(int $id = 101, string $name = 'Riverside Clinic'): array
    {
        return [
            'id' => $id,
            'businessId' => 10,
            'name' => $name,
            'address' => '1 River Road',
            'latitude' => 1.3,
            'longitude' => 103.8,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @param list<array<string, mixed>> $queues */
    private function dashboard(array $queues = []): array
    {
        return [
            'businessId' => 10,
            'branchId' => 101,
            'businessDate' => '2026-10-03',
            'queues' => $queues,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $waiting
     * @return array<string, mixed>
     */
    private function dashboardQueue(
        int $queueId,
        string $name,
        string $status,
        string $prefix,
        array $waiting = [],
        ?array $serving = null,
        ?array $called = null,
        ?int $serviceId = null,
    ): array {
        return [
            'queueId' => $queueId,
            'publicCode' => "queue-{$queueId}-public-code",
            'name' => $name,
            'status' => $status,
            'ticketPrefix' => $prefix,
            'service' => $serviceId === null ? null : [
                'id' => $serviceId,
                'name' => 'General Consultation',
                'durationMinutes' => 20,
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
    private function entry(int $entryId, string $ticketNumber, string $status): array
    {
        return [
            'entryId' => $entryId,
            'queueId' => 91,
            'serviceId' => 501,
            'userId' => null,
            'counterId' => null,
            'ticketSequence' => $entryId,
            'ticketNumber' => $ticketNumber,
            'status' => $status,
            'joinedAt' => '2026-10-03T09:00:00+08:00',
            'calledAt' => $status === 'CALLED' ? '2026-10-03T09:10:00+08:00' : null,
            'servingAt' => $status === 'SERVING' ? '2026-10-03T09:12:00+08:00' : null,
            'completedAt' => null,
            'cancelledAt' => null,
        ];
    }

    private function assertEnabledSubmitButton(string $content, string $label): void
    {
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*type="submit")[^>]*>.*?'.preg_quote($label, '/').'.*?<\/button>/s',
            $content,
            "Expected {$label} to render as a submit button.",
        );

        preg_match(
            '/<button\b(?=[^>]*type="submit")[^>]*>.*?'.preg_quote($label, '/').'.*?<\/button>/s',
            $content,
            $matches,
        );

        $openingTag = strtok($matches[0], '>');

        $this->assertStringNotContainsString(' disabled', $openingTag);
        $this->assertStringNotContainsString('aria-disabled', $openingTag);
    }
}
