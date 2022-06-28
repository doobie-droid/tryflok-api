<?php 

use App\Constants;
use App\Models;
use Tests\MockData;


test('retrieve all digiverses fails with invalid parameters', function()
{
    $response = $this->json('GET', '/api/v1/digiverses?page=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/digiverses?page=-10');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/digiverses?limit=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/digiverses?limit=-30');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/digiverses?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/digiverses?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/digiverses?tags=fdfr3-3434f-434,dfdrg-2323-frf');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/digiverses?creators=fdfr3-3434f-434,dfdrg-2323-frf');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'creators.0'
            ]
        ]);
});

test('unavailable digiverses do not get returned', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->unavailable()
                        ->setTags([$tag])
                        ->count(4)
                        ->create();

        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200);
        $this->assertTrue(empty($response->getData()->data->digiverses));
});

test('empty digiverses do not get returned', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->count(4)
                        ->create();

        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200);
        $this->assertTrue(empty($response->getData()->data->digiverses));
});

test('retrieve digiverses work when user is not signed in', function()
{
    $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
});

test('retrieve digiverses work when user is signed in', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);
        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
});

test('retrieve digiverses work when user is signed in and has paid', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);
        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverses = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();
        foreach ($digiverses as $digiverse) {
            Models\Userable::create([
                'user_id' => $user->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $digiverse->id,
            ]);
        }
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $response = $this->json('GET', '/api/v1/digiverses');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
});

test('filter by tags works', function()
{
    $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse1 = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag1])
                        ->setContents([$content])
                        ->create();
        $digiverse2 = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag2])
                        ->setContents([$content])
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];

        $response = $this->json('GET', '/api/v1/digiverses?tags=' . $tag1->id);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 1);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse1, 'id');

        $response = $this->json('GET', '/api/v1/digiverses?tags=' . $tag2->id);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 1);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse2, 'id');
});

test('filter by keyword works', function()
{
    $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse1 = Models\Collection::factory()
                        ->digiverse()
                        ->state([
                            'title' => 'dsds ddtitle1dsd sds',
                        ])
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse2 = Models\Collection::factory()
                        ->digiverse()
                        ->state([
                            'title' => 'dsds sdtitle2sd sd',
                        ])
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse3 = Models\Collection::factory()
                        ->digiverse()
                        ->state([
                            'description' => 'dsds sdtitle3sd sd',
                        ])
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];

        $response = $this->json('GET', '/api/v1/digiverses?keyword=title1 title3');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 2);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse1, 'id');
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse3, 'id');

        $response = $this->json('GET', '/api/v1/digiverses?keyword=title2 title3');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 2);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse2, 'id');
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse3, 'id');
});

test('filter by creators works', function()
{
    $user1 = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $user3 = Models\User::factory()->create();

        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse1 = Models\Collection::factory()
                        ->digiverse()
                        ->for($user1, 'owner')
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse2 = Models\Collection::factory()
                        ->digiverse()
                        ->for($user2, 'owner')
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse3 = Models\Collection::factory()
                        ->digiverse()
                        ->for($user3, 'owner')
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];

        $response = $this->json('GET', "/api/v1/digiverses?creators={$user1->id},{$user3->id}");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 2);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse1, 'id');
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse3, 'id');

        $response = $this->json('GET', "/api/v1/digiverses?creators={$user2->id},{$user3->id}");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 2);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse2, 'id');
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse3, 'id');
});

test('pagination works', function()
{
    $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse1 = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse2 = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse3 = Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();

        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];
        $response = $this->json('GET', '/api/v1/digiverses?page=1&limit=2');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertTrue(count($digiverses) === 2);
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse1, 'id');
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse2, 'id');

        $response = $this->json('GET', '/api/v1/digiverses?page=2&limit=2');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'digiverses' => [
                    $expected_response_structure['data']['digiverse'],
                ],
            ],
        ]);
        $digiverses = $response->getData()->data->digiverses;
        $this->assertTrue(count($digiverses) === 1);
        $this->assertArrayHasObjectWithElementValue($digiverses, $digiverse3, 'id');

});