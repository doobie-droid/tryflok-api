<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\MockData\User as UserMock;
use Tests\MockData\Digiverse as DigiverseMock;
use App\Models\User;
use App\Models\Collection;
use App\Models\Benefactor;
use App\Models\Tag;

class DigiverseTest extends TestCase
{
    use DatabaseTransactions;
    
    public function testCreateDigiverseWorksWithCorrectData()
    {
        $user = User::where('username', UserMock::SEEDED_USER['username'])->first();
        $this->be($user);
        $response = $this->json('POST', '/api/v1/digiverses', DigiverseMock::UNSEEDED_DIGIVERSE);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'digiverse' => [
                    'id',
                    'title',
                    'description',
                    'owner' => [
                        'id',
                        'name',
                        'email',
                        'username',
                    ],
                    'type',
                    'is_available',
                    'approved_by_admin',
                    'show_only_in_collections',
                    'views',
                    'cover' => [
                        'url',
                        'asset_type',
                        'encryption_key',
                    ],
                    'prices' => [
                        [
                            'id',
                            'amount',
                            'currency',
                            'interval',
                            'interval_amount'
                        ]
                    ],
                    'tags' => [
                        [
                            'id',
                            'type',
                            'name',
                        ]
                    ],
                ]
            ]
        ]);

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
            'asset_id' => DigiverseMock::UNSEEDED_DIGIVERSE['cover']['asset_id'],
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

    public function testCreateDigiverseDoesNotWorksWithoutCorrectData()
    {
        $user = User::where('username', UserMock::SEEDED_USER['username'])->first();
        $this->be($user);
        $testData = DigiverseMock::UNSEEDED_DIGIVERSE;
        $testData['cover']['asset_id'] = '263ec55f-2bfc-4259-a66d-08ceed037f74';
        $response = $this->json('POST', '/api/v1/digiverses',  $testData);
        $response->assertStatus(400);
    }
}
