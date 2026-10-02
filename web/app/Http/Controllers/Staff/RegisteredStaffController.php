<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\RegisterRequest;
use App\Services\QueueFlowAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class RegisteredStaffController extends Controller
{
    public function __construct(
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function create(): View
    {
        return view('staff.auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $registeredUser = $this->authService->register(
                $data['email'],
                $data['password'],
                $data['firstName'],
                $data['lastName'],
                $data['phone'] ?? null,
            );
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 409) {
                return back()
                    ->withErrors([
                        'email' => 'An account with this email address already exists.',
                    ])
                    ->withInput(Arr::except($data, 'password'));
            }

            if ($exception->status === 400) {
                return back()
                    ->withErrors($this->registrationErrors($exception))
                    ->withInput(Arr::except($data, 'password'));
            }

            return back()
                ->withErrors([
                    'registration' => 'Registration is temporarily unavailable. Please try again.',
                ])
                ->withInput(Arr::except($data, 'password'));
        }

        return redirect()
            ->route('staff.login')
            ->with('status', 'Your staff account has been created. Sign in to continue.')
            ->withInput(['email' => $registeredUser->email]);
    }

    /**
     * @return array<string, string>
     */
    private function registrationErrors(QueueFlowApiException $exception): array
    {
        $errors = [];

        foreach (['email', 'password', 'firstName', 'lastName', 'phone'] as $field) {
            $message = $exception->validationErrors[$field] ?? null;

            if (is_string($message) && $message !== '') {
                $errors[$field] = $message;
            }
        }

        if ($errors === []) {
            $errors['registration'] = 'We could not create your account. Please review your details and try again.';
        }

        return $errors;
    }
}
