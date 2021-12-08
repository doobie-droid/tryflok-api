<?php

namespace App\Jobs\Trending\Collection;

use App\Constants\Constants;
use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComputeTrending implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $collection;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $collectionTrendingCategory = $this->collection->categories()->where('name', 'trending')->first();
        if (is_null($collectionTrendingCategory)) {
            $trendingCategory = Category::where('name', 'trending')->first();
            $this->collection->categories()->syncWithoutDetaching([
                $trendingCategory->id => [
                    'id' => Str::uuid(),
                ],
            ]);
        }

        $contents_trending_aggregate = $this->collection->contents()->sum('trending_points');
        $this->collection->trending_points = bcmul($contents_trending_aggregate, Constants::TRENDING_COLLECTION_WEIGHT, 0);
        $this->collection->save();

        // TO DO: when we have multi-nested collections sum the content agregate for the sub-collections contents too
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
