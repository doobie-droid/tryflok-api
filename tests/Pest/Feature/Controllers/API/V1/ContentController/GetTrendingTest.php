<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class GetTrendingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retrieve_trending_fails_with_invalid_parameters()
    {
        $digiverse = Models\Collection::factory()->digiverse()->create();
        $response = $this->json('GET', "/api/v1/contents/trending?page=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/contents/trending?page=-10");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/contents/trending?limit=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/contents/trending?limit=-30");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/contents/trending?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/contents/trending?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/contents/trending?tags=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/contents/trending?creators=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'creators.0'
            ]
        ]);
    }

    public function test_unavailable_contents_do_not_get_returned_if_user_is_not_owner()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        Models\Content::factory()
        ->unavailable()
        ->setTags([Models\Tag::factory()->create()])
        ->count(4)
        ->create();
        $response = $this->json('GET', "/api/v1/contents/trending?page=1&limit=10");
        $response->assertStatus(200);
        $contents = $response->getData()->data->contents;
        $this->assertEquals($contents, []);
    }

    public function test_unavailable_contents_does_not_get_returned_event_if_user_is_owner()
    {
        $user = Models\User::factory()->create();
        $this->be($user);

        Models\Content::factory()
            ->unavailable()
            ->for($user, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->count(4)
            ->create();
        $response = $this->json('GET', "/api/v1/contents/trending?page=1&limit=10");
        $response->assertStatus(200);
        $contents = $response->getData()->data->contents;
        $this->assertEquals($contents, []);
    }

    public function test_pagination_works()
    {
        $content1 = Models\Content::factory()
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->setTags([Models\Tag::factory()->create()])
            ->create();

        $response = $this->json('GET', "/api/v1/contents/trending?page=1&limit=2");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertTrue(count($contents) === 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');

        $response = $this->json('GET', "/api/v1/contents/trending?page=2&limit=2");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertTrue(count($contents) === 1);
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');
    }

    public function test_filter_by_tags_work()
    {
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();

        $content1 = Models\Content::factory()
            ->setTags([$tag1])
            ->create();
        $content2 = Models\Content::factory()
            ->setTags([$tag2])
            ->create();

        $response = $this->json('GET', "/api/v1/contents/trending?tags={$tag1->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $this->assertTrue(count($response->getData()->data->contents) === 1);
        $contents = $response->getData()->data->contents;
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');

        $response = $this->json('GET', "/api/v1/contents/trending?tags={$tag2->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $this->assertTrue(count($response->getData()->data->contents) === 1);
        $contents = $response->getData()->data->contents;
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');
    }

    public function test_filter_by_keywords_work()
    {
        $content1 = Models\Content::factory()
            ->state([
                'title' => 'dsds ddtitle1dsd sds',
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->state([
                'title' => 'dsds ddtitle2dsd sds',
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->state([
                'description' => 'dsds ddtitle3dsd sds',
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $response = $this->json('GET', "/api/v1/contents/trending?keyword=title1 title3");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');

        $response = $this->json('GET', "/api/v1/contents/trending?keyword=title2 title3");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');
    }

    public function test_filter_by_creators_works()
    {
        $user1 = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $user3 = Models\User::factory()->create();

        $tag = Models\Tag::factory()->create();

        $content1 = Models\Content::factory()
            ->for($user1, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->for($user2, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->for($user3, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        
        $response = $this->json('GET', "/api/v1/contents/trending?creators={$user1->id},{$user3->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');

        $response = $this->json('GET', "/api/v1/contents/trending?creators={$user2->id},{$user3->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');
    }

    public function test_order_by_trending_works()
    {
        $content1 = Models\Content::factory()
            ->state([
                'trending_points' => 50,
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->state([
                'trending_points' => 100,
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->state([
                'trending_points' => 10,
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content4 = Models\Content::factory()
            ->state([
                'trending_points' => 15,
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $response = $this->json('GET', "/api/v1/contents/trending");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals($contents[0]->id, $content2->id);
        $this->assertEquals($contents[1]->id, $content1->id);
        $this->assertEquals($contents[2]->id, $content4->id);
        $this->assertEquals($contents[3]->id, $content3->id);
    }
}
