<?php

namespace OzkanOzcan\Laravel404To301\Exceptions;

use RuntimeException;

class InvalidRedirectException extends RuntimeException
{
    /**
     * Thrown when from_url and to_url are identical (circular redirect).
     */
    public static function circular(string $url): self
    {
        return new self(
            sprintf(
                'Circular redirect detected: from_url and to_url are identical [%s]. ' .
                'A redirect cannot point to itself.',
                $url
            )
        );
    }

    /**
     * Thrown when an invalid HTTP redirect code is provided.
     */
    public static function invalidCode(int $code): self
    {
        return new self(
            sprintf(
                'Invalid redirect code [%d]. Allowed values are 301 (Permanent) and 302 (Temporary).',
                $code
            )
        );
    }
}
