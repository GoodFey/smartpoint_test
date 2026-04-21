<?php

namespace App\Integrations\BlogApi\Clients;

use App\Integrations\BlogApi\CircuitBreaker\CircuitBreaker;
use App\Integrations\BlogApi\Contracts\BlogApiClientInterface;
use App\Integrations\BlogApi\DTO\BlogDto;
use App\Integrations\BlogApi\DTO\PostDto;
use App\Integrations\BlogApi\RateLimiter\RateLimiter;

class MockBlogApiClient implements BlogApiClientInterface
{
    private RateLimiter $rateLimiter;
    private CircuitBreaker $circuitBreaker;

    public function __construct()
    {
        $this->rateLimiter = new RateLimiter('mock');
        $this->circuitBreaker = new CircuitBreaker('mock');
    }

    /**
     * Mock data store: external_id => blog data
     */
    private array $blogs = [
        'cat-blog-001' => [
            'title' => 'Whisker Chronicles',
            'author' => 'Tom Henderson',
            'cat_name' => 'Mittens',
            'rating' => 4.8,
        ],
        'cat-blog-002' => [
            'title' => 'Purr Academy',
            'author' => 'Sarah Miller',
            'cat_name' => 'Shadowpaw',
            'rating' => 4.5,
        ],
        'cat-blog-003' => [
            'title' => 'Meow Daily',
            'author' => 'John Smith',
            'cat_name' => 'Fluffington',
            'rating' => 4.2,
        ],
        'cat-blog-004' => [
            'title' => 'Paws & Whiskers Weekly',
            'author' => 'Emma Wilson',
            'cat_name' => 'Luna',
            'rating' => 4.9,
        ],
        'cat-blog-005' => [
            'title' => 'The Tabby Times',
            'author' => 'Michael Chen',
            'cat_name' => 'Tiger',
            'rating' => 4.6,
        ],
        'cat-blog-006' => [
            'title' => 'Feline Fanatics',
            'author' => 'Jessica Brown',
            'cat_name' => 'Cleo',
            'rating' => 4.3,
        ],
        'cat-blog-007' => [
            'title' => 'The Cat Detective',
            'author' => 'Robert Davis',
            'cat_name' => 'Sherlock',
            'rating' => 4.7,
        ],
    ];

