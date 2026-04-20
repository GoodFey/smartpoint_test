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

        $blog->update(['last_checked_at' => now()]);

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
        $currentPosts = $blog->posts()
            ->get()
            ->keyBy('external_id');

        $apiExternalIds = [];
        $newPosts = [];

        foreach ($postDtos as $postDto) {
            $apiExternalIds[] = $postDto->externalId;

            if ($currentPosts->has($postDto->externalId)) {
                $this->updatePostIfChanged($currentPosts[$postDto->externalId], $postDto);
            } else {
                $createdPost = $this->createPost($blog, $postDto);
                $newPosts[] = [
                    'title' => $createdPost->title,
                    'rating' => $createdPost->rating,
                ];
            }
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
     * Update post if data has changed
     */
    private function updatePostIfChanged(Post $post, PostDto $postDto): void
    {
        $changes = [];

        if ($post->title !== $postDto->title) {
            $changes['title'] = $postDto->title;
        }

        if ($post->content !== $postDto->content) {
            $changes['content'] = $postDto->content;
        }

        if ($post->rating !== $postDto->rating) {
            $changes['rating'] = $postDto->rating;
        }

        if ($post->reactions !== $postDto->reactions) {
            $changes['reactions'] = $postDto->reactions;
        }

        if (!empty($changes)) {
            $post->update($changes);
        }
    }

    /**
     * Create new post from DTO
     */
    private function createPost(Blog $blog, PostDto $postDto): Post
    {
        return $blog->posts()->create([
            'external_id' => $postDto->externalId,
            'title' => $postDto->title,
            'content' => $postDto->content,
            'rating' => $postDto->rating,
            'reactions' => $postDto->reactions,
        ]);
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

