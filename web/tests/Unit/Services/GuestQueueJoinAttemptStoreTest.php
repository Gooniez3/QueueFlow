<?php

namespace Tests\Unit\Services;

use App\Services\GuestQueueJoinAttemptStore;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestQueueJoinAttemptStoreTest extends TestCase
{
    public function test_first_attempt_creates_uuid_and_same_context_reuses_it(): void
    {
        [$store, $session] = $this->attemptStore();

        $firstKey = $store->idempotencyKey(10, 21, 31, 91);
        $replayedKey = $store->idempotencyKey(10, 21, 31, 91);

        $this->assertTrue(Str::isUuid($firstKey));
        $this->assertSame($firstKey, $replayedKey);
        $this->assertSame(
            $firstKey,
            $session->get('queueflow.customer.join_attempts.10:21:31:91.idempotencyKey'),
        );
    }

    public function test_forget_removes_only_exact_attempt_and_preserves_other_session_state(): void
    {
        [$store, $session] = $this->attemptStore();
        $firstKey = $store->idempotencyKey(10, 21, 31, 91);
        $secondKey = $store->idempotencyKey(10, 21, 32, 92);
        $session->put('queueflow.auth', ['token' => 'inert-staff-token']);
        $session->put('queueflow.customer.entries', ['91:301' => ['guestToken' => 'inert-guest-token']]);
        $session->put('unrelated', 'preserved');

        $store->forget(10, 21, 31, 91);

        $this->assertNull($session->get('queueflow.customer.join_attempts.10:21:31:91'));
        $this->assertSame(
            $secondKey,
            $session->get('queueflow.customer.join_attempts.10:21:32:92.idempotencyKey'),
        );
        $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        $this->assertSame(
            'inert-guest-token',
            $session->get('queueflow.customer.entries.91:301.guestToken'),
        );
        $this->assertSame('preserved', $session->get('unrelated'));
        $this->assertNotSame($firstKey, $secondKey);
    }

    /** @return array{GuestQueueJoinAttemptStore, Session} */
    private function attemptStore(): array
    {
        $session = app(Session::class);
        $session->start();

        return [new GuestQueueJoinAttemptStore($session), $session];
    }
}
