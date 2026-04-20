<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Services\BlogMonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MonitorBlogJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $blogId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(BlogMonitorService $service): void
    {
        $blog = Blog::find($this->blogId);

        if ($blog === null) {
            \Log::warning('Blog not found for monitoring', ['blog_id' => $this->blogId]);
            return;
        }

        try {
            $service->monitor($blog);
        } catch (\Exception $e) {
            \Log::error('Job: Blog monitoring failed', [
                'job' => self::class,
                'blog_id' => $this->blogId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

