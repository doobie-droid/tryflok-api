<?php

namespace App\Console\Commands\Trending;

use App\Jobs\Trending\Collection\ComputeTrending;
use App\Models\Collection;
use Illuminate\Console\Command;

class ComputeCollectionTrending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:compute-collection-trending';

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
        Collection::where('is_available', 1)->chunk(100000, function ($collections) {
            foreach ($collections as $collection) {
                ComputeTrending::dispatch($collection);
            }
        });
        return Command::SUCCESS;
    }
}
