<?php

declare(strict_types=1);

namespace Paybeta\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown for anything that isn't an API response: request timeout, DNS /
 * connection failures, webhook signature failure, missing configuration.
 * Reserve PaybetaApiException for "the API responded, and it was an error."
 */
class PaybetaException extends Exception
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
