<?php 

use App\Constants;
use App\Models;
use Tests\MockData;

beforeEach(function()
{
    $this->digiverse = Models\Collection::factory()->digiverse()->create();
     
    $this->user = Models\User::factory()->create();
    $this->be($this->user);
});

test('retrieve trending fails with invalid parameters', function()
{
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
});
test('unavailable contents do not get returned if user is not owner', function()
{
    Models\Content::factory()
        ->unavailable()
        ->setTags([Models\Tag::factory()->create()])
        ->count(4)
        ->create();
        $response = $this->json('GET', "/api/v1/contents/trending?page=1&limit=10");
        $response->assertStatus(200);
        $contents = $response->getData()->data->contents;
        $this->assertEquals($contents, []);
});

test('unavailable contents does not get returned even if user is owner', function()
{
    Models\Content::factory()
            ->unavailable()
            ->for($this->user, 'owner')
            ->setTags([Models\Tag::factory()->create()])
            ->count(4)
            ->create();
        $response = $this->json('GET', "/api/v1/contents/trending?page=1&limit=10");
        $response->assertStatus(200);
        $contents = $response->getData()->data->contents;
        $this->assertEquals($contents, []);
});

test('pagination works', function()
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
});

test('filter by tags work', function()
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
});

test('filter by keywords work', function()
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
});

test('filter by creators works', function()
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
});

test('order by trending works', function()
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
});
test('contents whose lives ended more than two hours ago do not get returned', function()
{
        Models\Content::factory()
        ->for($this->user, 'owner')
        ->setTags([Models\Tag::factory()->create()])
        ->count(4)
        ->create([
            'live_ended_at' => now()->subHours(3),
        ]);
    $response = $this->json('GET', "/api/v1/contents/trending?page=1&limit=10");
    $response->assertStatus(200);
    $contents = $response->getData()->data->contents;
    $this->assertEquals($contents, []); 
});