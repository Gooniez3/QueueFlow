<?php

namespace Tests\Unit;

use App\Data\AuthUserData;
use App\Data\BusinessData;
use App\Data\LoginData;
use App\Data\RegisteredUserData;
use App\Data\StaffMembershipData;
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

    public function test_it_registers_a_user(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/register' => Http::response([
                'id' => 42,
                'email' => 'alex@example.com',
                'firstName' => 'Alex',
                'lastName' => 'Rivera',
                'phone' => '+65 6123 4567',
            ], 201),
        ]);

        $user = app(QueueFlowApiClient::class)->register(
            'alex@example.com',
            'correct horse battery staple',
            'Alex',
            'Rivera',
            '+65 6123 4567',
        );

        $this->assertInstanceOf(RegisteredUserData::class, $user);
        $this->assertSame(42, $user->id);
        $this->assertSame('alex@example.com', $user->email);
        $this->assertSame('Alex', $user->firstName);
        $this->assertSame('Rivera', $user->lastName);
        $this->assertSame('+65 6123 4567', $user->phone);

        Http::assertSent(
            fn ($request) => $request->method() === 'POST'
                && $request->url() === 'http://localhost:8080/api/v1/auth/register'
                && $request->data() === [
                    'email' => 'alex@example.com',
                    'password' => 'correct horse battery staple',
                    'firstName' => 'Alex',
                    'lastName' => 'Rivera',
                    'phone' => '+65 6123 4567',
                ]
        );
    }

    public function test_it_registers_a_user_with_null_phone(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/register' => Http::response([
                'id' => 43,
                'email' => 'sam@example.com',
                'firstName' => 'Sam',
                'lastName' => 'Tan',
                'phone' => null,
            ], 201),
        ]);

        $user = app(QueueFlowApiClient::class)->register(
            'sam@example.com',
            'another secure password',
            'Sam',
            'Tan',
        );

        $this->assertNull($user->phone);

        Http::assertSent(
            fn ($request) => $request->data() === [
                'email' => 'sam@example.com',
                'password' => 'another secure password',
                'firstName' => 'Sam',
                'lastName' => 'Tan',
                'phone' => null,
            ]
        );
    }

    public function test_it_preserves_duplicate_email_409_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/register' => Http::response([
                'message' => 'Email is already registered',
                'validationErrors' => [],
            ], 409),
        ]);

        try {
            app(QueueFlowApiClient::class)->register(
                'alex@example.com',
                'correct horse battery staple',
                'Alex',
                'Rivera',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(409, $exception->status);
            $this->assertSame('Email is already registered', $exception->getMessage());
            $this->assertSame([], $exception->validationErrors);
        }
    }

    public function test_it_preserves_registration_validation_400_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/register' => Http::response([
                'message' => 'Request validation failed',
                'validationErrors' => [
                    'password' => 'Password must be between 8 and 72 characters',
                ],
            ], 400),
        ]);

        try {
            app(QueueFlowApiClient::class)->register(
                'alex@example.com',
                'short',
                'Alex',
                'Rivera',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(400, $exception->status);
            $this->assertSame('Request validation failed', $exception->getMessage());
            $this->assertSame(
                'Password must be between 8 and 72 characters',
                $exception->validationErrors['password'],
            );
        }
    }

    public function test_it_logs_in_and_maps_the_authentication_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response([
                'token' => 'inert-login-token',
                'tokenType' => 'Bearer',
                'expiresAt' => '2030-04-15T10:30:00+08:00',
                'user' => [
                    'id' => 42,
                    'email' => 'alex@example.com',
                    'firstName' => 'Alex',
                    'lastName' => 'Rivera',
                    'phone' => null,
                ],
                'memberships' => [
                    [
                        'businessId' => 10,
                        'branchId' => 20,
                        'role' => 'MANAGER',
                    ],
                    [
                        'businessId' => 11,
                        'branchId' => null,
                        'role' => 'OWNER',
                    ],
                ],
            ]),
        ]);

        $login = app(QueueFlowApiClient::class)->login(
            'alex@example.com',
            'correct horse battery staple',
        );

        $this->assertInstanceOf(LoginData::class, $login);
        $this->assertSame('inert-login-token', $login->token);
        $this->assertSame('Bearer', $login->tokenType);
        $this->assertSame('2030-04-15T10:30:00+08:00', $login->expiresAt->format('Y-m-d\TH:i:sP'));
        $this->assertInstanceOf(AuthUserData::class, $login->user);
        $this->assertSame(42, $login->user->id);
        $this->assertNull($login->user->phone);
        $this->assertCount(2, $login->memberships);
        $this->assertInstanceOf(StaffMembershipData::class, $login->memberships[0]);
        $this->assertSame(20, $login->memberships[0]->branchId);
        $this->assertSame('MANAGER', $login->memberships[0]->role);
        $this->assertInstanceOf(StaffMembershipData::class, $login->memberships[1]);
        $this->assertNull($login->memberships[1]->branchId);
        $this->assertSame('OWNER', $login->memberships[1]->role);

        Http::assertSent(
            fn ($request) => $request->method() === 'POST'
                && $request->url() === 'http://localhost:8080/api/v1/auth/login'
                && $request->data() === [
                    'email' => 'alex@example.com',
                    'password' => 'correct horse battery staple',
                ]
        );
    }

    public function test_it_preserves_invalid_login_401_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => Http::response([
                'message' => 'Invalid email or password',
                'validationErrors' => [],
            ], 401),
        ]);

        try {
            app(QueueFlowApiClient::class)->login(
                'alex@example.com',
                'incorrect password',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(401, $exception->status);
            $this->assertSame('Invalid email or password', $exception->getMessage());
        }
    }

    public function test_it_fetches_the_current_user_with_bearer_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'user' => [
                    'id' => 42,
                    'email' => 'alex@example.com',
                    'firstName' => 'Alex',
                    'lastName' => 'Rivera',
                    'phone' => '+65 6123 4567',
                ],
                'memberships' => [
                    [
                        'businessId' => 10,
                        'branchId' => null,
                        'role' => 'OWNER',
                    ],
                ],
            ]),
        ]);

        $currentUser = app(QueueFlowApiClient::class)->currentUser('inert-current-user-token');

        $this->assertInstanceOf(AuthUserData::class, $currentUser['user']);
        $this->assertSame(42, $currentUser['user']->id);
        $this->assertSame('+65 6123 4567', $currentUser['user']->phone);
        $this->assertCount(1, $currentUser['memberships']);
        $this->assertInstanceOf(StaffMembershipData::class, $currentUser['memberships'][0]);
        $this->assertSame(10, $currentUser['memberships'][0]->businessId);
        $this->assertNull($currentUser['memberships'][0]->branchId);
        $this->assertSame('OWNER', $currentUser['memberships'][0]->role);

        Http::assertSent(
            fn ($request) => $request->method() === 'GET'
                && $request->url() === 'http://localhost:8080/api/v1/auth/me'
                && $request->hasHeader('Authorization', 'Bearer inert-current-user-token')
        );
    }

    public function test_it_preserves_current_user_401_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/me' => Http::response([
                'message' => 'Authentication is required',
                'validationErrors' => [],
            ], 401),
        ]);

        try {
            app(QueueFlowApiClient::class)->currentUser('inert-expired-token');

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(401, $exception->status);
            $this->assertSame('Authentication is required', $exception->getMessage());
        }
    }

    public function test_it_logs_out_with_bearer_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/logout' => Http::response(status: 200),
        ]);

        app(QueueFlowApiClient::class)->logout('inert-logout-token');

        Http::assertSent(
            fn ($request) => $request->method() === 'POST'
                && $request->url() === 'http://localhost:8080/api/v1/auth/logout'
                && $request->hasHeader('Authorization', 'Bearer inert-logout-token')
        );
    }

    public function test_it_maps_authentication_connection_failures(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://localhost:8080/api/v1/auth/login' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        try {
            app(QueueFlowApiClient::class)->login(
                'alex@example.com',
                'correct horse battery staple',
            );

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertNull($exception->status);
            $this->assertSame('Unable to connect to the QueueFlow API.', $exception->getMessage());
        }
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
