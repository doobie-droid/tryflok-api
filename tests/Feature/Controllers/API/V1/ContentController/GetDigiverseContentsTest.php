<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class GetDigiverseContentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_retrieve_all_digiverse_contents_fails_with_invalid_parameters()
    {
        $digiverse = Models\Collection::factory()->digiverse()->create();
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
        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
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

    public function test_unavailable_contents_do_not_get_returned_if_user_is_not_owner()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()->digiverse()->create();
        Models\Content::factory()
        ->unavailable()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->count(4)
        ->create();
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10");
        $response->assertStatus(200);
        $contents = $response->getData()->data->contents;
        $this->assertEquals($contents, []);
    }

    public function test_unavailable_contents_get_returned_if_user_is_owner()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->for($user, 'owner')
            ->create();
        Models\Content::factory()
            ->unavailable()
            ->for($user, 'owner')
            ->setDigiverse($digiverse)
            ->setTags([Models\Tag::factory()->create()])
            ->count(4)
            ->create();
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertTrue(count($contents) === 4);
    }

    public function test_pagination_works()
    {
        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->create();
        $content1 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->setTags([Models\Tag::factory()->create()])
            ->create();

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=2");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertTrue(count($contents) === 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=2&limit=2");
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

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->create();
        $content1 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->setTags([$tag1])
            ->create();
        $content2 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->setTags([$tag2])
            ->create();

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?tags={$tag1->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $this->assertTrue(count($response->getData()->data->contents) === 1);
        $contents = $response->getData()->data->contents;
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?tags={$tag2->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $this->assertTrue(count($response->getData()->data->contents) === 1);
        $contents = $response->getData()->data->contents;
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');
    }

    public function test_filter_by_keywords_work()
    {
        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->create();
        $content1 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->state([
                'title' => 'dsds ddtitle1dsd sds',
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->state([
                'title' => 'dsds ddtitle2dsd sds',
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->state([
                'description' => 'dsds ddtitle3dsd sds',
            ])
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?keyword=title1 title3");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?keyword=title2 title3");
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

        $digiverse = Models\Collection::factory()
            ->digiverse()
            ->create();
        $content1 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->for($user1, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content2 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->for($user2, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        $content3 = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->for($user3, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->create();
        
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?creators={$user1->id},{$user3->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content1, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?creators={$user2->id},{$user3->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
        $contents = $response->getData()->data->contents;
        $this->assertEquals(count($contents), 2);
        $this->assertArrayHasObjectWithElementValue($contents, $content2, 'id');
        $this->assertArrayHasObjectWithElementValue($contents, $content3, 'id');
    }
}
