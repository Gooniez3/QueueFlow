<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    public function test_business_page_lists_branches_using_public_get_requests(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(101, 'Riverside Clinic'),
                [
                    ...$this->branch(102, 'Central Clinic', null, null),
                    'address' => '88 Orchard Avenue',
                ],
            ]),
        ]);

        $response = $this->authenticated()->get('/staff/businesses/10');

        $response->assertOk()
            ->assertSee('Riverside Clinic')
            ->assertSee('Central Clinic')
            ->assertSee('1 River Road')
            ->assertSee('88 Orchard Avenue')
            ->assertSee('Northstar Health')
            ->assertSee('Community health services.')
            ->assertSee('Business overview')
            ->assertSee('Business information')
            ->assertSee('Branches')
            ->assertSee('&middot; 2', false)
            ->assertSee('STAFF')
            ->assertSee('href="'.route('staff.branches.create', 10).'"', false)
            ->assertSee('href="'.route('staff.branches.show', [10, 101]).'"', false)
            ->assertSee('href="'.route('staff.branches.show', [10, 102]).'"', false)
            ->assertSee('Live queues')
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee('Edit business')
            ->assertDontSee('href="#"', false)
            ->assertDontSee('Central Branch')
            ->assertDontSee('Harbour Branch')
            ->assertDontSee('OWNER')
            ->assertDontSee('inert-spring-token');

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'href="'.route('staff.branches.create', 10).'"'),
        );

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/v1/businesses/10')
            && ! str_ends_with($request->url(), '/auth/me')
            && ! $request->hasHeader('Authorization'));
    }

    public function test_branch_creation_uses_bearer_authentication_and_allows_omitted_coordinates(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response(
                $this->branch(101, 'Riverside Clinic', null, null),
                201,
            ),
        ]);

        $response = $this->authenticated()->post('/staff/businesses/10/branches', [
            'name' => 'Riverside Clinic',
            'address' => '1 River Road',
        ]);

        $response->assertRedirect(route('staff.branches.show', [10, 101]))
            ->assertSessionHas('status', 'Branch created successfully.');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/businesses/10/branches'
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token')
            && $request->data() === [
                'name' => 'Riverside Clinic',
                'address' => '1 River Road',
                'latitude' => null,
                'longitude' => null,
            ]);
    }

    public function test_branch_form_validates_coordinate_ranges_before_mutation(): void
    {
        $this->fake();

        $response = $this->authenticated()
            ->from('/staff/businesses/10/branches/create')
            ->post('/staff/businesses/10/branches', [
                'name' => 'Riverside Clinic',
                'address' => '1 River Road',
                'latitude' => 91,
                'longitude' => -181,
            ]);

        $response->assertRedirect('/staff/businesses/10/branches/create')
            ->assertSessionHasErrors(['latitude', 'longitude']);
        Http::assertSentCount(1);
    }

    public function test_spring_branch_validation_errors_are_mapped_to_fields(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                'message' => 'Internal Spring validation detail',
                'validationErrors' => [
                    'name' => 'Name is already in use.',
                    'latitude' => 'Latitude is outside the supported range.',
                ],
            ], 400),
        ]);

        $response = $this->authenticated()
            ->from('/staff/businesses/10/branches/create')
            ->post('/staff/businesses/10/branches', [
                'name' => 'Riverside Clinic',
                'address' => '1 River Road',
                'latitude' => 1.3,
            ]);

        $response->assertRedirect('/staff/businesses/10/branches/create')
            ->assertSessionHasErrors([
                'name' => 'Name is already in use.',
                'latitude' => 'Latitude is outside the supported range.',
            ])
            ->assertDontSee('Internal Spring validation detail');
    }

    public function test_branch_detail_rejects_a_response_for_another_business(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response([
                ...$this->branch(),
                'businessId' => 99,
            ]),
        ]);

        $this->authenticated()
            ->get('/staff/businesses/10/branches/101')
            ->assertNotFound();
    }

    public function test_authorized_staff_can_view_real_branch_context_and_service_links(): void
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
            ->get(route('staff.branches.show', [10, 101]));

        $response->assertOk()
            ->assertSee('Riverside Clinic')
            ->assertSee('Branch of Northstar Health')
            ->assertSee('1 River Road')
            ->assertSee('Asia/Singapore')
            ->assertSee('General Consultation')
            ->assertSee('Standard appointment.')
            ->assertSee('20 min')
            ->assertSee('Active')
            ->assertSee('Follow-up Consultation')
            ->assertSee('Inactive')
            ->assertSee('href="'.route('staff.services.show', [10, 101, 501]).'"', false)
            ->assertSee('href="'.route('staff.services.show', [10, 101, 502]).'"', false)
            ->assertSee('href="'.route('staff.services.create', [10, 101]).'"', false)
            ->assertSee('aria-label="Staff page context"', false)
            ->assertSeeInOrder([
                'staff-nav-item staff-nav-item-disabled staff-nav-item-active',
                '<span>Branches</span>',
            ], false)
            ->assertDontSee('Edit branch')
            ->assertDontSee('Open live queues')
            ->assertDontSee("Today's queues")
            ->assertDontSee('waiting today')
            ->assertDontSee('inert-spring-token');

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'href="'.route('staff.services.create', [10, 101]).'"'),
        );
    }

    public function test_branch_detail_displays_a_service_empty_state(): void
    {
        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/101/services' => Http::response([]),
        ]);

        $this->authenticated()
            ->get(route('staff.branches.show', [10, 101]))
            ->assertOk()
            ->assertSee('No services yet')
            ->assertSee('Create the first service customers can receive at this branch.')
            ->assertSee('href="'.route('staff.services.create', [10, 101]).'"', false);
    }

    public function test_branch_detail_rejects_staff_without_business_membership(): void
    {
        $otherMembership = ['businessId' => 99, 'branchId' => null, 'role' => 'STAFF'];
        $this->fake([], $otherMembership);

        $this->authenticated($otherMembership)
            ->get(route('staff.branches.show', [10, 101]))
            ->assertForbidden()
            ->assertSee('You are not authorized to manage this business.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Http::assertSentCount(1);
    }

    public function test_branch_detail_upstream_failure_is_safe(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);

        $this->fake([
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response([
                'message' => 'Internal Spring branch failure',
            ], 500),
        ]);

        $this->authenticated()
            ->get(route('staff.branches.show', [10, 101]))
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring branch failure')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
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
    private function membership(): array
    {
        return ['businessId' => 10, 'branchId' => null, 'role' => 'STAFF'];
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
    private function branch(
        int $id = 101,
        string $name = 'Riverside Clinic',
        ?float $latitude = 1.3521,
        ?float $longitude = 103.8198,
    ): array {
        return [
            'id' => $id,
            'businessId' => 10,
            'name' => $name,
            'address' => '1 River Road',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function service(int $id, string $name, bool $active): array
    {
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
}
