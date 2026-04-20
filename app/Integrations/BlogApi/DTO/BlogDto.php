<?php

namespace App\Integrations\BlogApi\DTO;

readonly class BlogDto
{
    public function __construct(
        public string $title,
        public string $author,
        public string $catName,
        public float $rating,
    ) {
    }
}

