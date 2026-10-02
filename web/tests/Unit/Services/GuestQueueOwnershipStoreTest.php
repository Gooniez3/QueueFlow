<?php

namespace Tests\Unit\Services;

use App\Data\GuestQueueOwnershipData;
use App\Services\GuestQueueOwnershipStore;
use Illuminate\Contracts\Session\Session;
use Tests\TestCase;

class GuestQueueOwnershipStoreTest extends TestCase
{
    public function test_stores_and_retrieves_exact_guest_ownership(): void
    {
        [$store, $session] = $this->ownershipStore();

        $store->store($this->ownership());

        $ownership = $store->find(91, 301);
        $this->assertNotNull($ownership);
        $this->assertSame(10, $ownership->businessId);
        $this->assertSame(21, $ownership->branchId);
        $this->assertSame(31, $ownership->serviceId);
        $this->assertSame(91, $ownership->queueId);
        $this->assertSame(301, $ownership->entryId);
        $this->assertSame('A023', $ownership->ticketNumber);
        $this->assertSame('inert-guest-token-301', $ownership->guestToken());
        $this->assertTrue($store->has(91, 301));
        $this->assertSame(
            'inert-guest-token-301',
            $session->get('queueflow.customer.entries.91:301.guestToken'),
        );
    }

    public function test_empty_store_and_missing_ownership_are_safe(): void
    {
        [$store] = $this->ownershipStore();

        $this->assertNull($store->find(91, 301));
        $this->assertFalse($store->has(91, 301));
        $this->assertSame([], $store->all());

        $store->remove(91, 301);

        $this->assertSame([], $store->all());
    }

    public function test_supports_multiple_entries_and_repeated_ticket_numbers_without_collisions(): void
    {
        [$store] = $this->ownershipStore();
        $store->store($this->ownership());
        $store->store($this->ownership(
            queueId: 92,
            entryId: 401,
            ticketNumber: 'A023',
            guestToken: 'inert-guest-token-401',
        ));

        $ownerships = $store->all();

        $this->assertCount(2, $ownerships);
        $this->assertSame(301, $store->find(91, 301)?->entryId);
        $this->assertSame(401, $store->find(92, 401)?->entryId);
        $this->assertSame('A023', $ownerships[0]->ticketNumber);
        $this->assertSame('A023', $ownerships[1]->ticketNumber);
    }

    public function test_removes_only_exact_ownership_and_preserves_other_session_state(): void
    {
        [$store, $session] = $this->ownershipStore();
        $store->store($this->ownership());
        $store->store($this->ownership(
            queueId: 92,
            entryId: 401,
            guestToken: 'inert-guest-token-401',
        ));
        $session->put('queueflow.auth', [
            'token' => 'inert-staff-token',
            'user' => [],
            'memberships' => [],
        ]);
        $session->put('unrelated', 'preserved');

        $store->remove(91, 301);

        $this->assertFalse($store->has(91, 301));
        $this->assertTrue($store->has(92, 401));
        $this->assertSame('inert-staff-token', $session->get('queueflow.auth.token'));
        $this->assertSame('preserved', $session->get('unrelated'));
    }

    public function test_storing_customer_ownership_preserves_staff_authentication(): void
    {
        [$store, $session] = $this->ownershipStore();
        $staffAuthentication = [
            'token' => 'inert-staff-token',
            'user' => ['id' => 42],
            'memberships' => [],
        ];
        $session->put('queueflow.auth', $staffAuthentication);

        $store->store($this->ownership());

        $this->assertSame($staffAuthentication, $session->get('queueflow.auth'));
    }

    /**
     * @return array{GuestQueueOwnershipStore, Session}
     */
    private function ownershipStore(): array
    {
        $session = app(Session::class);
        $session->start();

        return [
            new GuestQueueOwnershipStore($session),
            $session,
        ];
    }

    private function ownership(
        int $queueId = 91,
        int $entryId = 301,
        string $ticketNumber = 'A023',
        #[\SensitiveParameter] string $guestToken = 'inert-guest-token-301',
    ): GuestQueueOwnershipData {
        return new GuestQueueOwnershipData(
            businessId: 10,
            branchId: 21,
            serviceId: 31,
            queueId: $queueId,
            entryId: $entryId,
            ticketNumber: $ticketNumber,
            guestToken: $guestToken,
        );
    }
}
