<?php

namespace Tests\Unit;

use App\Data\BusinessData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueueFlowApiClientTest extends TestCase
{
    public function test_it_fetches_businesses_from_queueflow_api(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                [
                    'id' => 1,
                    'name' => 'QueueFlow Clinic',
                    'description' => 'Medical clinic',
                    'createdAt' => '2026-09-30T20:00:00+08:00',
                ],
                [
                    'id' => 2,
                    'name' => 'Finn Cuts',
                    'description' => null,
                    'createdAt' => '2026-09-30T21:00:00+08:00',
                ],
            ]),
        ]);

        $businesses = app(QueueFlowApiClient::class)->businesses();

        $this->assertCount(2, $businesses);
        $this->assertInstanceOf(BusinessData::class, $businesses[0]);

        $this->assertSame(1, $businesses[0]->id);
        $this->assertSame('QueueFlow Clinic', $businesses[0]->name);
        $this->assertSame('Medical clinic', $businesses[0]->description);

        $this->assertSame(2, $businesses[1]->id);
        $this->assertSame('Finn Cuts', $businesses[1]->name);
        $this->assertNull($businesses[1]->description);

        $this->assertSame(
            '+08:00',
            $businesses[0]->createdAt->format('P')
        );

        Http::assertSent(
            fn ($request) => $request->method() === 'GET'
                && $request->url() === 'http://localhost:8080/api/v1/businesses'
        );
    }

    public function test_it_fetches_a_business_by_id(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/businesses/42' => Http::response([
                'id' => 42,
                'name' => 'QueueFlow Barber',
                'description' => null,
                'createdAt' => '2026-09-30T20:00:00+08:00',
            ]),
        ]);

        $business = app(QueueFlowApiClient::class)->business(42);

        $this->assertInstanceOf(BusinessData::class, $business);
        $this->assertSame(42, $business->id);
        $this->assertSame('QueueFlow Barber', $business->name);
        $this->assertNull($business->description);
        $this->assertSame('+08:00', $business->createdAt->format('P'));

        Http::assertSent(
            fn ($request) => $request->method() === 'GET'
                && $request->url() === 'http://localhost:8080/api/v1/businesses/42'
        );
    }

    public function test_it_creates_a_business_through_queueflow_api(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'id' => 10,
                'name' => 'QueueFlow Clinic',
                'description' => 'Medical clinic',
                'createdAt' => '2026-09-30T22:00:00+08:00',
            ], 201),
        ]);

        $business = app(QueueFlowApiClient::class)->createBusiness(
            'QueueFlow Clinic',
            'Medical clinic'
        );

        $this->assertInstanceOf(BusinessData::class, $business);
        $this->assertSame(10, $business->id);
        $this->assertSame('QueueFlow Clinic', $business->name);
        $this->assertSame('Medical clinic', $business->description);
        $this->assertSame('+08:00', $business->createdAt->format('P'));

        Http::assertSent(
            fn ($request) => $request->method() === 'POST'
                && $request->url() === 'http://localhost:8080/api/v1/businesses'
                && $request['name'] === 'QueueFlow Clinic'
                && $request['description'] === 'Medical clinic'
        );
    }

    public function test_it_preserves_validation_errors_from_spring(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'timestamp' => '2026-09-30T22:00:00+08:00',
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'Validation failed',
                'path' => '/api/v1/businesses',
                'validationErrors' => [
                    'name' => 'Business name is required',
                ],
            ], 400),
        ]);

        try {
            app(QueueFlowApiClient::class)->createBusiness('', null);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(400, $exception->status);
            $this->assertSame('Validation failed', $exception->getMessage());
            $this->assertSame(
                'Business name is required',
                $exception->validationErrors['name']
            );
        }
    }

    public function test_it_handles_business_not_found(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/businesses/999' => Http::response([
                'timestamp' => '2026-09-30T22:00:00+08:00',
                'status' => 404,
                'error' => 'Not Found',
                'message' => 'Business not found with id: 999',
                'path' => '/api/v1/businesses/999',
                'validationErrors' => [],
            ], 404),
        ]);

        try {
            app(QueueFlowApiClient::class)->business(999);

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(404, $exception->status);
            $this->assertSame(
                'Business not found with id: 999',
                $exception->getMessage()
            );
        }
    }

    public function test_it_handles_server_errors(): void
    {
        Http::fake([
            'http://localhost:8080/api/v1/businesses' => Http::response([
                'message' => 'Internal server error',
            ], 500),
        ]);

        try {
            app(QueueFlowApiClient::class)->businesses();

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(500, $exception->status);
            $this->assertSame(
                'Internal server error',
                $exception->getMessage()
            );
        }
    }

    public function test_it_handles_connection_failures(): void
    {
        Http::fake(function () {
            throw new ConnectionException(
                'Connection refused'
            );
        });

        try {
            app(QueueFlowApiClient::class)->businesses();

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame(
                'Unable to connect to the QueueFlow API.',
                $exception->getMessage()
            );
        }
    }
}
