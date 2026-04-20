<?php

namespace App\Integrations\BlogApi\DTO;

readonly class PostDto
{
    /**
     * @param string $externalId Post ID in external system
     * @param string $title Post title
     * @param string $content Post content
     * @param float $rating Post rating
     * @param array $reactions Reactions keyed by type: ['like' => 10, 'laugh' => 3]
     */
    public function __construct(
        public string $externalId,
        public string $title,
        public string $content,
        public float $rating,
        public array $reactions = [],
    ) {
    }
}

