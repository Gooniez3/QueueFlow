<?php

namespace App\Services;

use App\Data\AuthUserData;
use App\Data\BusinessData;
use App\Data\LoginData;
use App\Data\RegisteredUserData;
use App\Data\StaffMembershipData;
use App\Exceptions\QueueFlowApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class QueueFlowApiClient
{
    public function client(): PendingRequest
    {
        return Http::baseUrl(
            config('services.queueflow.base_url')
        )
            ->acceptJson()
            ->asJson()
            ->timeout(5);
    }

    /**
     * @return list<BusinessData>
     */
    public function businesses(): array
    {
        try {
            $response = $this->client()
                ->get('/api/v1/businesses');
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return collect($response->json())
            ->map(fn (array $business) => BusinessData::fromArray($business))
            ->all();
    }

    public function business(int $id): BusinessData
    {
        try {
            $response = $this->client()
                ->get("/api/v1/businesses/{$id}");
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return BusinessData::fromArray($response->json());
    }

    public function createBusiness(
        string $name,
        ?string $description = null
    ): BusinessData {
        try {
            $response = $this->client()
                ->post('/api/v1/businesses', [
                    'name' => $name,
                    'description' => $description,
                ]);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return BusinessData::fromArray($response->json());
    }

    public function register(
        string $email,
        #[\SensitiveParameter] string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
    ): RegisteredUserData {
        try {
            $response = $this->client()
                ->post('/api/v1/auth/register', [
                    'email' => $email,
                    'password' => $password,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'phone' => $phone,
                ]);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return RegisteredUserData::fromArray($response->json());
    }

    public function login(
        string $email,
        #[\SensitiveParameter] string $password,
    ): LoginData {
        try {
            $response = $this->client()
                ->post('/api/v1/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return LoginData::fromArray($response->json());
    }

    /**
     * @return array{
     *     user: AuthUserData,
     *     memberships: list<StaffMembershipData>
     * }
     */
    public function currentUser(
        #[\SensitiveParameter] string $token,
    ): array {
        try {
            $response = $this->client()
                ->withToken($token)
                ->get('/api/v1/auth/me');
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        $data = $response->json();

        return [
            'user' => AuthUserData::fromArray($data['user']),
            'memberships' => array_map(
                static fn (array $membership): StaffMembershipData => StaffMembershipData::fromArray($membership),
                $data['memberships'],
            ),
        ];
    }

    public function logout(
        #[\SensitiveParameter] string $token,
    ): void {
        try {
            $response = $this->client()
                ->withToken($token)
                ->post('/api/v1/auth/logout');
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->json();

        $message = is_array($body)
            ? ($body['message'] ?? 'QueueFlow API request failed.')
            : 'QueueFlow API request failed.';

        $validationErrors = is_array($body)
            ? ($body['validationErrors'] ?? [])
            : [];

        throw new QueueFlowApiException(
            message: $message,
            status: $response->status(),
            validationErrors: is_array($validationErrors)
                ? $validationErrors
                : [],
        );
    }

    private function connectionException(
        ConnectionException $exception
    ): QueueFlowApiException {
        return new QueueFlowApiException(
            message: 'Unable to connect to the QueueFlow API.',
            previous: $exception,
        );
    }
}
