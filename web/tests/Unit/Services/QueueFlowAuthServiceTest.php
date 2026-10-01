<?php

namespace Tests\Unit\Services;

use App\Data\AuthUserData;
use App\Data\LoginData;
use App\Data\RegisteredUserData;
use App\Data\StaffMembershipData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowApiClient;
use App\Services\QueueFlowAuthService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Mockery;
use Tests\TestCase;

class QueueFlowAuthServiceTest extends TestCase
{
    public function test_registration_delegates_without_creating_authentication_state(): void
    {
        $registeredUser = new RegisteredUserData(
            id: 42,
            email: 'alex@example.com',
            firstName: 'Alex',
            lastName: 'Rivera',
            phone: null,
        );

        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('register')
            ->once()
            ->with(
                'alex@example.com',
                'inert-password',
                'Alex',
                'Rivera',
                null,
            )
            ->andReturn($registeredUser);

        $service = new QueueFlowAuthService(
            $apiClient,
            $session = $this->sessionStore(),
        );

        $result = $service->register(
            'alex@example.com',
            'inert-password',
            'Alex',
            'Rivera',
        );

        $this->assertSame($registeredUser, $result);
        $this->assertFalse($service->hasAuthSession());
        $this->assertNull($session->get('queueflow.auth'));
    }

