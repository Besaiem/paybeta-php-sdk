<?php

declare(strict_types=1);

namespace Paybeta\Exceptions;

/**
 * Thrown when the API returns a non-2xx response. Mirrors the real error
 * envelope shape — {"status":"error","error":{"code","message","traceId","timestamp"}}
 * — every api-main error response actually sends.
 */
class PaybetaApiException extends PaybetaException
{
    /**
     * @param string $errorCode The machine-readable error code from the API
     *     response body (e.g. "NOT_FOUND") — named to avoid colliding with
     *     the built-in \Exception::$code (an int, unrelated in meaning).
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly string $traceId,
        public readonly string $timestamp,
    ) {
        parent::__construct($message);
    }
}
