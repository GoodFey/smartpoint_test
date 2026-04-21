<?php

namespace App\Integrations\BlogApi\RateLimiter;

class RateLimitExceededException extends \Exception
{
    public function __construct(string $message = "", \Exception $previous = null)
    {
        parent::__construct($message, 429, $previous);
    }
}
