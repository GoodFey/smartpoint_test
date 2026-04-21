<?php

namespace App\Console\Commands;

use App\Jobs\MonitorBlogJob;
use App\Models\Blog;
use Illuminate\Console\Command;

class MonitorBlogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'blogs:monitor';

    /**
     * The description of the console command.
     */
    protected $description = 'Monitor all blogs that need to be checked based on monitoring_interval';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting blog monitoring...');

        $count = 0;
        $delayMs = 100;

        Blog::query()
            ->needsMonitoring()
            ->cursor()
            ->each(function ($blog) use (&$count, $delayMs) {
                MonitorBlogJob::dispatch($blog->id);
                $count++;

                if ($count % 100 === 0) {
                    $this->line("Dispatched {$count} jobs so far...");
                }

                usleep($delayMs * 1000);
            });

        $this->info("Successfully dispatched {$count} monitoring jobs");

        return self::SUCCESS;
    }
}

