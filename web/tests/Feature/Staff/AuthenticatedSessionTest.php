<?php

namespace Tests\Feature\Staff;

use App\Data\AuthUserData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowAuthService;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    public function test_login_page_renders_accessible_form_fields_and_csrf_token(): void
    {
        $response = $this->get(route('staff.login'));

        $response
            ->assertOk()
            ->assertViewIs('staff.auth.login')
            ->assertSee('Sign in')
            ->assertSee('Email address')
            ->assertSee('Password')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('type="password"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('href="'.route('staff.register').'"', false)
            ->assertDontSee('inert-spring-token');
    }

    public function test_successful_login_uses_auth_service_and_redirects_staff_home(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('login')
            ->once()
            ->with('alex@example.com', 'inert-password')
            ->andReturn([
                'user' => new AuthUserData(
                    id: 42,
                    email: 'alex@example.com',
                    firstName: 'Alex',
                    lastName: 'Rivera',
                    phone: null,
                ),
                'memberships' => [],
            ]);
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->post(route('staff.login.store'), [
            'email' => 'alex@example.com',
            'password' => 'inert-password',
        ]);

        $response
            ->assertRedirectToRoute('staff.home')
            ->assertSessionHas('status', 'Signed in successfully.')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_invalid_credentials_return_safe_error_and_never_flash_password(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('login')
            ->once()
            ->with('alex@example.com', 'incorrect-password')
            ->andThrow(new QueueFlowApiException('Backend credential detail', 401));
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
            'email' => 'alex@example.com',
            'password' => 'incorrect-password',
        ]);

        $response
            ->assertRedirect(route('staff.login'))
            ->assertSessionHasErrors([
                'email' => 'The provided email or password is incorrect.',
            ])
            ->assertSessionHasInput('email', 'alex@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('queueflow.auth');
    }

    /**
     * @param  array{status: int|null, message: string}  $failure
     */
    #[DataProvider('serviceFailures')]
    public function test_service_failures_return_safe_login_error(array $failure): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('login')
            ->once()
            ->andThrow(new QueueFlowApiException(
                $failure['message'],
                $failure['status'],
            ));
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
            'email' => 'alex@example.com',
            'password' => 'inert-password',
        ]);

        $response
            ->assertRedirect(route('staff.login'))
            ->assertSessionHasErrors([
                'authentication' => 'QueueFlow authentication is temporarily unavailable. Please try again.',
            ])
            ->assertSessionHasInput('email', 'alex@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('queueflow.auth');
    }

    public function test_login_validation_rejects_invalid_input_without_calling_service(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldNotReceive('login');
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        $response
            ->assertRedirect(route('staff.login'))
            ->assertSessionHasErrors(['email', 'password'])
            ->assertSessionHasInput('email', 'not-an-email')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_successful_logout_uses_auth_service_and_redirects_to_login(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('logout')
            ->once();

        $this->app->instance(
            QueueFlowAuthService::class,
            $authService,
        );

        $response = $this->post(route('staff.logout'));

        $response
            ->assertRedirectToRoute('staff.login')
            ->assertSessionHas(
                'status',
                'Signed out successfully.',
            );
    }

    public function test_spring_logout_failure_still_redirects_to_login_with_safe_message(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('logout')
            ->once()
            ->andThrow(
                new QueueFlowApiException(
                    'Internal Spring connection details',
                    503,
                ),
            );

        $this->app->instance(
            QueueFlowAuthService::class,
            $authService,
        );

        $response = $this->post(route('staff.logout'));

        $response
            ->assertRedirectToRoute('staff.login')
            ->assertSessionHas(
                'status',
                'Signed out locally. QueueFlow could not confirm the server session was revoked.',
            )
            ->assertDontSee('Internal Spring connection details');
    }

    /**
     * @return array<string, array{array{status: int|null, message: string}}>
     */
    public static function serviceFailures(): array
    {
        return [
            'connection failure' => [[
                'status' => null,
                'message' => 'Connection refused with internal host details',
            ]],
            'server failure' => [[
                'status' => 500,
                'message' => 'Internal Spring stack detail',
            ]],
        ];
    }
}
