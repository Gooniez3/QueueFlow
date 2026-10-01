<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class QueueFlowApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $validationErrors
     */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $validationErrors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
