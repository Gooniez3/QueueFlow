<?php

namespace Tests\Feature\Staff;

use Illuminate\Http\Client\Request;
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
            ->assertSee('Add service')
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
            ->assertDontSee('inert-spring-token');
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

    /**
     * @param  array<string, mixed>  $responses
     */
    private function fake(array $responses = []): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => $this->user(),
                'memberships' => [$this->membership()],
            ]),
            'http://localhost:8080/api/v1/businesses/10/branches/101' => Http::response($this->branch()),
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
    private function branch(): array
    {
        return [
            'id' => 101,
            'businessId' => 10,
            'name' => 'Riverside Clinic',
            'address' => '1 River Road',
            'latitude' => null,
            'longitude' => null,
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
}
