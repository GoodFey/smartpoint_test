<?php

namespace App\Integrations\BlogApi\CircuitBreaker;

use Illuminate\Support\Facades\Redis;

class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 5; // Fail after 5 errors
    private const SUCCESS_THRESHOLD = 2; // Success after 2 successes in half-open state
    private const TIMEOUT = 300; // 5 minutes timeout before trying again

    public const STATE_CLOSED = 'closed'; // Normal operation
    public const STATE_OPEN = 'open'; // Failing, reject requests
    public const STATE_HALF_OPEN = 'half_open'; // Testing if recovered

    /**
     * Create a new circuit breaker instance.
     *
     * @param string $clientId Unique identifier for the API client
     */
    public function __construct(
        private string $clientId,
    ) {
    }

    /**
     * Get current state of the circuit breaker.
     *
     * @return string One of: closed, open, half_open
     */
    public function getState(): string
    {
        $state = Redis::get("circuit:{$this->clientId}:state");

        if ($state === null) {
            return self::STATE_CLOSED;
        }

        if ($state === self::STATE_OPEN) {
            $openedAt = Redis::get("circuit:{$this->clientId}:opened_at");
            if ($openedAt && time() - $openedAt > self::TIMEOUT) {
                $this->setState(self::STATE_HALF_OPEN);
                return self::STATE_HALF_OPEN;
            }
        }

        return $state;
    }

    /**
     * Record a successful request.
     *
     * @return void
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            Redis::del("circuit:{$this->clientId}:failures");
            return;
        }

        if ($state === self::STATE_HALF_OPEN) {
            $successes = (int) Redis::get("circuit:{$this->clientId}:successes") + 1;
            Redis::set("circuit:{$this->clientId}:successes", $successes);

            if ($successes >= self::SUCCESS_THRESHOLD) {
                $this->setState(self::STATE_CLOSED);
                Redis::del("circuit:{$this->clientId}:successes");
                Redis::del("circuit:{$this->clientId}:failures");
            }
        }
    }

    /**
     * Record a failed request.
     *
     * @return void
     */
    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            return; // Already open, no need to count more failures
        }

        $failures = (int) Redis::get("circuit:{$this->clientId}:failures") + 1;
        Redis::set("circuit:{$this->clientId}:failures", $failures);

        if ($failures >= self::FAILURE_THRESHOLD) {
            $this->setState(self::STATE_OPEN);
        }
    }

    /**
     * Check if requests are allowed based on circuit state.
     *
     * @return bool
     * @throws \Exception If circuit is open
     */
    public function canRequest(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            throw new \Exception(
                "Circuit breaker is OPEN for client '{$this->clientId}'. "
                . "API is unavailable. Will retry in " . self::TIMEOUT . " seconds."
            );
        }

        return true;
    }

    /**
     * Set circuit state.
     *
     * @param string $state
     * @return void
     */
    private function setState(string $state): void
    {
        Redis::set("circuit:{$this->clientId}:state", $state);

        if ($state === self::STATE_OPEN) {
            Redis::set("circuit:{$this->clientId}:opened_at", time());
        } else {
            Redis::del("circuit:{$this->clientId}:opened_at");
        }
    }

    /**
     * Reset circuit breaker for testing/maintenance.
     *
     * @return void
     */
    public function reset(): void
    {
        Redis::del("circuit:{$this->clientId}:state");
        Redis::del("circuit:{$this->clientId}:failures");
        Redis::del("circuit:{$this->clientId}:successes");
        Redis::del("circuit:{$this->clientId}:opened_at");
    }
}
