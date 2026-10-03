<?php

namespace Tests\Feature;

use App\Data\QueuePositionData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowCustomerQueueService;
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
            ->assertSee('Hi, Guest!')
            ->assertSee('No ticket yet.')
            ->assertSee('Open scanner')
            ->assertSee('LIVE QUEUE')
            ->assertSee('Northstar Health')
            ->assertSee('Harbour Services')
            ->assertSee('href="'.route('businesses.show', 10).'"', false)
            ->assertSee('href="'.route('businesses.show', 20).'"', false)
            ->assertSee('data-customer-navigation', false)
            ->assertSee('data-active-destination="home"', false)
            ->assertSee('data-customer-bottom-navigation', false)
            ->assertSee('Home')
            ->assertSee('Places')
            ->assertSee('Tickets')
            ->assertSee('Account')
            ->assertSee('More')
            ->assertSee('href="'.route('account.show').'"', false)
            ->assertSee('href="'.route('more.show').'"', false)
            ->assertDontSee('href="#"', false)
            ->assertDontSee('Explore')
            ->assertDontSee('A023')
            ->assertDontSee('inert-spring-token');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'http://localhost:8080/api/v1/businesses'
            && ! $request->hasHeader('Authorization'));
        Http::assertSentCount(1);
    }

    public function test_customer_home_renders_business_empty_state(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('No ticket yet.')
            ->assertSee('QueueFlow Clinic')
            ->assertSee('Open scanner');

        Http::assertSentCount(1);
    }

    public function test_customer_home_renders_real_owned_ticket_with_live_spring_position(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                $this->business(10, 'Northstar Health'),
            ]),
        ]);
        $customerQueueService = $this->mock(QueueFlowCustomerQueueService::class);
        $customerQueueService->shouldReceive('position')->once()->with(91, 301)->andReturn(
            new QueuePositionData(301, 91, 31, 23, 'A023', 'WAITING', 5, 15),
        );

        $response = $this->withSession([
            'queueflow.customer.entries' => [
                '91:301' => [
                    'businessId' => 10,
                    'branchId' => 21,
                    'serviceId' => 31,
                    'queueId' => 91,
                    'entryId' => 301,
                    'ticketNumber' => 'A023',
                    'guestToken' => 'raw-home-token',
                ],
            ],
        ])->get(route('home'));

        $response->assertOk()
            ->assertSee('Your ticket is active')
            ->assertSee('Northstar Health')
            ->assertSee('Your saved ticket')
            ->assertSee('A023')
            ->assertSee('5')
            ->assertSee('15 min')
            ->assertSee('data-demo-qr', false)
            ->assertDontSee('raw-home-token');
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
            ->assertSee('data-customer-navigation', false)
            ->assertSee('Back to discovery')
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
