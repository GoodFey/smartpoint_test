<?php

namespace App\Services;

use App\Integrations\BlogApi\Factories\BlogApiClientFactory;
use App\Models\Blog;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class BlogMonitorService
{
    public function __construct(
        private BlogApiClientFactory $apiClientFactory,
        private BlogSyncService $syncService,
    ) {
    }

    /**
     * Monitor blog: fetch data from API and sync with local DB
     *
     * @param Blog $blog Blog to monitor
     * @return void
     */
    public function monitor(Blog $blog): void
    {
        try {
            $apiClient = $this->apiClientFactory->make($blog->resource);

            $blogDto = $apiClient->getBlog($blog->external_id);
            $postDtos = $apiClient->getPosts($blog->external_id);

            $newPosts = $this->syncService->sync($blog, $blogDto, $postDtos);

            if (!empty($newPosts)) {
                Notification::create([
                    'blog_id' => $blog->id,
                    'new_posts' => $newPosts,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Blog monitoring failed', [
                'blog_id' => $blog->id,
                'resource' => $blog->resource,
                'external_id' => $blog->external_id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        }
    }
}

