<?php

namespace Tests\Unit\Data;

use App\Data\CustomerQueueTicketData;
use Tests\TestCase;

class CustomerQueueTicketDataTest extends TestCase
{
    public function test_serialized_ticket_contains_no_guest_or_idempotency_credentials(): void
    {
        $ticket = new CustomerQueueTicketData(
            businessId: 10,
            branchId: 21,
            serviceId: 31,
            queueId: 91,
            entryId: 301,
            ticketNumber: 'A023',
            status: 'WAITING',
        );

        $serialized = (string) json_encode($ticket, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('guestToken', $serialized);
        $this->assertStringNotContainsString('inert-guest-token', $serialized);
        $this->assertStringNotContainsString('idempotency', $serialized);
        $this->assertStringNotContainsString('11111111-1111-4111-8111-111111111111', $serialized);
    }
}
