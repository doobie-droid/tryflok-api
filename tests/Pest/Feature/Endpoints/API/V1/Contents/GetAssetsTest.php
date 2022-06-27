<?php 

use App\Models;
use Tests\MockData;

beforeEach(function()
{
        $this->digiverse = Models\Collection::factory()->digiverse()->create();
        $this->content = Models\Content::factory()
        ->setDigiverse($this->digiverse)
        ->setPriceAmount(100)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        $this->user = Models\User::factory()->create();
        $this->be($this->user);
});

test('asset gets returned for free content', function()
{       
        $digiverse = Models\Collection::factory()->digiverse()->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create();
        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
});

test('asset is not returned for paid content when user has not paid', function()
{
        $response = $this->json('GET', "/api/v1/contents/{$this->content->id}/assets");
        $response->assertStatus(400);
        $this->assertEquals($response->getData()->message, 'You are not permitted to view the assets of this content');
}); 

test('asset is not returned for free content with paid ancestor when user has not paid', function()
{
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->setPriceAmount(100)
        ->create();

        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(400);
        $this->assertEquals($response->getData()->message, 'You are not permitted to view the assets of this content');

});

test('asset is returned for paid content when user has paid', function()
{
        Models\Userable::create([
            'user_id' => $this->user->id,
            'status' => 'available',
            'userable_type' => 'content',
            'userable_id' => $this->content->id,
        ]);
        $response = $this->json('GET', "/api/v1/contents/{$this->content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
});

test('asset is returned for free content with paid ancestor when user has paid via ancestor', function()
{
            $digiverse = Models\Collection::factory()
            ->digiverse()
            ->setPriceAmount(100)
            ->create();
            $content = Models\Content::factory()
            ->setDigiverse($digiverse)
            ->setTags([Models\Tag::factory()->create()])
            ->create();

        Models\Userable::create([
            'user_id' => $this->user->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
});

test('asset is returned for paid content with paid ancestor when user has paid via ancestor', function()
{
            $digiverse = Models\Collection::factory()
            ->digiverse()
            ->setPriceAmount(100)
            ->create();
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setPriceAmount(100)
        ->setTags([Models\Tag::factory()->create()])
        ->create();

        Models\Userable::create([
            'user_id' => $this->user->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $digiverse->id,
        ]);

        $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
});