    public function test_successful_login_stores_authentication_state_without_password(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('login')
            ->once()
            ->with('alex@example.com', 'inert-password')
            ->andReturn($this->loginData());

        $session = $this->sessionStore();
        $service = new QueueFlowAuthService($apiClient, $session);

        $context = $service->login('alex@example.com', 'inert-password');

        $this->assertSame([
            'token' => 'inert-spring-token',
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
                    'branchId' => null,
                    'role' => 'OWNER',
                ],
            ],
        ], $session->get('queueflow.auth'));

        $this->assertArrayNotHasKey('token', $context);
        $this->assertArrayNotHasKey('password', $session->get('queueflow.auth'));
        $this->assertStringNotContainsString(
            'inert-password',
            json_encode($session->all(), JSON_THROW_ON_ERROR),
        );
    }

    public function test_successful_login_regenerates_the_session_id(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('login')
            ->once()
            ->andReturn($this->loginData());

        $session = $this->sessionStore();
        $originalSessionId = $session->getId();
        $service = new QueueFlowAuthService($apiClient, $session);

        $service->login('alex@example.com', 'inert-password');

        $this->assertNotSame($originalSessionId, $session->getId());
    }

    public function test_login_api_failure_does_not_establish_local_authentication(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('login')
            ->once()
            ->andThrow(
                new QueueFlowApiException(
                    'Invalid email or password',
                    401,
                ),
            );

        $session = $this->sessionStore();
        $originalSessionId = $session->getId();
        $service = new QueueFlowAuthService($apiClient, $session);

        try {
            $service->login('alex@example.com', 'incorrect-password');

            $this->fail('Expected QueueFlowApiException was not thrown.');
        } catch (QueueFlowApiException $exception) {
            $this->assertSame(401, $exception->status);
            $this->assertFalse($service->hasAuthSession());
            $this->assertNull($session->get('queueflow.auth'));
            $this->assertSame($originalSessionId, $session->getId());
        }
    }

    public function test_authentication_state_reflects_the_server_side_token(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $session = $this->sessionStore(),
        );

        $this->assertFalse($service->hasAuthSession());

        $session->put('queueflow.auth', [
            'token' => 'inert-spring-token',
        ]);

        $this->assertTrue($service->hasAuthSession());
    }

    public function test_cached_context_exposes_no_raw_token_and_preserves_nullable_branch(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $session = $this->sessionStore(),
        );

        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );

        $context = $service->cachedContext();

        $this->assertNotNull($context);
        $this->assertArrayNotHasKey('token', $context);
        $this->assertInstanceOf(
            AuthUserData::class,
            $context['user'],
        );
        $this->assertInstanceOf(
            StaffMembershipData::class,
            $context['memberships'][0],
        );
        $this->assertNull(
            $context['memberships'][0]->branchId,
        );
        $this->assertSame(
            'OWNER',
            $context['memberships'][0]->role,
        );
    }

    public function test_cached_context_returns_null_without_authentication_state(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $this->sessionStore(),
        );

        $context = $service->cachedContext();

        $this->assertNull($context);
    }

    public function test_current_user_forwards_the_stored_token_and_refreshes_context(): void
    {
        $refreshedContext = [
            'user' => new AuthUserData(
                id: 42,
                email: 'alex@example.com',
                firstName: 'Alexandra',
                lastName: 'Rivera',
                phone: '+65 6123 4567',
            ),
            'memberships' => [
                new StaffMembershipData(
                    businessId: 10,
                    branchId: null,
                    role: 'MANAGER',
                ),
            ],
        ];

        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('currentUser')
            ->once()
            ->with('inert-spring-token')
            ->andReturn($refreshedContext);

        $session = $this->sessionStore();
        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );

        $service = new QueueFlowAuthService(
            $apiClient,
            $session,
        );

        $context = $service->currentUser();

        $this->assertSame($refreshedContext, $context);
        $this->assertSame(
            'inert-spring-token',
            $session->get('queueflow.auth.token'),
        );
        $this->assertSame(
            'Alexandra',
            $session->get('queueflow.auth.user.firstName'),
        );
        $this->assertNull(
            $session->get(
                'queueflow.auth.memberships.0.branchId',
            ),
        );
        $this->assertSame(
            'MANAGER',
            $session->get(
                'queueflow.auth.memberships.0.role',
            ),
        );
    }

    public function test_current_user_401_clears_local_authentication_and_preserves_the_error(): void
    {
        $apiException = new QueueFlowApiException(
            'Authentication is required',
            401,
        );

        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('currentUser')
            ->once()
            ->with('inert-spring-token')
            ->andThrow($apiException);

        $session = $this->sessionStore();
        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );
        $session->put('unrelated', 'preserved');

        $service = new QueueFlowAuthService(
            $apiClient,
            $session,
        );

        try {
            $service->currentUser();

            $this->fail(
                'Expected QueueFlowApiException was not thrown.',
            );
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertFalse($service->hasAuthSession());
            $this->assertNull(
                $session->get('queueflow.auth'),
            );
            $this->assertSame(
                'preserved',
                $session->get('unrelated'),
            );
        }
    }

    public function test_current_user_non_401_error_preserves_local_authentication(): void
    {
        $apiException = new QueueFlowApiException(
            'QueueFlow API request failed.',
            500,
        );

        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('currentUser')
            ->once()
            ->with('inert-spring-token')
            ->andThrow($apiException);

        $session = $this->sessionStore();
        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );

        $service = new QueueFlowAuthService(
            $apiClient,
            $session,
        );

        try {
            $service->currentUser();

            $this->fail(
                'Expected QueueFlowApiException was not thrown.',
            );
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertTrue($service->hasAuthSession());
        }
    }

    public function test_current_user_without_authentication_state_returns_null(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldNotReceive('currentUser');

        $service = new QueueFlowAuthService(
            $apiClient,
            $this->sessionStore(),
        );

        $context = $service->currentUser();

        $this->assertNull($context);
    }

    public function test_successful_logout_revokes_spring_session_and_invalidates_local_session(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('logout')
            ->once()
            ->with('inert-spring-token');

        $session = $this->sessionStore();
        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );
        $session->put('unrelated', 'cleared');

        $originalSessionId = $session->getId();
        $originalCsrfToken = $session->token();

        $service = new QueueFlowAuthService(
            $apiClient,
            $session,
        );

        $service->logout();

        $this->assertFalse($service->hasAuthSession());
        $this->assertNull(
            $session->get('queueflow.auth'),
        );
        $this->assertNull(
            $session->get('unrelated'),
        );
        $this->assertNotSame(
            $originalSessionId,
            $session->getId(),
        );
        $this->assertNotSame(
            $originalCsrfToken,
            $session->token(),
        );
    }

    public function test_logout_without_token_is_safe_and_idempotent(): void
    {
        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldNotReceive('logout');

        $service = new QueueFlowAuthService(
            $apiClient,
            $this->sessionStore(),
        );

        $service->logout();
        $service->logout();

        $this->assertFalse($service->hasAuthSession());
    }

    public function test_spring_logout_failure_still_invalidates_local_session_and_is_rethrown(): void
    {
        $apiException = new QueueFlowApiException(
            'Unable to connect to the QueueFlow API.',
        );

        $apiClient = Mockery::mock(QueueFlowApiClient::class);
        $apiClient->shouldReceive('logout')
            ->once()
            ->with('inert-spring-token')
            ->andThrow($apiException);

        $session = $this->sessionStore();
        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );

        $originalSessionId = $session->getId();

        $service = new QueueFlowAuthService(
            $apiClient,
            $session,
        );

        try {
            $service->logout();

            $this->fail(
                'Expected QueueFlowApiException was not thrown.',
            );
        } catch (QueueFlowApiException $exception) {
            $this->assertSame($apiException, $exception);
            $this->assertFalse($service->hasAuthSession());
            $this->assertNull(
                $session->get('queueflow.auth'),
            );
            $this->assertNotSame(
                $originalSessionId,
                $session->getId(),
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 6.8: Membership context
    |--------------------------------------------------------------------------
    */

    public function test_membership_for_business_finds_matching_membership(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $session = $this->sessionStore(),
        );

        $state = $this->storedAuthenticationState();

        $state['memberships'] = [
            [
                'businessId' => 10,
                'branchId' => null,
                'role' => 'OWNER',
            ],
            [
                'businessId' => 20,
                'branchId' => 200,
                'role' => 'STAFF',
            ],
        ];

        $session->put('queueflow.auth', $state);

        $membership = $service->membershipForBusiness(20);

        $this->assertNotNull($membership);
        $this->assertSame(
            20,
            $membership->businessId,
        );
        $this->assertSame(
            200,
            $membership->branchId,
        );
        $this->assertSame(
            'STAFF',
            $membership->role,
        );
    }

    public function test_membership_for_business_returns_null_for_unknown_business(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $session = $this->sessionStore(),
        );

        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );

        $this->assertNull(
            $service->membershipForBusiness(999),
        );
    }

    public function test_membership_for_business_returns_null_without_authentication_state(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $this->sessionStore(),
        );

        $this->assertNull(
            $service->membershipForBusiness(10),
        );
    }

    public function test_membership_for_branch_finds_matching_branch_membership(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $session = $this->sessionStore(),
        );

        $state = $this->storedAuthenticationState();

        $state['memberships'] = [
            [
                'businessId' => 10,
                'branchId' => null,
                'role' => 'OWNER',
            ],
            [
                'businessId' => 10,
                'branchId' => 101,
                'role' => 'STAFF',
            ],
        ];

        $session->put('queueflow.auth', $state);

        $membership = $service->membershipForBranch(101);

        $this->assertNotNull($membership);
        $this->assertSame(
            10,
            $membership->businessId,
        );
        $this->assertSame(
            101,
            $membership->branchId,
        );
        $this->assertSame(
            'STAFF',
            $membership->role,
        );
    }

    public function test_business_wide_membership_does_not_match_specific_branch_lookup(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $session = $this->sessionStore(),
        );

        $session->put(
            'queueflow.auth',
            $this->storedAuthenticationState(),
        );

        $this->assertNull(
            $service->membershipForBranch(101),
        );
    }

    public function test_membership_for_branch_returns_null_without_authentication_state(): void
    {
        $service = new QueueFlowAuthService(
            Mockery::mock(QueueFlowApiClient::class),
            $this->sessionStore(),
        );

        $this->assertNull(
            $service->membershipForBranch(101),
        );
    }

    private function sessionStore(): Session
    {
        $session = app(Session::class);
        $session->start();

        return $session;
    }

    private function loginData(): LoginData
    {
        return new LoginData(
            token: 'inert-spring-token',
            tokenType: 'Bearer',
            expiresAt: CarbonImmutable::parse(
                '2030-04-15T10:30:00+08:00',
            ),
            user: new AuthUserData(
                id: 42,
                email: 'alex@example.com',
                firstName: 'Alex',
                lastName: 'Rivera',
                phone: null,
            ),
            memberships: [
                new StaffMembershipData(
                    businessId: 10,
                    branchId: null,
                    role: 'OWNER',
                ),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function storedAuthenticationState(): array
    {
        return [
            'token' => 'inert-spring-token',
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
                    'branchId' => null,
                    'role' => 'OWNER',
                ],
            ],
        ];
    }
}
