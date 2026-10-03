<?php

namespace App\Services;

use App\Data\AuthUserData;
use App\Data\BranchData;
use App\Data\BusinessData;
use App\Data\LoginData;
use App\Data\QueueData;
use App\Data\QueueEntryData;
use App\Data\QueuePositionData;
use App\Data\QueueStaffEntryData;
use App\Data\RegisteredUserData;
use App\Data\ServiceData;
use App\Data\StaffMembershipData;
use App\Data\TodayQueueData;
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
        #[\SensitiveParameter] string $token,
        string $name,
        ?string $description = null,
    ): BusinessData {
        try {
            $response = $this->client()
                ->withToken($token)
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

    /**
     * @return list<BranchData>
     */
    public function branches(int $businessId): array
    {
        try {
            $response = $this->client()
                ->get("/api/v1/businesses/{$businessId}/branches");
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return collect($response->json())
            ->map(fn (array $branch) => BranchData::fromArray($branch))
            ->all();
    }

    public function branch(int $businessId, int $branchId): BranchData
    {
        try {
            $response = $this->client()
                ->get("/api/v1/businesses/{$businessId}/branches/{$branchId}");
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return BranchData::fromArray($response->json());
    }

    public function createBranch(
        int $businessId,
        #[\SensitiveParameter] string $token,
        string $name,
        string $address,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $timezone = null,
    ): BranchData {
        $payload = [
            'name' => $name,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($timezone !== null) {
            $payload['timezone'] = $timezone;
        }

        try {
            $response = $this->client()
                ->withToken($token)
                ->post("/api/v1/businesses/{$businessId}/branches", $payload);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return BranchData::fromArray($response->json());
    }

    /**
     * @return list<ServiceData>
     */
    public function services(int $businessId, int $branchId): array
    {
        try {
            $response = $this->client()
                ->get("/api/v1/businesses/{$businessId}/branches/{$branchId}/services");
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return collect($response->json())
            ->map(fn (array $service) => ServiceData::fromArray($service))
            ->all();
    }

    public function service(
        int $businessId,
        int $branchId,
        int $serviceId,
    ): ServiceData {
        try {
            $response = $this->client()
                ->get("/api/v1/businesses/{$businessId}/branches/{$branchId}/services/{$serviceId}");
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return ServiceData::fromArray($response->json());
    }

    public function createQueue(
        int $businessId,
        int $branchId,
        #[\SensitiveParameter] string $token,
        ?int $serviceId,
        string $name,
        string $ticketPrefix,
    ): QueueData {
        $response = $this->sendPost(
            $this->client()->withToken($token),
            "/api/v1/businesses/{$businessId}/branches/{$branchId}/queues",
            [
                'serviceId' => $serviceId,
                'name' => $name,
                'ticketPrefix' => $ticketPrefix,
            ],
        );

        return QueueData::fromArray($response->json());
    }

    public function todayQueue(
        int $businessId,
        int $branchId,
        ?int $serviceId = null,
    ): TodayQueueData {
        $response = $this->sendGet(
            $this->client(),
            "/api/v1/businesses/{$businessId}/branches/{$branchId}/queues/today",
            $serviceId === null ? [] : ['serviceId' => $serviceId],
        );

        return TodayQueueData::fromArray($response->json());
    }

    public function joinQueue(
        int $queueId,
        ?int $serviceId = null,
        #[\SensitiveParameter] ?string $token = null,
        #[\SensitiveParameter] ?string $idempotencyKey = null,
    ): QueueEntryData {
        $request = $this->withQueueCredentials($token);

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $request = $request->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ]);
        }

        $response = $this->sendPost(
            $request,
            "/api/v1/queues/{$queueId}/entries",
            ['serviceId' => $serviceId],
        );

        return QueueEntryData::fromArray($response->json());
    }

    public function queuePosition(
        int $queueId,
        int $entryId,
        #[\SensitiveParameter] ?string $token = null,
        #[\SensitiveParameter] ?string $guestToken = null,
    ): QueuePositionData {
        $response = $this->sendGet(
            $this->withQueueCredentials($token, $guestToken),
            "/api/v1/queues/{$queueId}/entries/{$entryId}/position",
        );

        return QueuePositionData::fromArray($response->json());
    }

    public function cancelQueueEntry(
        int $queueId,
        int $entryId,
        #[\SensitiveParameter] ?string $token = null,
        #[\SensitiveParameter] ?string $guestToken = null,
    ): QueueEntryData {
        $response = $this->sendPost(
            $this->withQueueCredentials($token, $guestToken),
            "/api/v1/queues/{$queueId}/entries/{$entryId}/cancel",
        );

        return QueueEntryData::fromArray($response->json());
    }

    public function callNextQueueEntry(
        int $queueId,
        #[\SensitiveParameter] string $token,
    ): QueueStaffEntryData {
        return $this->staffQueueEntryTransition(
            $queueId,
            $token,
            'call-next',
        );
    }

    public function startServingQueueEntry(
        int $queueId,
        int $entryId,
        #[\SensitiveParameter] string $token,
    ): QueueStaffEntryData {
        return $this->staffQueueEntryTransition(
            $queueId,
            $token,
            "entries/{$entryId}/start",
        );
    }

    public function completeQueueEntry(
        int $queueId,
        int $entryId,
        #[\SensitiveParameter] string $token,
    ): QueueStaffEntryData {
        return $this->staffQueueEntryTransition(
            $queueId,
            $token,
            "entries/{$entryId}/complete",
        );
    }

    public function skipQueueEntry(
        int $queueId,
        int $entryId,
        #[\SensitiveParameter] string $token,
    ): QueueStaffEntryData {
        return $this->staffQueueEntryTransition(
            $queueId,
            $token,
            "entries/{$entryId}/skip",
        );
    }

    public function pauseQueue(
        int $queueId,
        #[\SensitiveParameter] string $token,
    ): QueueData {
        return $this->staffQueueTransition($queueId, $token, 'pause');
    }

    public function resumeQueue(
        int $queueId,
        #[\SensitiveParameter] string $token,
    ): QueueData {
        return $this->staffQueueTransition($queueId, $token, 'resume');
    }

    public function closeQueue(
        int $queueId,
        #[\SensitiveParameter] string $token,
    ): QueueData {
        return $this->staffQueueTransition($queueId, $token, 'close');
    }

    public function createService(
        int $businessId,
        int $branchId,
        #[\SensitiveParameter] string $token,
        string $name,
        ?string $description,
        int $durationMinutes,
    ): ServiceData {
        try {
            $response = $this->client()
                ->withToken($token)
                ->post("/api/v1/businesses/{$businessId}/branches/{$branchId}/services", [
                    'name' => $name,
                    'description' => $description,
                    'durationMinutes' => $durationMinutes,
                ]);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return ServiceData::fromArray($response->json());
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

    private function staffQueueEntryTransition(
        int $queueId,
        #[\SensitiveParameter] string $token,
        string $path,
    ): QueueStaffEntryData {
        $response = $this->sendPost(
            $this->client()->withToken($token),
            "/api/v1/queues/{$queueId}/staff/{$path}",
        );

        return QueueStaffEntryData::fromArray($response->json());
    }

    private function staffQueueTransition(
        int $queueId,
        #[\SensitiveParameter] string $token,
        string $action,
    ): QueueData {
        $response = $this->sendPost(
            $this->client()->withToken($token),
            "/api/v1/queues/{$queueId}/staff/{$action}",
        );

        return QueueData::fromArray($response->json());
    }

    private function withQueueCredentials(
        #[\SensitiveParameter] ?string $token = null,
        #[\SensitiveParameter] ?string $guestToken = null,
    ): PendingRequest {
        $request = $this->client();

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        if ($guestToken !== null && $guestToken !== '') {
            $request = $request->withHeaders([
                'X-Guest-Token' => $guestToken,
            ]);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendPost(
        PendingRequest $request,
        string $path,
        array $payload = [],
    ): Response {
        try {
            $response = $request->post($path, $payload);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return $response;
    }

    private function sendGet(
        PendingRequest $request,
        string $path,
        array $query = [],
    ): Response {
        try {
            $response = $request->get($path, $query);
        } catch (ConnectionException $exception) {
            throw $this->connectionException($exception);
        }

        $this->ensureSuccessful($response);

        return $response;
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
