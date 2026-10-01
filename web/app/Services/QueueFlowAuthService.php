<?php

namespace App\Services;

use App\Data\AuthUserData;
use App\Data\LoginData;
use App\Data\RegisteredUserData;
use App\Data\StaffMembershipData;
use App\Exceptions\QueueFlowApiException;
use Illuminate\Contracts\Session\Session;

class QueueFlowAuthService
{
    private const string SESSION_KEY = 'queueflow.auth';

    public function __construct(
        private QueueFlowApiClient $apiClient,
        private Session $session,
    ) {}

    /**
     * @return array{
     *     user: AuthUserData,
     *     memberships: list<StaffMembershipData>
     * }
     */
    public function login(
        string $email,
        #[\SensitiveParameter] string $password,
    ): array {
        $login = $this->apiClient->login($email, $password);

        $this->session->regenerate();
        $this->storeAuthentication($login);

        return $this->contextFromLogin($login);
    }

    public function register(
        string $email,
        #[\SensitiveParameter] string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
    ): RegisteredUserData {
        return $this->apiClient->register(
            $email,
            $password,
            $firstName,
            $lastName,
            $phone,
        );
    }

    public function hasAuthSession(): bool
    {
        return $this->token() !== null;
    }

    /**
     * @return array{
     *     user: AuthUserData,
     *     memberships: list<StaffMembershipData>
     * }|null
     */
    public function cachedContext(): ?array
    {
        $state = $this->authenticationState();

        if ($state === null
            || ! is_array($state['user'] ?? null)
            || ! is_array($state['memberships'] ?? null)) {
            return null;
        }

        return [
            'user' => AuthUserData::fromArray($state['user']),
            'memberships' => array_map(
                static fn (array $membership): StaffMembershipData => StaffMembershipData::fromArray($membership),
                $state['memberships'],
            ),
        ];
    }

    public function membershipForBusiness(int $businessId): ?StaffMembershipData
    {
        $context = $this->cachedContext();

        if ($context === null) {
            return null;
        }

        foreach ($context['memberships'] as $membership) {
            if ($membership->belongsToBusiness($businessId)) {
                return $membership;
            }
        }

        return null;
    }

    public function membershipForBranch(int $branchId): ?StaffMembershipData
    {
        $context = $this->cachedContext();

        if ($context === null) {
            return null;
        }

        foreach ($context['memberships'] as $membership) {
            if ($membership->belongsToBranch($branchId)) {
                return $membership;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     user: AuthUserData,
     *     memberships: list<StaffMembershipData>
     * }|null
     */
    public function currentUser(): ?array
    {
        $token = $this->token();

        if ($token === null) {
            return null;
        }

        try {
            $context = $this->apiClient->currentUser($token);
        } catch (QueueFlowApiException $exception) {
            if ($exception->status === 401) {
                $this->clearAuthenticationState();
            }

            throw $exception;
        }

        $this->storeContext(
            $token,
            $context['user'],
            $context['memberships'],
        );

        return $context;
    }

    public function logout(): void
    {
        $token = $this->token();

        try {
            if ($token !== null) {
                $this->apiClient->logout($token);
            }
        } finally {
            $this->session->invalidate();
            $this->session->regenerateToken();
        }
    }

    private function storeAuthentication(LoginData $login): void
    {
        $this->storeContext(
            $login->token,
            $login->user,
            $login->memberships,
        );
    }

    /**
     * @param  list<StaffMembershipData>  $memberships
     */
    private function storeContext(
        #[\SensitiveParameter] string $token,
        AuthUserData $user,
        array $memberships,
    ): void {
        $this->session->put(self::SESSION_KEY, [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'phone' => $user->phone,
            ],
            'memberships' => array_map(
                static fn (StaffMembershipData $membership): array => [
                    'businessId' => $membership->businessId,
                    'branchId' => $membership->branchId,
                    'role' => $membership->role,
                ],
                $memberships,
            ),
        ]);
    }

    /**
     * @return array{
     *     user: AuthUserData,
     *     memberships: list<StaffMembershipData>
     * }
     */
    private function contextFromLogin(LoginData $login): array
    {
        return [
            'user' => $login->user,
            'memberships' => $login->memberships,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authenticationState(): ?array
    {
        $state = $this->session->get(self::SESSION_KEY);

        return is_array($state) ? $state : null;
    }

    private function token(): ?string
    {
        $token = $this->authenticationState()['token'] ?? null;

        return is_string($token) && $token !== ''
            ? $token
            : null;
    }

    private function clearAuthenticationState(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
