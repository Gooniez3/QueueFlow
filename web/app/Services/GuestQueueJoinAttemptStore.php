<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

class GuestQueueJoinAttemptStore
{
    private const string SESSION_KEY = 'queueflow.customer.join_attempts';

    public function __construct(
        private Session $session,
    ) {}

    public function idempotencyKey(
        int $businessId,
        int $branchId,
        int $serviceId,
        int $queueId,
    ): string {
        $attempts = $this->storedAttempts();
        $attemptKey = $this->attemptKey($businessId, $branchId, $serviceId, $queueId);
        $attempt = $attempts[$attemptKey] ?? null;

        if ($this->isValidAttempt($attempt, $businessId, $branchId, $serviceId, $queueId)) {
            return $attempt['idempotencyKey'];
        }

        $idempotencyKey = (string) Str::uuid();
        $attempts[$attemptKey] = [
            'businessId' => $businessId,
            'branchId' => $branchId,
            'serviceId' => $serviceId,
            'queueId' => $queueId,
            'idempotencyKey' => $idempotencyKey,
        ];

        $this->session->put(self::SESSION_KEY, $attempts);

        return $idempotencyKey;
    }

    public function forget(
        int $businessId,
        int $branchId,
        int $serviceId,
        int $queueId,
    ): void {
        $attempts = $this->storedAttempts();
        unset($attempts[$this->attemptKey($businessId, $branchId, $serviceId, $queueId)]);

        if ($attempts === []) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $this->session->put(self::SESSION_KEY, $attempts);
    }

    /** @return array<string, mixed> */
    private function storedAttempts(): array
    {
        $attempts = $this->session->get(self::SESSION_KEY, []);

        return is_array($attempts) ? $attempts : [];
    }

    private function attemptKey(
        int $businessId,
        int $branchId,
        int $serviceId,
        int $queueId,
    ): string {
        return "{$businessId}:{$branchId}:{$serviceId}:{$queueId}";
    }

    private function isValidAttempt(
        mixed $attempt,
        int $businessId,
        int $branchId,
        int $serviceId,
        int $queueId,
    ): bool {
        return is_array($attempt)
            && ($attempt['businessId'] ?? null) === $businessId
            && ($attempt['branchId'] ?? null) === $branchId
            && ($attempt['serviceId'] ?? null) === $serviceId
            && ($attempt['queueId'] ?? null) === $queueId
            && is_string($attempt['idempotencyKey'] ?? null)
            && Str::isUuid($attempt['idempotencyKey']);
    }
}
