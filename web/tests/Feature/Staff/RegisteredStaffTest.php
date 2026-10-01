<?php

namespace Tests\Feature\Staff;

use App\Data\RegisteredUserData;
use App\Exceptions\QueueFlowApiException;
use App\Services\QueueFlowAuthService;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisteredStaffTest extends TestCase
{
    public function test_registration_page_renders_expected_fields_and_csrf_token(): void
    {
        $response = $this->get(route('staff.register'));

        $response
            ->assertOk()
            ->assertViewIs('staff.auth.register')
            ->assertSee('Create account')
            ->assertSee('name="firstName"', false)
            ->assertSee('name="lastName"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="password"', false)
            ->assertSee('type="password"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('href="'.route('staff.login').'"', false);
    }

    public function test_successful_registration_does_not_authenticate_and_redirects_to_login(): void
    {
        $registeredUser = $this->registeredUser();
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->with(
                'alex@example.com',
                'inert-password',
                'Alex',
                'Rivera',
                '+65 6123 4567',
            )
            ->andReturn($registeredUser);
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->post(route('staff.register.store'), $this->validRegistration());

        $response
            ->assertRedirectToRoute('staff.login')
            ->assertSessionHas('status', 'Your staff account has been created. Sign in to continue.')
            ->assertSessionHasInput('email', 'alex@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('queueflow.auth');

        $loginResponse = $this->get(route('staff.login'));

        $loginResponse
            ->assertOk()
            ->assertSee('Your staff account has been created. Sign in to continue.')
            ->assertSee('value="alex@example.com"', false);
    }

    public function test_registration_accepts_nullable_phone(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->with(
                'alex@example.com',
                'inert-password',
                'Alex',
                'Rivera',
                null,
            )
            ->andReturn($this->registeredUser());
        $this->app->instance(QueueFlowAuthService::class, $authService);
        $registration = $this->validRegistration();
        $registration['phone'] = null;

        $response = $this->post(route('staff.register.store'), $registration);

        $response->assertRedirectToRoute('staff.login');
    }

    public function test_registration_requires_supported_fields(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldNotReceive('register');
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.register'))->post(route('staff.register.store'));

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors(['email', 'password', 'firstName', 'lastName'])
            ->assertSessionDoesntHaveErrors(['phone'])
            ->assertSessionMissing('_old_input.password');
    }

    public function test_registration_rejects_invalid_field_constraints(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldNotReceive('register');
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.register'))->post(route('staff.register.store'), [
            'email' => 'not-an-email',
            'password' => 'short',
            'firstName' => Str::repeat('A', 101),
            'lastName' => Str::repeat('B', 101),
            'phone' => Str::repeat('1', 51),
        ]);

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors(['email', 'password', 'firstName', 'lastName', 'phone'])
            ->assertSessionMissing('_old_input.password');
    }

    public function test_registration_rejects_password_longer_than_spring_limit(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldNotReceive('register');
        $this->app->instance(QueueFlowAuthService::class, $authService);
        $registration = $this->validRegistration();
        $registration['password'] = Str::repeat('p', 73);

        $response = $this->from(route('staff.register'))->post(
            route('staff.register.store'),
            $registration,
        );

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors(['password'])
            ->assertSessionMissing('_old_input.password');
    }

    public function test_duplicate_email_409_returns_useful_error_without_password(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->andThrow(new QueueFlowApiException('Backend duplicate detail', 409));
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.register'))->post(
            route('staff.register.store'),
            $this->validRegistration(),
        );

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors([
                'email' => 'An account with this email address already exists.',
            ])
            ->assertSessionHasInput('email', 'alex@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('queueflow.auth');
    }

    public function test_backend_validation_errors_are_mapped_to_supported_fields(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->andThrow(new QueueFlowApiException(
                'Request validation failed',
                400,
                [
                    'firstName' => 'First name is required',
                    'internalField' => 'Internal detail must not be shown',
                ],
            ));
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.register'))->post(
            route('staff.register.store'),
            $this->validRegistration(),
        );

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors([
                'firstName' => 'First name is required',
            ])
            ->assertSessionDoesntHaveErrors(['internalField'])
            ->assertSessionMissing('_old_input.password');
    }

    public function test_unsupported_backend_validation_details_are_not_exposed(): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->andThrow(new QueueFlowApiException(
                'Internal validation detail',
                400,
                ['internalField' => 'Sensitive backend field detail'],
            ));
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.register'))->post(
            route('staff.register.store'),
            $this->validRegistration(),
        );

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors([
                'registration' => 'We could not create your account. Please review your details and try again.',
            ])
            ->assertSessionDoesntHaveErrors(['internalField'])
            ->assertSessionMissing('_old_input.password');
    }

    /**
     * @param  array{status: int|null, message: string}  $failure
     */
    #[DataProvider('serviceFailures')]
    public function test_service_failures_return_safe_registration_error(array $failure): void
    {
        $authService = Mockery::mock(QueueFlowAuthService::class);
        $authService->shouldReceive('register')
            ->once()
            ->andThrow(new QueueFlowApiException(
                $failure['message'],
                $failure['status'],
            ));
        $this->app->instance(QueueFlowAuthService::class, $authService);

        $response = $this->from(route('staff.register'))->post(
            route('staff.register.store'),
            $this->validRegistration(),
        );

        $response
            ->assertRedirect(route('staff.register'))
            ->assertSessionHasErrors([
                'registration' => 'Registration is temporarily unavailable. Please try again.',
            ])
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('queueflow.auth');
    }

    /**
     * @return array<string, mixed>
     */
    private function validRegistration(): array
    {
        return [
            'email' => 'alex@example.com',
            'password' => 'inert-password',
            'firstName' => 'Alex',
            'lastName' => 'Rivera',
            'phone' => '+65 6123 4567',
        ];
    }

    private function registeredUser(): RegisteredUserData
    {
        return new RegisteredUserData(
            id: 42,
            email: 'alex@example.com',
            firstName: 'Alex',
            lastName: 'Rivera',
            phone: null,
        );
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
