<?php

namespace App\Services;

use App\Data\GuestQueueOwnershipData;
use Illuminate\Contracts\Session\Session;

class GuestQueueOwnershipStore
{
    private const string SESSION_KEY = 'queueflow.customer.entries';

    public function __construct(
        private Session $session,
    ) {}

    public function store(
        #[\SensitiveParameter] GuestQueueOwnershipData $ownership,
    ): void {
        $entries = $this->storedEntries();
        $entries[$this->ownershipKey($ownership->queueId, $ownership->entryId)] = [
            'businessId' => $ownership->businessId,
            'branchId' => $ownership->branchId,
            'serviceId' => $ownership->serviceId,
            'queueId' => $ownership->queueId,
            'entryId' => $ownership->entryId,
            'ticketNumber' => $ownership->ticketNumber,
            'guestToken' => $ownership->guestToken(),
        ];

        $this->session->put(self::SESSION_KEY, $entries);
    }

    public function find(int $queueId, int $entryId): ?GuestQueueOwnershipData
    {
        $entry = $this->storedEntries()[$this->ownershipKey($queueId, $entryId)] ?? null;

        return $this->ownershipFromSession($entry);
    }

    public function has(int $queueId, int $entryId): bool
    {
        return $this->find($queueId, $entryId) !== null;
    }

    /**
     * @return list<GuestQueueOwnershipData>
     */
    public function all(): array
    {
        $ownerships = [];

        foreach ($this->storedEntries() as $entry) {
            $ownership = $this->ownershipFromSession($entry);

            if ($ownership !== null) {
                $ownerships[] = $ownership;
            }
        }

        return $ownerships;
    }

    public function remove(int $queueId, int $entryId): void
    {
        $entries = $this->storedEntries();
        unset($entries[$this->ownershipKey($queueId, $entryId)]);

        if ($entries === []) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $this->session->put(self::SESSION_KEY, $entries);
    }

    /**
     * @return array<string, mixed>
     */
    private function storedEntries(): array
    {
        $entries = $this->session->get(self::SESSION_KEY, []);

        return is_array($entries) ? $entries : [];
    }

    private function ownershipKey(int $queueId, int $entryId): string
    {
        return "{$queueId}:{$entryId}";
    }

    private function ownershipFromSession(
        #[\SensitiveParameter] mixed $entry,
    ): ?GuestQueueOwnershipData {
        if (! is_array($entry)
            || ! is_int($entry['businessId'] ?? null)
            || ! is_int($entry['branchId'] ?? null)
            || ! is_int($entry['queueId'] ?? null)
            || ! is_int($entry['entryId'] ?? null)
            || ! is_string($entry['ticketNumber'] ?? null)
            || ! is_string($entry['guestToken'] ?? null)
            || (! is_null($entry['serviceId'] ?? null) && ! is_int($entry['serviceId']))) {
            return null;
        }

        return new GuestQueueOwnershipData(
            businessId: $entry['businessId'],
            branchId: $entry['branchId'],
            serviceId: $entry['serviceId'] ?? null,
            queueId: $entry['queueId'],
            entryId: $entry['entryId'],
            ticketNumber: $entry['ticketNumber'],
            guestToken: $entry['guestToken'],
        );
    }
}
