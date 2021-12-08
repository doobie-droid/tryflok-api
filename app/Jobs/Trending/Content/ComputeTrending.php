<?php

namespace App\Jobs\Trending\Content;

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
    public $content;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contentTrendingCategory = $this->content->categories()->where('name', 'trending')->first();
        if (is_null($contentTrendingCategory)) {
            $trendingCategory = Category::where('name', 'trending')->first();
            $this->content->categories()->syncWithoutDetaching([
                $trendingCategory->id => [
                    'id' => Str::uuid(),
                ],
            ]);
        }

        $days_ago = now()->subDays(7);
        // get subscribers
        $total_subscribers = $this->content->subscribers()->count();
        $normalized_subscribers = bcmul($total_subscribers, Constants::TRENDING_SUBSCRIBERS_WEIGHT, 3);
        // get views in last 7 days
        $total_views = $this->content->views()->whereDate('created_at', '>=', $days_ago)->count();
        $normalized_views = bcmul($total_views, Constants::TRENDING_VIEWS_WEIGHT, 3);
        // get reviews in last 7 days
        $total_review_ratings = $this->content->reviews()->whereDate('created_at', '>=', $days_ago)->sum('rating');
        $normalized_reviews = bcmul($total_review_ratings, Constants::TRENDING_REVIEWS_WEIGHT, 3);
        // get revenues from last 7 days
        $total_purcahses = $this->content->revenues()->where('revenue_from', 'sale')->whereDate('created_at', '>=', $days_ago)->count();
        $normalized_purcahses = $total_purcahses * Constants::TRENDING_PURCHASES_WEIGHT;

        $trending_points = (int) bcadd(
            bcadd(
                bcadd(
                    $normalized_views, 
                    $normalized_reviews, 
                    3
                ), 
                $normalized_subscribers, 
                3
            ), 
            $normalized_purcahses, 
            0
        );

        $this->content->trending_points = $trending_points;
        $this->content->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
