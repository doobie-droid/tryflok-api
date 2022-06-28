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