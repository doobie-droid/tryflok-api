<?php 

use App\Constants;
use App\Models;
use Tests\MockData;

it('fails when user not signed in', function()
{
    $response = $this->json('GET', '/api/v1/account/digiverses');
    $response->assertStatus(401);
});

it('fails with invalid parameters', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $response = $this->json('GET', '/api/v1/account/digiverses?page=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/account/digiverses?page=-10');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/account/digiverses?limit=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/account/digiverses?limit=-30');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/account/digiverses?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/account/digiverses?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/account/digiverses?tags=fdfr3-3434f-434,dfdrg-2323-frf');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);
});

test('unavailable digiverses get returned', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->setTags([$tag])
                        ->count(4)
                        ->create();

        $response = $this->json('GET', '/api/v1/account/digiverses');
        $response->assertStatus(200);
        $this->assertFalse(empty($response->getData()->data->digiverses));
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
});

test('empty digiverses get returned', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->setTags([$tag])
                        ->count(4)
                        ->create();

        $response = $this->json('GET', '/api/v1/account/digiverses');
        $response->assertStatus(200);
        $this->assertFalse(empty($response->getData()->data->digiverses));
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);
});

test('filter by tags works', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse1 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->setTags([$tag1])
                        ->setContents([$content])
                        ->create();
        $digiverse2 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->setTags([$tag2])
                        ->setContents([$content])
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];

        $response = $this->json('GET', '/api/v1/account/digiverses?tags=' . $tag1->id);
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

        $response = $this->json('GET', '/api/v1/account/digiverses?tags=' . $tag2->id);
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
    $user = Models\User::factory()->create();
        $this->be($user);

        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse1 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->state([
                            'title' => 'dsds ddtitle1dsd sds',
                        ])
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse2 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->state([
                            'title' => 'dsds sdtitle2sd sd',
                        ])
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $digiverse3 = Models\Collection::factory()
                        ->for($user, 'owner')
                        ->digiverse()
                        ->unavailable()
                        ->state([
                            'description' => 'dsds sdtitle3sd sd',
                        ])
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->create();
        $expected_response_structure = MockData\Digiverse::generateGetAllResponse();
        $expected_response_structure['data']['digiverse']['userables'] = [];

        $response = $this->json('GET', '/api/v1/account/digiverses?keyword=title1 title3');
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

        $response = $this->json('GET', '/api/v1/account/digiverses?keyword=title2 title3');
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