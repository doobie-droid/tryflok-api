<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Collection;
use App\Models\Content;
use App\Models\View;
use App\Models\Revenue;
use App\Models\Review;
use App\Constants\Constants;
use Illuminate\Support\Str;

class ComputeCollectionTrendingTest extends TestCase
{

    use DatabaseTransactions;
    use WithFaker;

    public function test_compute_collection_trending_works()
    {
        $digiverse = Collection::factory()
        ->digiverse()
        ->create();

        $content1_views = 100;
        $content1_reviews = 5;
        $content1_revnues = 10;
        $content1 = Content::factory()
        ->hasAttached(
            $digiverse,
            [
                'id' => Str::uuid(),
            ]
        )
        ->has(View::factory()->count($content1_views))
        ->has(Revenue::factory()->count($content1_revnues))
        ->has(Review::factory()->count($content1_reviews))
        ->create();
        $content1_trending_points = (int) (Constants::TRENDING_VIEWS_WEIGHT * $content1_views) + (Constants::TRENDING_REVIEWS_WEIGHT * $content1_reviews * 5) + (Constants::TRENDING_PURCHASES_WEIGHT * $content1_revnues);

        $content2_views = 83;
        $content2_reviews = 2;
        $content2_revnues = 3;
        $content2 = Content::factory()
        ->hasAttached(
            $digiverse,
            [
                'id' => Str::uuid(),
            ]
        )
        ->has(View::factory()->count($content2_views))
        ->has(Revenue::factory()->count($content2_revnues))
        ->has(Review::factory()->count($content2_reviews))
        ->create();
        $content2_trending_points = (int) (Constants::TRENDING_VIEWS_WEIGHT * $content2_views) + (Constants::TRENDING_REVIEWS_WEIGHT * $content2_reviews * 5) + (Constants::TRENDING_PURCHASES_WEIGHT * $content2_revnues);

        $this->artisan('flok:compute-content-trending')->assertSuccessful();

        $this->artisan('flok:compute-collection-trending')->assertSuccessful();

        $collection_trending_points = bcmul($content1_trending_points + $content2_trending_points, Constants::TRENDING_COLLECTION_WEIGHT, 0);

        $this->assertDatabaseHas('collections', [
            'id' => $digiverse->id,
            'trending_points' => $collection_trending_points,
        ]);
    }
}
