<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerBranchDiscoveryTest extends TestCase
{
    public function test_public_nested_branch_displays_services_and_active_links(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/21' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/21/services' => Http::response([
                $this->service(31, 'General Consultation'),
                $this->service(32, 'Inactive Service', active: false),
            ]),
        ]);

        $response = $this->get(route('branches.show', [10, 21]));

        $response->assertOk()
            ->assertViewIs('branches.show')
            ->assertSee('Riverside Clinic')
            ->assertSee('General Consultation')
            ->assertSee('Inactive Service')
            ->assertSee('Unavailable')
            ->assertSee('href="'.route('services.show', [10, 21, 31]).'"', false)
            ->assertDontSee('href="'.route('services.show', [10, 21, 32]).'"', false)
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
        Http::assertSentCount(3);
    }

    public function test_branch_business_relationship_mismatch_returns_404_before_services_lookup(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/21' => Http::response([
                ...$this->branch(),
                'businessId' => 99,
            ]),
        ]);

        $this->get(route('branches.show', [10, 21]))
            ->assertNotFound()
            ->assertDontSee('General Consultation');

        Http::assertSentCount(2);
    }

    public function test_branch_service_list_relationship_mismatch_returns_404(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches/21' => Http::response($this->branch()),
            'http://localhost:8080/api/v1/businesses/10/branches/21/services' => Http::response([[
                ...$this->service(31, 'Wrong Branch Service'),
                'branchId' => 22,
            ]]),
        ]);

        $this->get(route('branches.show', [10, 21]))
            ->assertNotFound()
            ->assertDontSee('Wrong Branch Service');
    }

    /** @return array<string, mixed> */
    private function business(): array
    {
        return [
            'id' => 10,
            'name' => 'Northstar Health',
            'description' => 'Neighbourhood healthcare.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function branch(): array
    {
        return [
            'id' => 21,
            'businessId' => 10,
            'name' => 'Riverside Clinic',
            'address' => '10 River Road, Singapore',
            'latitude' => null,
            'longitude' => null,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }

    /** @return array<string, mixed> */
    private function service(int $id, string $name, bool $active = true): array
    {
        return [
            'id' => $id,
            'branchId' => 21,
            'name' => $name,
            'description' => 'Customer care service.',
            'durationMinutes' => 20,
            'active' => $active,
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
