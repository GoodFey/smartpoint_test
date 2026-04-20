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
            return;
        }

        $service->monitor($blog);
    }
}

