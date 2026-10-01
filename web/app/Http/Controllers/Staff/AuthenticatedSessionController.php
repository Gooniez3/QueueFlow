<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\QueueFlowApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\LoginRequest;
use App\Services\QueueFlowAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly QueueFlowAuthService $authService,
    ) {}

    public function create(): View
    {
        return view('staff.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        try {
            $this->authService->login(
                $credentials['email'],
                $credentials['password'],
            );
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 401) {
                return back()
                    ->withErrors([
                        'email' => 'The provided email or password is incorrect.',
                    ])
                    ->withInput(['email' => $credentials['email']]);
            }

            return back()
                ->withErrors([
                    'authentication' => 'QueueFlow authentication is temporarily unavailable. Please try again.',
                ])
                ->withInput(['email' => $credentials['email']]);
        }

        return redirect()
            ->route('staff.home')
            ->with('status', 'Signed in successfully.');
    }
}
