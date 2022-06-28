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

test('retrieve all digiverse contents fails with invalid parameters', function()
{
        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?page=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?page=-10");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?limit=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?limit=-30");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?tags=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$this->digiverse->id}/contents?creators=fdfr3-3434f-434,dfdrg-2323-frf");
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
        $user = Models\User::factory()->create();
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
});

test('unavailable contents get returned if user is owner', function()
{
            $digiverse = Models\Collection::factory()
                ->digiverse()
                ->for($this->user, 'owner')
                ->create();
            Models\Content::factory()
                ->unavailable()
                ->for($this->user, 'owner')
                ->setDigiverse($digiverse)
                ->setTags([Models\Tag::factory()->create()])
                ->count(4)
                ->create();
            $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Content::generateGetDigiverseContentsResponse());
            $contents = $response->getData()->data->contents;
            $this->assertTrue(count($contents) === 4);
    });

    test('pagination works', function()
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
});

test('filter by tags work', function()
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
});

test('filter by keywords work', function()
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
});

test('filter by creators works', function()
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
});