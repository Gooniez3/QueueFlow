<?php

namespace Tests\Feature;

use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerHomeTest extends TestCase
{
    public function test_customer_home_uses_real_public_businesses_and_links(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                $this->business(10, 'Northstar Health'),
                $this->business(20, 'Harbour Services'),
            ]),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertViewIs('home')
            ->assertSee('Where do you need service?')
            ->assertSee('Northstar Health')
            ->assertSee('Harbour Services')
            ->assertSee('href="'.route('businesses.show', 10).'"', false)
            ->assertSee('href="'.route('businesses.show', 20).'"', false)
            ->assertSee('data-customer-navigation', false)
            ->assertDontSee('A023')
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses'
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(1);
    }

    public function test_customer_home_renders_safe_503_for_spring_5xx(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'message' => 'Internal Spring database detail',
            ], 500),
        ]);

        $response = $this->get(route('home'));

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Internal Spring database detail');
        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === 500,
        );
    }

    public function test_customer_home_renders_safe_503_for_connection_failure(): void
    {
        Exceptions::fake([QueueFlowApiException::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::failedConnection(
                'Connection refused with backend URL',
            ),
        ]);

        $response = $this->get(route('home'));

        $response->assertServiceUnavailable()
            ->assertSee('QueueFlow is temporarily unavailable. Please try again later.')
            ->assertDontSee('Connection refused')
            ->assertDontSee('Unable to connect to the QueueFlow API.');
        Exceptions::assertReported(
            fn (QueueFlowApiException $exception): bool => $exception->status === null,
        );
    }

    public function test_customer_home_has_the_root_route(): void
    {
        $this->assertSame(url('/'), route('home'));
    }

    /** @return array<string, mixed> */
    private function business(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Customer-facing services.',
            'createdAt' => '2026-09-30T10:15:30+08:00',
        ];
    }
}
