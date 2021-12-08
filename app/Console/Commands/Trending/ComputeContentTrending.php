<?php

namespace App\Console\Commands\Trending;

use App\Jobs\Trending\Content\ComputeTrending;
use App\Models\Content;
use Illuminate\Console\Command;

class ComputeContentTrending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:compute-content-trending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate trending value for each content';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Content::where('is_available', 1)->whereIn('live_status', ['active', 'inactive'])->chunk(100000, function ($contents) {
            foreach ($contents as $content) {
                ComputeTrending::dispatch($content);
            }
        });
        return Command::SUCCESS;
    }
}