    /**
     * Mock data store: blog_external_id => posts array
     */
    private array $posts = [
        'cat-blog-001' => [
            [
                'external_id' => 'post-001-1',
                'title' => 'Tips for Cat Grooming',
                'content' => 'Learn the best practices for keeping your cat healthy and beautiful.',
                'rating' => 4.9,
                'reactions' => ['like' => 42, 'love' => 18, 'cat' => 25],
            ],
            [
                'external_id' => 'post-001-2',
                'title' => 'Understanding Cat Behavior',
                'content' => 'Decode your cat\'s mysterious behaviors and what they really mean.',
                'rating' => 4.7,
                'reactions' => ['like' => 35, 'love' => 12, 'cat' => 8],
            ],
            [
                'external_id' => 'post-001-3',
                'title' => 'Best Cat Toys of 2026',
                'content' => 'A comprehensive guide to toys that will keep your feline friend entertained.',
                'rating' => 4.6,
                'reactions' => ['like' => 28, 'love' => 15, 'cat' => 31],
            ],
            [
                'external_id' => 'post-001-4',
                'title' => 'Whisker Health: A Complete Guide',
                'content' => 'Everything you need to know about maintaining your cat\'s whiskers and sensitivity.',
                'rating' => 4.8,
                'reactions' => ['like' => 51, 'love' => 22, 'cat' => 14],
            ],
            [
                'external_id' => 'post-001-5',
                'title' => 'Indoor vs Outdoor Cats',
                'content' => 'Pros and cons of different living environments for your feline companion.',
                'rating' => 4.5,
                'reactions' => ['like' => 38, 'love' => 9, 'cat' => 19],
            ],
        ],
        'cat-blog-002' => [
            [
                'external_id' => 'post-002-1',
                'title' => 'Cat Training Basics',
                'content' => 'Surprising facts: you CAN train your cat. Here\'s how.',
                'rating' => 4.8,
                'reactions' => ['like' => 55, 'laugh' => 22, 'cat' => 17],
            ],
            [
                'external_id' => 'post-002-2',
                'title' => 'Nutrition for Feline Health',
                'content' => 'Complete guide to feeding your cat the right diet for optimal health.',
                'rating' => 4.4,
                'reactions' => ['like' => 32, 'love' => 19, 'cat' => 5],
            ],
            [
                'external_id' => 'post-002-3',
                'title' => 'Common Cat Health Issues',
                'content' => 'Recognizing and treating the most common health problems in cats.',
                'rating' => 4.7,
                'reactions' => ['like' => 48, 'love' => 27, 'cat' => 11],
            ],
            [
                'external_id' => 'post-002-4',
                'title' => 'Litter Box Mastery',
                'content' => 'The ultimate guide to keeping your litter box clean and your cat happy.',
                'rating' => 4.6,
                'reactions' => ['like' => 25, 'love' => 8, 'cat' => 22],
            ],
        ],
        'cat-blog-003' => [
            [
                'external_id' => 'post-003-1',
                'title' => 'Daily Cat Care Routine',
                'content' => 'Everything you need to do every day to keep your cat happy and healthy.',
                'rating' => 4.3,
                'reactions' => ['like' => 19, 'love' => 8, 'cat' => 12],
            ],
            [
                'external_id' => 'post-003-2',
                'title' => 'Understanding Feline Language',
                'content' => 'What your cat is trying to communicate through meows, purrs, and body language.',
                'rating' => 4.5,
                'reactions' => ['like' => 31, 'laugh' => 14, 'cat' => 9],
            ],
            [
                'external_id' => 'post-003-3',
                'title' => 'Playtime Essentials',
                'content' => 'How to engage in proper playtime with your cat for mental and physical health.',
                'rating' => 4.4,
                'reactions' => ['like' => 26, 'love' => 13, 'cat' => 18],
            ],
            [
                'external_id' => 'post-003-4',
                'title' => 'Cat Scratching: Normal or Problem?',
                'content' => 'Understanding scratching behavior and how to redirect it appropriately.',
                'rating' => 4.2,
                'reactions' => ['like' => 22, 'love' => 6, 'cat' => 15],
            ],
            [
                'external_id' => 'post-003-5',
                'title' => 'Traveling with Your Cat',
                'content' => 'Tips and tricks for making travel safer and less stressful for your feline friend.',
                'rating' => 4.6,
                'reactions' => ['like' => 29, 'love' => 16, 'cat' => 7],
            ],
        ],
        'cat-blog-004' => [
            [
                'external_id' => 'post-004-1',
                'title' => 'Advanced Cat Psychology',
                'content' => 'Deep dive into the complex psychology behind your cat\'s quirky behaviors.',
                'rating' => 4.9,
                'reactions' => ['like' => 67, 'love' => 31, 'cat' => 28],
            ],
            [
                'external_id' => 'post-004-2',
                'title' => 'Breeding Considerations',
                'content' => 'Ethical breeding practices and what responsible cat owners should know.',
                'rating' => 4.7,
                'reactions' => ['like' => 44, 'love' => 12, 'cat' => 19],
            ],
            [
                'external_id' => 'post-004-3',
                'title' => 'Senior Cat Care',
                'content' => 'Special needs and care strategies for aging cats.',
                'rating' => 4.8,
                'reactions' => ['like' => 53, 'love' => 24, 'cat' => 10],
            ],
            [
                'external_id' => 'post-004-4',
                'title' => 'Kitten Development Stages',
                'content' => 'Understanding the growth and development phases of kittens.',
                'rating' => 4.6,
                'reactions' => ['like' => 39, 'love' => 28, 'cat' => 35],
            ],
        ],
        'cat-blog-005' => [
            [
                'external_id' => 'post-005-1',
                'title' => 'Striped Beauty: All About Tabbies',
                'content' => 'The complete guide to tabby cats, their patterns, and personalities.',
                'rating' => 4.8,
                'reactions' => ['like' => 58, 'love' => 33, 'cat' => 42],
            ],
            [
                'external_id' => 'post-005-2',
                'title' => 'Black Cats: Myth vs Reality',
                'content' => 'Debunking superstitions and celebrating black cat charm.',
                'rating' => 4.5,
                'reactions' => ['like' => 45, 'love' => 20, 'cat' => 15],
            ],
            [
                'external_id' => 'post-005-3',
                'title' => 'Siamese Personalities Explained',
                'content' => 'Understanding the vocal and affectionate nature of Siamese cats.',
                'rating' => 4.7,
                'reactions' => ['like' => 52, 'laugh' => 18, 'cat' => 21],
            ],
            [
                'external_id' => 'post-005-4',
                'title' => 'Rescue and Adoption Stories',
                'content' => 'Inspiring tales of cats finding their forever homes.',
                'rating' => 4.6,
                'reactions' => ['like' => 49, 'love' => 41, 'cat' => 8],
            ],
            [
                'external_id' => 'post-005-5',
                'title' => 'Long-haired vs Short-haired Care',
                'content' => 'Grooming and maintenance differences for various coat types.',
                'rating' => 4.4,
                'reactions' => ['like' => 33, 'love' => 11, 'cat' => 16],
            ],
        ],
        'cat-blog-006' => [
            [
                'external_id' => 'post-006-1',
                'title' => 'Multi-Cat Households',
                'content' => 'Managing dynamics when you have multiple cats under one roof.',
                'rating' => 4.5,
                'reactions' => ['like' => 36, 'love' => 14, 'cat' => 9],
            ],
            [
                'external_id' => 'post-006-2',
                'title' => 'Allergies in Cats',
                'content' => 'Identifying and managing allergic reactions in your feline friend.',
                'rating' => 4.6,
                'reactions' => ['like' => 27, 'love' => 18, 'cat' => 12],
            ],
            [
                'external_id' => 'post-006-3',
                'title' => 'Enrichment Ideas for Lazy Cats',
                'content' => 'Creative ways to keep your indoor cat mentally stimulated.',
                'rating' => 4.4,
                'reactions' => ['like' => 41, 'love' => 9, 'cat' => 24],
            ],
            [
                'external_id' => 'post-006-4',
                'title' => 'Understanding Cat Anxiety',
                'content' => 'Recognizing and addressing anxiety issues in your cat.',
                'rating' => 4.3,
                'reactions' => ['like' => 30, 'love' => 16, 'cat' => 7],
            ],
        ],
        'cat-blog-007' => [
            [
                'external_id' => 'post-007-1',
                'title' => 'Microchipping Your Cat',
                'content' => 'Why microchipping is essential for cat safety and identification.',
                'rating' => 4.8,
                'reactions' => ['like' => 46, 'love' => 19, 'cat' => 13],
            ],
            [
                'external_id' => 'post-007-2',
                'title' => 'Claw Care and Scratching Posts',
                'content' => 'Proper nail maintenance and providing appropriate scratching surfaces.',
                'rating' => 4.6,
                'reactions' => ['like' => 38, 'love' => 14, 'cat' => 20],
            ],
            [
                'external_id' => 'post-007-3',
                'title' => 'The Mystery of Purring',
                'content' => 'Scientific exploration of why and how cats purr.',
                'rating' => 4.7,
                'reactions' => ['like' => 54, 'love' => 26, 'cat' => 17],
            ],
            [
                'external_id' => 'post-007-4',
                'title' => 'Cat Dental Health',
                'content' => 'Brushing, dental disease prevention, and oral care for cats.',
                'rating' => 4.5,
                'reactions' => ['like' => 32, 'love' => 11, 'cat' => 9],
            ],
            [
                'external_id' => 'post-007-5',
                'title' => 'Creating a Cat-Friendly Home',
                'content' => 'Design tips for making your home environment optimal for your feline.',
                'rating' => 4.9,
                'reactions' => ['like' => 61, 'love' => 35, 'cat' => 29],
            ],
        ],
    ];

