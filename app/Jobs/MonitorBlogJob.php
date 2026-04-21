<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Services\BlogMonitorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MonitorBlogJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $blogId,
    ) {
    }

    /**
     * Calculate the backoff delay based on attempt number
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [3, 10, 30];
    }

    /**
     * Execute the job.
     */
    public function handle(BlogMonitorService $service): void
    {
        $blog = Blog::find($this->blogId);

        if ($blog === null) {
            Log::warning('Blog not found for monitoring', ['blog_id' => $this->blogId]);
            return;
        }

        try {
            $service->monitor($blog);
        } catch (\Exception $e) {
            $isRateLimitError = $e->getCode() === 429;
            $isCircuitBreakerError = strpos($e->getMessage(), 'Circuit breaker is OPEN') !== false;

            if ($isRateLimitError) {
                Log::warning('Job: Rate limit exceeded', [
                    'blog_id' => $this->blogId,
                    'attempt' => $this->attempts(),
                ]);
            } elseif ($isCircuitBreakerError) {
                Log::warning('Job: Circuit breaker open', [
                    'blog_id' => $this->blogId,
                    'message' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);
            } else {
                Log::error('Job: Blog monitoring failed', [
                    'blog_id' => $this->blogId,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);
            }

            throw $e;
        }
    }
}

