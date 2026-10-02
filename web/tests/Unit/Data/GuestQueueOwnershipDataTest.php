<?php

namespace Tests\Unit\Data;

use App\Data\GuestQueueOwnershipData;
use Tests\TestCase;

class GuestQueueOwnershipDataTest extends TestCase
{
    public function test_guest_token_is_available_server_side_but_not_json_serialized(): void
    {
        $ownership = new GuestQueueOwnershipData(
            businessId: 10,
            branchId: 21,
            serviceId: 31,
            queueId: 91,
            entryId: 301,
            ticketNumber: 'A023',
            guestToken: 'inert-guest-token',
        );

        $this->assertSame('inert-guest-token', $ownership->guestToken());
        $this->assertStringNotContainsString(
            'inert-guest-token',
            (string) json_encode($ownership, JSON_THROW_ON_ERROR),
        );
    }
}
