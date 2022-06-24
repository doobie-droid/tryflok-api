<?php

namespace Tests\Feature\Console\Commands\Trending;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ComputeContentTrendingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_compute_content_trending_works()
    {
        $content1_views = 100;
        $content1_reviews = 5;
        $content1_revnues = 10;
        $content1 = Models\Content::factory()
        ->has(Models\View::factory()->count($content1_views))
        ->has(Models\Revenue::factory()->count($content1_revnues))
        ->has(Models\Review::factory()->count($content1_reviews))
        ->create();
        $content1_trending_points = (int) (Constants\Constants::TRENDING_VIEWS_WEIGHT * $content1_views) + (Constants\Constants::TRENDING_REVIEWS_WEIGHT * $content1_reviews * 5) + (Constants\Constants::TRENDING_PURCHASES_WEIGHT * $content1_revnues);

        $content2_views = 83;
        $content2_reviews = 2;
        $content2_revnues = 3;
        $content2 = Models\Content::factory()
        ->has(Models\View::factory()->count($content2_views))
        ->has(Models\Revenue::factory()->count($content2_revnues))
        ->has(Models\Review::factory()->count($content2_reviews))
        ->create();
        $content2_trending_points = (int) (Constants\Constants::TRENDING_VIEWS_WEIGHT * $content2_views) + (Constants\Constants::TRENDING_REVIEWS_WEIGHT * $content2_reviews * 5) + (Constants\Constants::TRENDING_PURCHASES_WEIGHT * $content2_revnues);

        $content3_views = 100;
        $content3_reviews = 5;
        $content3_revnues = 10;
        $content3 = Models\Content::factory()
        ->has(Models\View::factory()->createdDaysAgo(8)->count($content3_views))
        ->has(Models\Revenue::factory()->createdDaysAgo(8)->count($content3_revnues))
        ->has(Models\Review::factory()->createdDaysAgo(8)->count($content3_reviews))
        ->create();

        $this->artisan('flok:compute-content-trending')->assertSuccessful();

        $this->assertDatabaseHas('contents', [
            'id' => $content1->id,
            'trending_points' => $content1_trending_points,
        ]);

        $this->assertDatabaseHas('contents', [
            'id' => $content2->id,
            'trending_points' => $content2_trending_points,
        ]);

        $this->assertDatabaseHas('contents', [
            'id' => $content3->id,
            'trending_points' => 0,
        ]);
    }
}
