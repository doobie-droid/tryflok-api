<?php

namespace Tests\Feature\Digiverse;

use App\Constants\Roles;
use App\Models\Asset;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockData\Digiverse as DigiverseMock;
use Tests\TestCase;

class CreateAndUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_digiverse_works_with_correct_data()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $coverAsset = Asset::factory()->create();

        $request = DigiverseMock::UNSEEDED_DIGIVERSE;
        $request['cover']['asset_id'] = $coverAsset->id;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(DigiverseMock::STANDARD_DIGIVERSE_RESPONSE);

        $this->assertDatabaseHas('collections', [
            'title' => DigiverseMock::UNSEEDED_DIGIVERSE['title'],
            'description' => DigiverseMock::UNSEEDED_DIGIVERSE['description'],
            'user_id' => $user->id,
            'type' => 'digiverse',
            'is_available' => 0,
            'approved_by_admin' => 0,
            'show_only_in_collections' => 0,
            'views' => 0,
        ]);

        $digiverse = Collection::where('title', DigiverseMock::UNSEEDED_DIGIVERSE['title'])->first();
        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => DigiverseMock::UNSEEDED_DIGIVERSE['tags'][0],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
        $this->assertTrue($digiverse->tags()->where('tags.id', DigiverseMock::UNSEEDED_DIGIVERSE['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => DigiverseMock::UNSEEDED_DIGIVERSE['tags'][1],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
        $this->assertTrue($digiverse->tags()->where('tags.id', DigiverseMock::UNSEEDED_DIGIVERSE['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $digiverse->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($digiverse->cover()->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'collection',
            'priceable_id' => $digiverse->id,
            'amount' => DigiverseMock::UNSEEDED_DIGIVERSE['price']['amount'],
            'interval' => DigiverseMock::UNSEEDED_DIGIVERSE['price']['interval'],
            'interval_amount' => DigiverseMock::UNSEEDED_DIGIVERSE['price']['interval_amount'],
            'currency' => 'USD',
        ]);
        $this->assertTrue($digiverse->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'collection',
            'benefactable_id' => $digiverse->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($digiverse->benefactors()->count() === 1);
    }

    public function test_create_digiverse_does_not_work_without_correct_data()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $testData = DigiverseMock::UNSEEDED_DIGIVERSE;
        $cover = Asset::factory()->video()->create();
        $testData['cover']['asset_id'] = $cover->id;
        $response = $this->json('POST', '/api/v1/digiverses',  $testData);
        $response->assertStatus(400);
    }

    public function test_update_digiverse_with_correct_data_works()
    {
        $user = User::factory()->create();
        $this->be($user);

        $oldCoverAsset = Asset::factory()->create();

        $request = DigiverseMock::UNSEEDED_DIGIVERSE;
        $request['cover']['asset_id'] = $oldCoverAsset->id;
        $response = $this->json('POST', '/api/v1/digiverses', $request);
        $response->assertStatus(200);

        $digiverse = Collection::where('title', DigiverseMock::UNSEEDED_DIGIVERSE['title'])->first();

        $newCoverAsset = Asset::factory()->create();
        $request['cover']['asset_id'] = $newCoverAsset->id;
        $request['title'] = 'The first Digiverse Updated';
        $request['description'] = 'Testing digiverse update';
        $request['price'] = [
            'id' => $digiverse->prices()->first()->id,
            'amount' => 0,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ];
        $request['tags'] = [
            [
                'action' => 'add',
                'id' => '2186c1d6-fea2-4746-ac46-0e4f445f7c9e',
            ],
            [
                'action' => 'remove',
                'id' => '120566de-0361-4d66-b458-321d4ede62a9',
            ],
        ];
        $request['is_available'] = 1;
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)->assertJsonStructure(DigiverseMock::STANDARD_DIGIVERSE_RESPONSE);
        $this->assertDatabaseHas('collections', [
            'id' => $digiverse->id,
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'digiverse',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_collections' => 0,
            'views' => 0,
        ]);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => DigiverseMock::UNSEEDED_DIGIVERSE['tags'][0],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $request['tags'][1]['id'],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0]['id'],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);

        // validate cover was changed
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $digiverse->id,
            'asset_id' => $newCoverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertDatabaseMissing('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $digiverse->id,
            'asset_id' => $oldCoverAsset->id,
        ]);
        $this->assertDatabaseMissing('assets', [
            'id' => $oldCoverAsset->id,
        ]);

        //validate price changed
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'collection',
            'priceable_id' => $digiverse->id,
            'amount' => $request['price']['amount'],
            'interval' => $request['price']['interval'],
            'interval_amount' => $request['price']['interval_amount'],
            'currency' => 'USD',
        ]);
    }
}