    public function getBlog(string $externalId): BlogDto
    {
        $this->circuitBreaker->canRequest();
        $this->rateLimiter->allowRequest();

        try {
            $data = $this->blogs[$externalId] ?? null;

            if ($data === null) {
                throw new \InvalidArgumentException("Blog with external ID '{$externalId}' not found");
            }

            $result = new BlogDto(
                title: $data['title'],
                author: $data['author'],
                catName: $data['cat_name'],
                rating: $data['rating'],
            );

            $this->circuitBreaker->recordSuccess();

            return $result;
        } catch (\Exception $e) {
            // Don't record rate limit as failure
            if ($e->getCode() !== 429) {
                $this->circuitBreaker->recordFailure();
            }
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function getPosts(string $externalId): array
    {
        $this->circuitBreaker->canRequest();
        $this->rateLimiter->allowRequest();

        try {
            $posts = $this->posts[$externalId] ?? [];

            $result = array_map(
                fn (array $post) => new PostDto(
                    externalId: $post['external_id'],
                    title: $post['title'],
                    content: $post['content'],
                    rating: $post['rating'],
                    reactions: $post['reactions'],
                ),
                $posts
            );

            $this->circuitBreaker->recordSuccess();

            return $result;
        } catch (\Exception $e) {
            // Don't record rate limit as failure
            if ($e->getCode() !== 429) {
                $this->circuitBreaker->recordFailure();
            }
            throw $e;
        }
    }
}

