<?php

namespace Tests\Feature\Content;

use App\Constants\Constants;
use App\Constants\Roles;
use App\Models\Asset;
use App\Models\Benefactor;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\Tag;
use App\Models\User;
use App\Models\View;
use App\Models\Revenue;
use App\Models\Review;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData\Content as ContentMock;
use Tests\TestCase;

class RetrieveTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_retrieve_single_content_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $content = Content::factory()->setTags([Tag::factory()->create()])->create();
        $response = $this->json('GET', "/api/v1/contents/{$content->id}");
        $response->assertStatus(200)->assertJsonStructure(ContentMock::CONTENT_WITH_NO_ASSET_RESPONSE);
    }

    public function test_retrieve_all_digiverse_contents_fails_with_invalid_parameters()
    {
        $digiverse = Collection::factory()->digiverse()->create();
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=-10");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?limit=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?limit=-30");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?tags=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?creators=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'creators.0'
            ]
        ]);
    }

    public function test_retrieve_all_digiverse_contents_works_with_correct_parameters()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $digiverse = Collection::factory()->digiverse()->create();

        $content1 = Content::factory()
        ->state([
            'title' => 'title1',
        ])
        ->for($user, 'owner')
        ->setDigiverse($digiverse)
        ->setTags([$tag1, $tag2])
        ->create();

        $content2 = Content::factory()
        ->state([
            'title' => 'title2',
        ])
        ->for($user, 'owner')
        ->setDigiverse($digiverse)
        ->setTags([$tag1, $tag3])
        ->create();

        $content3 = Content::factory()
        ->state([
            'title' => 'title3',
        ])
        ->for($user, 'owner')
        ->setDigiverse($digiverse)
        ->setTags([$tag2, $tag3])
        ->create();

        $content4 = Content::factory()
        ->state([
            'description' => 'title1',
        ])
        ->for($user, 'owner')
        ->setDigiverse($digiverse)
        ->setTags([$tag1, $tag2])
        ->create();

        $content5 = Content::factory()
        ->state([
            'description' => 'title2',
        ])
        ->for($user, 'owner')
        ->setDigiverse($digiverse)
        ->setTags([$tag1, $tag3])
        ->create();

        $content6 = Content::factory()
        ->state([
            'description' => 'title3',
        ])
        ->for($user, 'owner')
        ->setDigiverse($digiverse)
        ->setTags([$tag2, $tag3])
        ->create();

        // when no filtering is set
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'contents' => [
                    ContentMock::LIVE_CONTENT_RESPONSE['data']['content'],
                ],
                'current_page',
                'items_per_page',
                'total',
            ]
        ])
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content3->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ])
        ->assertJsonFragment([
            'id' => $content6->id
        ]);

        // when two tags are specified
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&tags={$tag1->id},{$tag2->id},{$tag3->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content3->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ])
        ->assertJsonFragment([
            'id' => $content6->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 6);

        // when a single tag is specified
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&tags={$tag1->id},");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 4);

        // when filtering is done by keyword
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&keyword=title1");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 2);

        // when filtering is done by creators
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&creators={$user->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content3->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ])
        ->assertJsonFragment([
            'id' => $content6->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 6);
    }

    public function test_retrieve_trending_works()
    {
        $user = User::factory()->create();
        $content3 = Content::factory()
        ->state([
            'created_at' => now()->subMinutes(3),
        ])
        ->create();

        $content4 = Content::factory()
        ->state([
            'created_at' => now(),
        ])
        ->has(View::factory()->count(100))
        ->create();


        $content1_views = 100;
        $content1_reviews = 5;
        $content1_revnues = 10;
        $content1 = Content::factory()
        ->state([
            'created_at' => now()->subMinutes(7),
        ])
        ->has(View::factory()->count($content1_views))
        ->has(Revenue::factory()->count($content1_revnues))
        ->has(Review::factory()->count($content1_reviews))
        ->create();
        $content1_trending_points = (int) (Constants::TRENDING_VIEWS_WEIGHT * $content1_views) + (Constants::TRENDING_REVIEWS_WEIGHT * $content1_reviews * 5) + (Constants::TRENDING_PURCHASES_WEIGHT * $content1_revnues);

        $content2_views = 83;
        $content2_reviews = 2;
        $content2_revnues = 3;
        $content2 = Content::factory()
        ->state([
            'created_at' => now()->subMinutes(5),
        ])
        ->has(View::factory()->count($content2_views))
        ->has(Revenue::factory()->count($content2_revnues))
        ->has(Review::factory()->count($content2_reviews))
        ->create();
        $content2_trending_points = (int) (Constants::TRENDING_VIEWS_WEIGHT * $content2_views) + (Constants::TRENDING_REVIEWS_WEIGHT * $content2_reviews * 5) + (Constants::TRENDING_PURCHASES_WEIGHT * $content2_revnues);

        $this->artisan('flok:compute-content-trending')->assertSuccessful();

        $response = $this->json('GET', '/api/v1/contents/trending?page=1&limit=10');
        $response->assertStatus(200);
        /*$contents = $response->getData()->data->contents;
        $this->assertEquals($contents[0]->id, $content1->id);
        $this->assertEquals($contents[1]->id, $content2->id);
        $this->assertEquals($contents[2]->id, $content4->id);
        $this->assertEquals($contents[3]->id, $content3->id);*/
    }
}
