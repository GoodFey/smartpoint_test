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

    /**
     * Get all available blogs
     *
     * @return array<string, BlogDto> Keyed by external ID
     */
    public function getAllBlogs(): array;

    /**
     * Get all posts for all blogs
     *
     * @return array<string, array<PostDto>> Posts grouped by blog external ID
     */
    public function getAllPosts(): array;
}

