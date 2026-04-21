<?php

namespace App\Integrations\BlogApi\RateLimiter;

use Illuminate\Support\Facades\Redis;

class RateLimiter
{
    private const WINDOW_SIZE = 3600; // 1 hour in seconds
    private const REQUEST_LIMIT = 1000; // requests per hour

    /**
     * Create a new rate limiter instance.
     *
     * @param string $clientId Unique identifier for the API client
     */
    public function __construct(
        private string $clientId,
    ) {
    }

    /**
     * Check if a request can be made within the rate limit.
     * Returns remaining requests or throws exception if limit exceeded.
     *
     * @return int Remaining requests in current window
     * @throws RateLimitExceededException
     */
    public function allowRequest(): int
    {
        $key = "rate_limit:{$this->clientId}";
        $now = time();
        $windowStart = $now - self::WINDOW_SIZE;

        $pipe = Redis::pipeline(function ($pipe) use ($key, $windowStart, $now) {
            $pipe->zremrangebyscore($key, 0, $windowStart);
            $pipe->zadd($key, $now, $now . '-' . uniqid('', true));
            $pipe->zcard($key);
            $pipe->expire($key, self::WINDOW_SIZE + 60);
        });

        $requestCount = $pipe[2];
        $remaining = self::REQUEST_LIMIT - $requestCount;

        if ($remaining < 0) {
            throw new RateLimitExceededException(
                "Rate limit exceeded for client '{$this->clientId}'. "
                . "Limit: " . self::REQUEST_LIMIT . " requests per hour."
            );
        }

        return $remaining;
    }

    /**
     * Get current request count in the window.
     *
     * @return int
     */
    public function getCurrentCount(): int
    {
        $key = "rate_limit:{$this->clientId}";
        $now = time();
        $windowStart = $now - self::WINDOW_SIZE;

        return Redis::zcount($key, $windowStart, $now);
    }

    /**
     * Reset rate limiter for testing/maintenance.
     *
     * @return void
     */
    public function reset(): void
    {
        Redis::del("rate_limit:{$this->clientId}");
    }
}
