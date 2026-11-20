<?php

namespace App\Exceptions;

use RuntimeException;

class UserGateException extends RuntimeException
{
    public const KIND_INVALID_CREDENTIALS = 'invalid_credentials';
    public const KIND_UNAUTHORIZED = 'unauthorized';
    public const KIND_FORBIDDEN = 'forbidden';
    public const KIND_VALIDATION = 'validation';
    public const KIND_RATE_LIMIT = 'rate_limit';
    public const KIND_SERVER = 'server';
    public const KIND_NETWORK = 'network';

    public function __construct(
        string $message = '',
        int $code = 0,
        public readonly string $kind = self::KIND_SERVER,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $code);
    }

    public function shouldFallback(): bool
    {
        return in_array($this->kind, [self::KIND_NETWORK, self::KIND_SERVER], true);
    }
}
