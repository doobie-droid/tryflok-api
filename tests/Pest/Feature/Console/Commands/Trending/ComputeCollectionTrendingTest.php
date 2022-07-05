<?php 

use App\Constants;
use App\Models;

test('compute collection trending works', function()
{
    $digiverse = Models\Collection::factory()
        ->digiverse()
        ->create();


        $content1_views = 100;
        $content1_reviews = 5;
        $content1_revnues = 10;
        $content1 = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->has(Models\View::factory()->count($content1_views))
        ->has(Models\Revenue::factory()->count($content1_revnues))
        ->has(Models\Review::factory()->count($content1_reviews))
        ->create();
        $content1_trending_points = (int) (Constants\Constants::TRENDING_VIEWS_WEIGHT * $content1_views) + (Constants\Constants::TRENDING_REVIEWS_WEIGHT * $content1_reviews * 5) + (Constants\Constants::TRENDING_PURCHASES_WEIGHT * $content1_revnues);

        $content2_views = 83;
        $content2_reviews = 2;
        $content2_revnues = 3;
        $content2 = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->has(Models\View::factory()->count($content2_views))
        ->has(Models\Revenue::factory()->count($content2_revnues))
        ->has(Models\Review::factory()->count($content2_reviews))
        ->create();
        $content2_trending_points = (int) (Constants\Constants::TRENDING_VIEWS_WEIGHT * $content2_views) + (Constants\Constants::TRENDING_REVIEWS_WEIGHT * $content2_reviews * 5) + (Constants\Constants::TRENDING_PURCHASES_WEIGHT * $content2_revnues);

        $this->artisan('flok:compute-content-trending')->assertSuccessful();

        $this->artisan('flok:compute-collection-trending')->assertSuccessful();

        $collection_trending_points = bcmul($content1_trending_points + $content2_trending_points, Constants\Constants::TRENDING_COLLECTION_WEIGHT, 0);
        
        $this->assertDatabaseHas('collections', [
            'id' => $digiverse->id,
            'trending_points' => $collection_trending_points,
        ]);
});