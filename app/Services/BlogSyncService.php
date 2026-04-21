<?php

namespace App\Services;

use App\Integrations\BlogApi\DTO\BlogDto;
use App\Integrations\BlogApi\DTO\PostDto;
use App\Models\Blog;
use App\Models\Post;

class BlogSyncService
{
    /**
     * Complete synchronization of blog metadata and posts
     *
     * @param Blog $blog Blog to sync
     * @param BlogDto $blogDto Blog metadata from API
     * @param array<PostDto> $postDtos Posts from API
     * @return array<array{title: string, rating: float}> New posts for notification
     */
    public function sync(Blog $blog, BlogDto $blogDto, array $postDtos): array
    {
        $this->syncPostMetadata($blog, $blogDto);

        $newPosts = $this->syncPosts($blog, $postDtos);

        $blog->update([
            'next_check_at' => now()->addHours($blog->monitoring_interval),
        ]);

        return $newPosts;
    }

    /**
     * Synchronize blog posts with data from API
     *
     * @param Blog $blog Blog to sync
     * @param array<PostDto> $postDtos Posts from API
     * @return array<array{title: string, rating: float}> New posts for notification
     */
    public function syncPosts(Blog $blog, array $postDtos): array
    {
        $existingExternalIds = $blog->posts()
            ->pluck('external_id')
            ->flip();

        $apiExternalIds = [];
        $newPosts = [];
        $upsertData = [];

        foreach ($postDtos as $postDto) {
            $apiExternalIds[] = $postDto->externalId;

            if (!$existingExternalIds->has($postDto->externalId)) {
                $newPosts[] = [
                    'title' => $postDto->title,
                    'rating' => $postDto->rating,
                ];
            }

            $upsertData[] = [
                'blog_id' => $blog->id,
                'external_id' => $postDto->externalId,
                'title' => $postDto->title,
                'content' => $postDto->content,
                'rating' => $postDto->rating,
                'reactions' => $postDto->reactions,
            ];
        }

        if (!empty($upsertData)) {
            Post::query()->upsert(
                $upsertData,
                uniqueBy: ['blog_id', 'external_id'],
                update: ['title', 'content', 'rating', 'reactions', 'updated_at']
            );
        }

        $this->deleteRemovedPosts($blog, $apiExternalIds);

        return $newPosts;
    }

    /**
     * Synchronize blog metadata with data from API
     *
     * @param Blog $blog Blog to sync
     * @param BlogDto $blogDto Blog data from API
     * @return bool True if blog was updated, false otherwise
     */
    public function syncPostMetadata(Blog $blog, BlogDto $blogDto): bool
    {
        $changes = [];

        if ($blog->title !== $blogDto->title) {
            $changes['title'] = $blogDto->title;
        }

        if ($blog->author !== $blogDto->author) {
            $changes['author'] = $blogDto->author;
        }

        if ($blog->cat_name !== $blogDto->catName) {
            $changes['cat_name'] = $blogDto->catName;
        }

        if ($blog->rating !== $blogDto->rating) {
            $changes['rating'] = $blogDto->rating;
        }

        if (!empty($changes)) {
            $blog->update($changes);
            return true;
        }

        return false;
    }


    /**
     * Delete posts that are no longer in API
     *
     * @param Blog $blog
     * @param array<string> $apiExternalIds External IDs from API
     */
    private function deleteRemovedPosts(Blog $blog, array $apiExternalIds): void
    {
        $blog->posts()
            ->whereNotIn('external_id', $apiExternalIds)
            ->delete();
    }
}

