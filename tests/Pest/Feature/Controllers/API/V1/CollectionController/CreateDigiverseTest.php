<?php

namespace Tests\Feature\Controllers\API\V1\CollectionController;

use App\Constants\Roles;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockData;
use Tests\TestCase;

class CreateDigiverseTest extends TestCase
{
    use DatabaseTransactions;
   
    public function test_create_digiverse_fails_when_user_is_not_signed_in()
    {
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(401);
    }

    public function test_create_digiverse_fails_with_invalid_request()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        
        // when no title is passed
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['title'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when no description is passed
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['description'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price is empty
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price amount is missing
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price']['amount'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price amount is negative
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price']['amount'] = -1;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price interval is missing
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price']['interval'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price interval is invalid
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price']['interval'] = 'yarly';
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price interval amount is missing
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price']['interval_amount'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when price interval amount is invalid
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['price']['interval_amount'] = 10;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when tags are invalid
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['tags'][0] = 'sdsds';
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when cover is missing
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['cover'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when cover asset id is missing
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['cover']['asset_id'] = null;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);

        // when cover asset id is invalid
        $request = MockData\Digiverse::generateStandardCreateRequest();
        $request['cover']['asset_id'] = 'assdsds';
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(400);
    }

    public function test_create_digiverse_works_with_valid_request()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $request = MockData\Digiverse::generateStandardCreateRequest();
        $expected_response_structure = MockData\Digiverse::generateDigiverseCreatedResponse();
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);

        $this->assertDatabaseHas('collections', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'digiverse',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_collections' => 0,
        ]);

        $digiverse = Models\Collection::where('title', $request['title'])->first();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
        $this->assertTrue($digiverse->tags()->where('tags.id', $request['tags'][0])->count() === 1);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $digiverse->id,
            'asset_id' => $request['cover']['asset_id'],
            'purpose' => 'cover',
        ]);
        $this->assertTrue($digiverse->cover()->count() === 1);

        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'collection',
            'priceable_id' => $digiverse->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($digiverse->prices()->count() === 1);

        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'collection',
            'benefactable_id' => $digiverse->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($digiverse->benefactors()->count() === 1);
    }
}
