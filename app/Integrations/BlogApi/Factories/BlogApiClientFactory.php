<?php

namespace App\Integrations\BlogApi\Factories;

use App\Integrations\BlogApi\Clients\MockBlogApiClient;
use App\Integrations\BlogApi\Contracts\BlogApiClientInterface;

class BlogApiClientFactory
{
    /**
     * Registry of available API clients by resource type
     *
     * @var array<string, callable>
     */
    private array $registry = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Register default API clients
     */
    private function registerDefaults(): void
    {
        $this->register('mock', fn () => new MockBlogApiClient());
    }

    /**
     * Register a new API client for a resource
     *
     * @param string $resource Resource type identifier
     * @param callable $factory Factory function that returns BlogApiClientInterface instance
     */
    public function register(string $resource, callable $factory): void
    {
        $this->registry[$resource] = $factory;
    }

    /**
     * Create API client for the given resource
     *
     * @param string $resource Resource type identifier
     * @return BlogApiClientInterface
     * @throws \InvalidArgumentException If resource is not registered
     */
    public function make(string $resource): BlogApiClientInterface
    {
        if (!isset($this->registry[$resource])) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Unknown resource '%s'. Available resources: %s",
                    $resource,
                    implode(', ', array_keys($this->registry))
                )
            );
        }

        $factory = $this->registry[$resource];
        return $factory();
    }

    /**
     * Get all registered resources
     *
     * @return array<string>
     */
    public function getAvailableResources(): array
    {
        return array_keys($this->registry);
    }
}

