<?php


use App\Constants\Roles;
use App\Models;
use Tests\MockData;

test('update fails when user is not signed in', function()
{
        $request = MockData\Digiverse::generateStandardUpdateRequest();
        $tag = Models\Tag::factory()->create();
        $request['tags'][] = [
            'action' => 'remove',
            'id' => $tag->id,
        ];
        $digiverse = Models\Collection::factory()
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();

        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(401);
});

test('update fails with invalid request', function()
{
    $user = Models\User::factory()->create();
    $user->assignRole(Roles::USER);
    $this->be($user);
    $tag = Models\Tag::factory()->create();

    $digiverse = Models\Collection::factory()
                    ->for($user, 'owner')
                    ->digiverse()
                    ->setPriceAmount(10)
                    ->setTags([$tag])
                    ->create();

    // when cover asset id is invalid
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['cover']['asset_id'] = 'assdsds';
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);

    // when price id is invalid
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['price']['id'] = 'assdsds';
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);

    // when price amount is negative
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['price']['amount'] = -1;
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);

    // when price interval is invalid
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['price']['interval'] = 'yarly';
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);

    // when price interval amount is invalid
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['price']['interval_amount'] = 10;
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);

    // when tag action is invalid
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['tags'][] = [
        'action' => 'removert',
        'id' => $tag->id,
    ];
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);

    // when tag id is invalid
    $request = MockData\Digiverse::generateStandardUpdateRequest();
    $request['tags'][] = [
        'action' => 'removert',
        'id' => 'sdsds',
    ];
    $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
    $response->assertStatus(400);
});

test('update title works', function()
{
    $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();

        $request = [
            'title' => 'an updated title' . date('Ymd-His'),
        ];
        $expected_response_structure = MockData\Digiverse::generateDigiverseUpdatedResponse();
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $this->assertDatabaseHas('collections', [
            'id' => $digiverse->id,
            'title' => $request['title'],
        ]);
});

test('update description works', function()
{
    $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();

        $request = [
            'description' => 'an updated description' . date('Ymd-His'),
        ];
        $expected_response_structure = MockData\Digiverse::generateDigiverseUpdatedResponse();
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $this->assertDatabaseHas('collections', [
            'id' => $digiverse->id,
            'description' => $request['description'],
        ]);
});

test('update is available works', function()
{
    $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();

        $request = [
            'is_available' => 0,
        ];
        $expected_response_structure = MockData\Digiverse::generateDigiverseUpdatedResponse();
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $this->assertDatabaseHas('collections', [
            'id' => $digiverse->id,
            'is_available' => $request['is_available'],
        ]);
});

test('update cover works', function()
{
    $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();
        $oldCover = $digiverse->cover()->first();
        $newCover = Models\Asset::factory()->create();
        $request = [
            'cover' => [
                'asset_id' => $newCover->id,
            ],
        ];
        $expected_response_structure = MockData\Digiverse::generateDigiverseUpdatedResponse();
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $digiverse->id,
            'asset_id' => $newCover->id,
            'purpose' => 'cover',
        ]);
        $this->assertDatabaseMissing('assetables', [
            'assetable_type' => 'collection',
            'assetable_id' => $digiverse->id,
            'asset_id' => $oldCover->id,
        ]);
        $this->assertDatabaseMissing('assets', [
            'id' => $oldCover->id,
        ]);
});

test('update price works', function()
{
    $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();
        $price = $digiverse->prices()->first();

        $request = [
            'price' => [
                'id' => $price->id,
                'amount' => 100,
            ],
        ];

        $expected_response_structure = MockData\Digiverse::generateDigiverseUpdatedResponse();
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'collection',
            'priceable_id' => $digiverse->id,
            'amount' => $request['price']['amount'],
            'currency' => 'USD',
        ]);
});

test('update tags works', function()
{
    $user = Models\User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $tag = Models\Tag::factory()->create();

        $digiverse = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->setPriceAmount(10)
                        ->setTags([$tag])
                        ->create();

        $request['tags'] = [
            [
                'action' => 'remove',
                'id' => $tag->id,
            ],
            [
                'action' => 'add',
                'id' => Models\Tag::factory()->create()->id,
            ],
        ];

        $expected_response_structure = MockData\Digiverse::generateDigiverseUpdatedResponse();
        $response = $this->json('PATCH', "/api/v1/digiverses/{$digiverse->id}", $request);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);
        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $request['tags'][0]['id'],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1]['id'],
            'taggable_type' => 'collection',
            'taggable_id' => $digiverse->id,
        ]);
});