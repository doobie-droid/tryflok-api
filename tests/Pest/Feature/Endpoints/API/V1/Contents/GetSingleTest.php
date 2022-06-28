<?php

use App\Models;
use Tests\MockData;

beforeEach(function()
{
    $this->user = Models\User::factory()->create();
    $this->be($this->user);

    $this->content = Models\Content::factory()->setTags([Models\Tag::factory()->create()])->create();
    $this->digiverse = $this->content->collections()->first();
});

test('retrieve single content works when user is not signed in', function()
{
        $response = $this->json('GET', "/api/v1/contents/{$this->content->id}");
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
        $userables = $response->getData()->data->content->userables;
        $this->assertEquals($userables, []);
        $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
        $this->assertEquals($access_through_ancestors, []);
});

test('retrieve single content works when user is signed in and has not paid for content', function()
{
    Models\Userable::create([
        'user_id' => $this->user->id,
        'status' => 'subscription-ended',
        'userable_type' => 'collection',
        'userable_id' => $this->digiverse->id,
    ]);
    Models\Userable::create([
        'user_id' => $this->user->id,
        'status' => 'unavailable',
        'userable_type' => 'content',
        'userable_id' => $this->content->id,
    ]);
    $response = $this->json('GET', "/api/v1/contents/{$this->content->id}");
    $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
    $userables = $response->getData()->data->content->userables;
    $this->assertEquals($userables, []);
    $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
    $this->assertEquals($access_through_ancestors, []);
});

test('retrieve single content works when user is signed in and has paid for content directly', function()
{
    Models\Userable::create([
        'user_id' => $this->user->id,
        'status' => 'subscription-ended',
        'userable_type' => 'collection',
        'userable_id' => $this->digiverse->id,
    ]);
    Models\Userable::create([
        'user_id' => $this->user->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $this->content->id,
    ]);
    $response = $this->json('GET', "/api/v1/contents/{$this->content->id}");
    $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
    $userables = $response->getData()->data->content->userables;
    $this->assertFalse(empty($userables));
    $this->assertTrue($userables[0]->user_id === $this->user->id);
    $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
    $this->assertEquals($access_through_ancestors, []);
});

test('retrieve single content works when user is signed in and has paid for content via ancestor', function()
{
    Models\Userable::create([
        'user_id' => $this->user->id,
        'status' => 'available',
        'userable_type' => 'collection',
        'userable_id' => $this->digiverse->id,
    ]);

    $response = $this->json('GET', "/api/v1/contents/{$this->content->id}");
    $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateGetSingleContentResponse());
    $userables = $response->getData()->data->content->userables;
    $this->assertEquals($userables, []);
    $access_through_ancestors = $response->getData()->data->content->access_through_ancestors;
    $this->assertFalse(empty($access_through_ancestors));
});