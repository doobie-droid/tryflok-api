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

        $total_contents = $this->collection->contents()->count();
        $normalized_contents = bcmul($total_contents, Constants::TRENDING_COLLECTION_CONTENT_WEIGHT, 3);

        $total_subscriptions = $this->collection->subscriptions()->count();
        $normalized_subscriptions = bcmul($total_subscriptions, Constants::TRENDING_COLLECTION_SUBSCRIBERS_WEIGHT, 3);

        $contents_trending_aggregate = $this->collection->contents()->sum('trending_points');
        $normalized_contents_trending_aggregate = bcmul($contents_trending_aggregate, Constants::TRENDING_COLLECTION_WEIGHT, 0);

        $this->collection->trending_points = bcadd(
            bcadd(
                $normalized_contents,
                $normalized_subscriptions,
                3
            ),
            $normalized_contents_trending_aggregate,
            0
        );
        $this->collection->save();

        // TO DO: when we have multi-nested collections sum the content agregate for the sub-collections contents too
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
