<?php

use App\Models;
use Tests\MockData;

test('create collections  works', function()
{     
        $user = Models\User::factory()->create();
        $this->be($user);

        $request = MockData\Collection::generateStandardCreateRequest();
        $request['digiverse_id'] = Models\Collection::factory()
        ->for($user, 'owner')
        ->create()->id;
        $expected_response_structure = MockData\Collection::generateCollectionCreatedResponse();
        $response = $this->json('POST', '/api/v1/collections', $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);

        $this->assertDatabaseHas('collections', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'collection',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_collections' => 0,
        ]);

        $collection = Models\Collection::where('title', $request['title'])->first();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'collection',
            'taggable_id' => $collection->id,
        ]);
        $this->assertTrue($collection->tags()->where('tags.id', $request['tags'][0])->count() === 1);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $collection->id,
            'asset_id' => $request['cover']['asset_id'],
            'purpose' => 'cover',
        ]);
        $this->assertTrue($collection->cover()->count() === 1);

        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'collection',
            'priceable_id' => $collection->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($collection->prices()->count() === 1);

        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'collection',
            'benefactable_id' => $collection->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($collection->benefactors()->count() === 1);

});