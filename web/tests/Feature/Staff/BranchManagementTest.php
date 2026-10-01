<?php

namespace Tests\Feature\Staff;

use Illuminate\Http\Client\Request;
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
                $this->branch(102, 'Central Clinic', null, null),
            ]),
        ]);

        $response = $this->authenticated()->get('/staff/businesses/10');

        $response->assertOk()
            ->assertSee('Riverside Clinic')
            ->assertSee('Central Clinic')
            ->assertSee('Add branch')
            ->assertDontSee('inert-spring-token');

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
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
