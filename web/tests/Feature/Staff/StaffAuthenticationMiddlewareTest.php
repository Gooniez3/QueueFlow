<?php

namespace Tests\Feature\Staff;

use App\Data\AuthUserData;
use App\Data\StaffMembershipData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowAuthService;
use Mockery\MockInterface;
use Tests\TestCase;

class StaffAuthenticationMiddlewareTest extends TestCase
{
    public function test_staff_home_redirects_to_login_without_local_authentication(): void
    {
        $this->mock(QueueFlowAuthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasAuthSession')
                ->once()
                ->andReturnFalse();

            $mock->shouldNotReceive('currentUser');
        });

        $response = $this->get('/staff');

        $response->assertRedirect(route('staff.login'));
        $response->assertSessionHas('error', 'Please sign in to continue.');
    }

    public function test_staff_home_allows_authenticated_staff_after_spring_verification(): void
    {
        $context = [
            'user' => new AuthUserData(
                id: 1,
                email: 'staff@example.com',
                firstName: 'Queue',
                lastName: 'Staff',
                phone: null,
            ),
            'memberships' => [
                new StaffMembershipData(
                    businessId: 10,
                    branchId: null,
                    role: 'STAFF',
                ),
            ],
        ];

        $this->mock(QueueFlowAuthService::class, function (MockInterface $mock) use ($context): void {
            $mock->shouldReceive('hasAuthSession')
                ->once()
                ->andReturnTrue();

            $mock->shouldReceive('currentUser')
                ->once()
                ->andReturn($context);
        });

        $response = $this->get('/staff');

        $response
            ->assertOk()
            ->assertSee('Signed in successfully.')
            ->assertSee('Sign out')
            ->assertSee(
                'action="'.route('staff.logout').'"',
                false,
            )
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_staff_home_receives_verified_spring_auth_context(): void
    {
        $context = [
            'user' => new AuthUserData(
                id: 42,
                email: 'manager@example.com',
                firstName: 'Alex',
                lastName: 'Manager',
                phone: null,
            ),
            'memberships' => [
                new StaffMembershipData(
                    businessId: 10,
                    branchId: null,
                    role: 'OWNER',
                ),
                new StaffMembershipData(
                    businessId: 20,
                    branchId: 201,
                    role: 'MANAGER',
                ),
            ],
        ];

        $this->mock(
            QueueFlowAuthService::class,
            function (MockInterface $mock) use ($context): void {
                $mock->shouldReceive('hasAuthSession')
                    ->once()
                    ->andReturnTrue();

                $mock->shouldReceive('currentUser')
                    ->once()
                    ->andReturn($context);
            },
        );

        $response = $this->get('/staff');

        $response->assertOk();
        $response->assertViewHas(
            'authContext',
            function (array $authContext) use ($context): bool {
                return $authContext === $context;
            },
        );
    }

    public function test_expired_spring_session_redirects_staff_to_login(): void
    {
        $this->mock(QueueFlowAuthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasAuthSession')
                ->once()
                ->andReturnTrue();

            $mock->shouldReceive('currentUser')
                ->once()
                ->andThrow(new QueueFlowApiException(
                    message: 'Unauthorized',
                    status: 401,
                ));
        });

        $response = $this->get('/staff');

        $response->assertRedirect(route('staff.login'));
        $response->assertSessionHas(
            'error',
            'Your session has expired. Please sign in again.',
        );
    }

    public function test_non_authentication_api_failure_does_not_become_expired_session_redirect(): void
    {
        $this->mock(QueueFlowAuthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasAuthSession')
                ->once()
                ->andReturnTrue();

            $mock->shouldReceive('currentUser')
                ->once()
                ->andThrow(new QueueFlowApiException(
                    message: 'QueueFlow API unavailable',
                    status: 503,
                ));
        });

        $response = $this->get('/staff');

        $response->assertStatus(500);
        $response->assertSessionMissing('error');
    }

    public function test_staff_login_and_registration_pages_remain_public(): void
    {
        $this->mock(QueueFlowAuthService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('hasAuthSession');
            $mock->shouldNotReceive('currentUser');
        });

        $this->get('/staff/login')->assertOk();
        $this->get('/staff/register')->assertOk();
    }

    public function test_customer_routes_remain_public(): void
    {
        $this->mock(QueueFlowAuthService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('hasAuthSession');
            $mock->shouldNotReceive('currentUser');
        });

        $this->get('/')->assertOk();
        $this->get('/ticket')->assertOk();
        $this->get('/queue-board')->assertOk();
    }
}
