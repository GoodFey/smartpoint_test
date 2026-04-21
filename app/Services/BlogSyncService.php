<?php

namespace App\Services;

use App\Integrations\BlogApi\DTO\BlogDto;
use App\Integrations\BlogApi\DTO\PostDto;
use App\Models\Blog;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class BlogSyncService
{
    public function sync(Blog $blog, BlogDto $blogDto, array $postDtos): array
    {
        $this->syncPostMetadata($blog, $blogDto);

        return $this->syncPosts($blog, $postDtos);
    }

    public function syncPosts(Blog $blog, array $postDtos): array
    {
        return DB::transaction(function () use ($blog, $postDtos) {
            $apiExternalIds = array_map(fn($dto) => $dto->externalId, $postDtos);
            $existingPosts = $this->getExistingPosts($blog, $apiExternalIds);

            ['newPosts' => $newPosts, 'createData' => $createData, 'updateData' => $updateData] =
                $this->prepareSyncData($blog, $postDtos, $existingPosts);

            $this->executeSyncOperations($createData, $updateData);
            $this->deleteRemovedPosts($blog, $apiExternalIds);

            return $newPosts;
        });
    }

    private function getExistingPosts(Blog $blog, array $apiExternalIds)
    {
        return $blog->posts()
            ->whereIn('external_id', $apiExternalIds)
            ->get()
            ->keyBy('external_id');
    }

    private function prepareSyncData(Blog $blog, array $postDtos, $existingPosts): array
    {
        $newPosts = [];
        $createData = [];
        $updateData = [];

        foreach ($postDtos as $dto) {
            if ($existingPosts->has($dto->externalId)) {
                $changes = $this->getPostChanges($existingPosts[$dto->externalId], $dto);

                if (!empty($changes)) {
                    $updateData[] = array_merge($changes, [
                        'blog_id' => $blog->id,
                        'external_id' => $dto->externalId,
                    ]);
                }
            } else {
                $newPosts[] = [
                    'title' => $dto->title,
                    'rating' => $dto->rating,
                ];

                $createData[] = [
                    'blog_id' => $blog->id,
                    'external_id' => $dto->externalId,
                    'title' => $dto->title,
                    'content' => $dto->content,
                    'rating' => $dto->rating,
                    'reactions' => $dto->reactions,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return [
            'newPosts' => $newPosts,
            'createData' => $createData,
            'updateData' => $updateData,
        ];
    }

    private function executeSyncOperations(array $createData, array $updateData): void
    {
        if (!empty($createData)) {
            Post::query()->insert($createData);
        }

        if (!empty($updateData)) {
            $updateColumns = collect($updateData)
                ->flatMap(fn($item) => array_keys($item))
                ->unique()
                ->diff(['blog_id', 'external_id'])
                ->values()
                ->toArray();

            Post::query()->upsert(
                $updateData,
                ['blog_id', 'external_id'],
                $updateColumns
            );
        }
    }

    private function deleteRemovedPosts(Blog $blog, array $apiExternalIds): void
    {
        $blog->posts()
            ->whereNotIn('external_id', $apiExternalIds)
            ->delete();
    }

    private function getPostChanges(Post $post, PostDto $dto): array
    {
        $changes = [];

        if ($post->title !== $dto->title) {
            $changes['title'] = $dto->title;
        }

        if ($post->content !== $dto->content) {
            $changes['content'] = $dto->content;
        }

        if ($post->rating !== $dto->rating) {
            $changes['rating'] = $dto->rating;
        }

        if ($post->reactions !== json_encode($dto->reactions)) {
            $changes['reactions'] = $dto->reactions;
        }

        if (!empty($changes)) {
            $changes['updated_at'] = now();
        }

        return $changes;
    }

    public function syncPostMetadata(Blog $blog, BlogDto $dto): bool
    {
        $changes = [];

        if ($blog->title !== $dto->title) {
            $changes['title'] = $dto->title;
        }

        if ($blog->author !== $dto->author) {
            $changes['author'] = $dto->author;
        }

        if ($blog->cat_name !== $dto->catName) {
            $changes['cat_name'] = $dto->catName;
        }

        if ($blog->rating !== $dto->rating) {
            $changes['rating'] = $dto->rating;
        }

        if (!empty($changes)) {
            $blog->update($changes);
            return true;
        }

        return false;
    }
}
