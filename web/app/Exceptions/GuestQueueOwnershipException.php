<?php

namespace App\Exceptions;

use RuntimeException;

class GuestQueueOwnershipException extends RuntimeException
{
    public static function missing(): self
    {
        return new self('This browser session does not own this queue entry.');
    }

    public static function missingGuestToken(): self
    {
        return new self('Guest queue ownership credentials were not returned.');
    }
}
