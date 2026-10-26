<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;

class GuestFavouriteStore
{
    private const string SESSION_KEY = 'queueflow.customer.favourites';

    public function __construct(private readonly Session $session) {}

    /** @return list<int> */
    public function all(): array
    {
        $ids = $this->session->get(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $ids,
            static fn (mixed $id): bool => is_int($id) && $id > 0,
        )));
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function has(int $businessId): bool
    {
        return in_array($businessId, $this->all(), true);
    }

    public function add(int $businessId): void
    {
        $ids = $this->all();

        if (! in_array($businessId, $ids, true)) {
            $ids[] = $businessId;
            $this->session->put(self::SESSION_KEY, $ids);
        }
    }

    public function remove(int $businessId): void
    {
        $ids = array_values(array_filter($this->all(), static fn (int $id): bool => $id !== $businessId));

        if ($ids === []) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $this->session->put(self::SESSION_KEY, $ids);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
