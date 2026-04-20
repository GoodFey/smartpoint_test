<?php

namespace App\Integrations\BlogApi\Contracts;

use App\Integrations\BlogApi\DTO\BlogDto;
use App\Integrations\BlogApi\DTO\PostDto;

interface BlogApiClientInterface
{
    /**
     * Get blog metadata from external API
     *
     * @param string $externalId Blog ID in external system
     * @return BlogDto
     */
    public function getBlog(string $externalId): BlogDto;

    /**
     * Get all posts for a blog from external API
     *
     * @param string $externalId Blog ID in external system
     * @return array<PostDto>
     */
    public function getPosts(string $externalId): array;
}

