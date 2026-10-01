<?php

namespace Tests\Feature\Staff;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessManagementTest extends TestCase
{
    public function test_business_management_requires_staff_authentication(): void
    {
        $this->get('/staff/businesses')
            ->assertRedirect(route('staff.login'));
    }

    public function test_staff_without_memberships_sees_business_empty_state(): void
    {
        $this->fakeCurrentUser([]);

        $response = $this->withAuthentication([])
            ->get('/staff/businesses');

        $response->assertOk()
            ->assertSee('Create your first business')
            ->assertSee('Create business')
            ->assertDontSee('inert-spring-token');

        Http::assertSentCount(1);
    }

    public function test_business_index_resolves_only_membership_businesses_without_bearer_authentication(): void
    {
        $memberships = [
            $this->membership(10, 'OWNER'),
            $this->membership(20, 'STAFF'),
            $this->membership(10, 'MANAGER'),
        ];

        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse($memberships)),
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business(10, 'Northstar Health')),
            'http://localhost:8080/api/v1/businesses/20' => Http::response($this->business(20, 'Harbour Services')),
        ]);

        $response = $this->withAuthentication($memberships)
            ->get('/staff/businesses');

        $response->assertOk()
            ->assertSee('Northstar Health')
            ->assertSee('Harbour Services')
            ->assertDontSee('Unmanaged Business')
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => in_array($request->url(), [
            'http://localhost:8080/api/v1/businesses/10',
            'http://localhost:8080/api/v1/businesses/20',
        ], true)
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(3);
    }

    public function test_business_creation_is_authenticated_and_refreshes_membership_context(): void
    {
        $newMembership = $this->membership(30, 'OWNER');

        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::sequence()
                ->push($this->meResponse([]))
                ->push($this->meResponse([$newMembership])),
            'http://localhost:8080/api/v1/businesses' => Http::response(
                $this->business(30, 'Riverbend Clinic', 'Neighbourhood care.'),
                201,
            ),
        ]);

        $response = $this->withAuthentication([])->post('/staff/businesses', [
            'name' => 'Riverbend Clinic',
            'description' => 'Neighbourhood care.',
        ]);

        $response->assertRedirect(route('staff.businesses.show', 30))
            ->assertSessionHas('status', 'Business created successfully.')
            ->assertSessionHas('queueflow.auth.memberships.0.businessId', 30)
            ->assertSessionHas('queueflow.auth.memberships.0.role', 'OWNER');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'http://localhost:8080/api/v1/businesses'
            && $request->hasHeader('Authorization', 'Bearer inert-spring-token')
            && $request->data() === [
                'name' => 'Riverbend Clinic',
                'description' => 'Neighbourhood care.',
            ]);
        Http::assertSentCount(3);
    }

    public function test_business_form_validation_prevents_an_api_mutation(): void
    {
        $this->fakeCurrentUser([]);

        $response = $this->withAuthentication([])
            ->from('/staff/businesses/create')
            ->post('/staff/businesses', ['name' => '']);

        $response->assertRedirect('/staff/businesses/create')
            ->assertSessionHasErrors('name');
        Http::assertSentCount(1);
    }

    public function test_spring_business_validation_errors_are_mapped_without_leaking_internal_details(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse([])),
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'message' => 'Internal Spring validation detail',
                'validationErrors' => ['name' => 'A business with this name already exists.'],
            ], 400),
        ]);

        $response = $this->withAuthentication([])
            ->from('/staff/businesses/create')
            ->post('/staff/businesses', ['name' => 'Northstar Health']);

        $response->assertRedirect('/staff/businesses/create')
            ->assertSessionHasErrors([
                'name' => 'A business with this name already exists.',
            ])
            ->assertDontSee('Internal Spring validation detail');
    }

    public function test_business_membership_guard_denies_unmanaged_business_and_preserves_authentication(): void
    {
        $this->fakeCurrentUser([$this->membership(10, 'STAFF')]);

        $response = $this->withAuthentication([$this->membership(10, 'STAFF')])
            ->get('/staff/businesses/99');

        $response->assertForbidden()
            ->assertSee('You are not authorized to manage this business.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token');
        Http::assertSentCount(1);
    }

    public function test_business_mutation_401_clears_authentication_and_redirects_safely(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse([])),
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'message' => 'Internal Spring token detail',
            ], 401),
        ]);

        $response = $this->withAuthentication([])
            ->post('/staff/businesses', ['name' => 'Riverbend Clinic']);

        $response->assertRedirect(route('staff.login'))
            ->assertSessionHas('error', 'Your session has expired. Please sign in again.')
            ->assertSessionMissing('queueflow.auth')
            ->assertDontSee('Internal Spring token detail');
    }

    public function test_business_mutation_403_is_safe_and_preserves_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse([])),
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'message' => 'Internal Spring authorization detail',
            ], 403),
        ]);

        $response = $this->withAuthentication([])
            ->post('/staff/businesses', ['name' => 'Riverbend Clinic']);

        $response->assertForbidden()
            ->assertSee('You are not authorized to access this staff resource.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token')
            ->assertDontSee('Internal Spring authorization detail');
    }

    public function test_business_not_found_and_network_failures_are_safe(): void
    {
        $membership = $this->membership(10, 'OWNER');

        Exceptions::fake([QueueFlowApiException::class]);

        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse([$membership])),
            'http://localhost:8080/api/v1/businesses/10' => Http::response([
                'message' => 'Internal Spring database detail',
            ], 404),
        ]);

        $this->withAuthentication([$membership])
            ->get('/staff/businesses/10')
            ->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('Internal Spring database detail');

        Exceptions::assertNothingReported();

        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse([$membership])),
            'http://localhost:8080/api/v1/businesses/10' => Http::failedConnection(),
        ]);

        $response = $this->withAuthentication([$membership])
            ->get('/staff/businesses/10')
            ->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertSessionHas('queueflow.auth.token', 'inert-spring-token')
            ->assertDontSee('Unable to connect to the QueueFlow API.');

        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === null
                && $exception->getMessage() === 'Unable to connect to the QueueFlow API.',
        );
        $this->assertSame(503, $response->getStatusCode());
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     */
    private function fakeCurrentUser(array $memberships): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response($this->meResponse($memberships)),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     */
    private function withAuthentication(array $memberships): static
    {
        return $this->withSession([
            'queueflow.auth' => [
                'token' => 'inert-spring-token',
                'user' => $this->user(),
                'memberships' => $memberships,
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $memberships
     * @return array<string, mixed>
     */
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
    private function membership(int $businessId, string $role): array
    {
        return ['businessId' => $businessId, 'branchId' => null, 'role' => $role];
    }

    /** @return array<string, mixed> */
    private function business(int $id, string $name, ?string $description = null): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
