<?php

namespace Tests\Feature;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerBusinessDiscoveryTest extends TestCase
{
    public function test_public_business_page_displays_real_branches_and_links(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([
                $this->branch(21, 'Riverside Clinic'),
                $this->branch(22, 'Downtown Clinic'),
            ]),
        ]);

        $response = $this->get(route('businesses.show', 10));

        $response->assertOk()
            ->assertViewIs('businesses.show')
            ->assertSee('Northstar Health')
            ->assertSee('Riverside Clinic')
            ->assertSee('Downtown Clinic')
            ->assertSee('href="'.route('places.index').'"', false)
            ->assertSee('href="'.route('branches.show', [10, 21]).'"', false)
            ->assertSee('href="'.route('branches.show', [10, 22]).'"', false)
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
        Http::assertSentCount(2);
    }

    public function test_public_business_page_renders_location_empty_state(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([]),
        ]);

        $this->get(route('businesses.show', 10))
            ->assertOk()
            ->assertSee('No locations are available.')
            ->assertSee('Please check again later.');

        Http::assertSentCount(2);
    }

    public function test_missing_public_business_returns_safe_404(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/99' => Http::response([
                'message' => 'Business not found with internal persistence detail',
            ], 404),
        ]);

        $response = $this->get(route('businesses.show', 99));

        $response->assertNotFound()
            ->assertSee('The requested QueueFlow resource was not found.')
            ->assertDontSee('internal persistence detail');
        Exceptions::assertNothingReported();
    }

    public function test_public_business_api_failure_is_customer_safe(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response([
                'message' => 'Internal Spring failure',
            ], 500),
        ]);

        $response = $this->get(route('businesses.show', 10));

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring failure');
        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_business_branch_list_relationship_mismatch_returns_404(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses/10' => Http::response($this->business()),
            'http://localhost:8080/api/v1/businesses/10/branches' => Http::response([[
                ...$this->branch(21, 'Wrong Business Branch'),
                'businessId' => 99,
            ]]),
        ]);

        $this->get(route('businesses.show', 10))
            ->assertNotFound()
            ->assertDontSee('Wrong Business Branch');
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
    private function branch(int $id, string $name): array
    {
        return [
            'id' => $id,
            'businessId' => 10,
            'name' => $name,
            'address' => '10 River Road, Singapore',
            'latitude' => null,
            'longitude' => null,
            'timezone' => 'Asia/Singapore',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
