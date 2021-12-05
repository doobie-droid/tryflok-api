<?php

namespace Tests\Feature\Digiverse;

use App\Constants\Constants;
use App\Constants\Roles;
use App\Models\Asset;
use App\Models\Benefactor;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData\Digiverse as DigiverseMock;
use Tests\TestCase;

class RetrieveTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_retrieve_digiverse_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached(Tag::factory()->count(1), [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->hasAttached(
            Content::factory()->count(1),
            [
            'id' => Str::uuid(),
            ]
        )
        ->create();

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}");
        $response->assertStatus(200)->assertJsonStructure(DigiverseMock::STANDARD_DIGIVERSE_RESPONSE);
    }

    public function test_retrieve_all_digiverses_fails_with_invalid_parameters()
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
        $max_limit_exceed = Constants::MAX_ITEMS_LIMIT + 1;
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
    }

    public function test_retrieve_all_digiverses_works_with_correct_parameters()
    {
        $user1 = User::factory()->create();
        $user1->assignRole(Roles::USER);
        $this->be($user1);
        $user2 = User::factory()->create();
        $user2->assignRole(Roles::USER);
        $this->be($user2);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $digiverse1 = Collection::factory()
        ->state([
            'title' => 'title1',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();
        $digiverse2 = Collection::factory()
        ->state([
            'title' => 'title2',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();
        $digiverse3 = Collection::factory()
        ->state([
            'title' => 'title3',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();

        $digiverse4 = Collection::factory()
        ->state([
            'description' => 'title1',
        ])
        ->for($user2, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user2->id
            ])
        )
        ->create();
        $digiverse5 = Collection::factory()
        ->state([
            'description' => 'title2',
        ])
        ->for($user2, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user2->id
            ])
        )
        ->create();
        $digiverse6 = Collection::factory()
        ->state([
            'description' => 'title3',
        ])
        ->for($user2, 'owner')
        ->digiverse()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user2->id
            ])
        )
        ->create();

        // when no filtering is set
        $response = $this->json('GET', '/api/v1/digiverses?page=1&limit=10');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'digiverses' => [
                    DigiverseMock::STANDARD_DIGIVERSE_RESPONSE['data']['digiverse'],
                ],
                'current_page',
                'items_per_page',
                'total',
            ]
        ])
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse3->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse6->id
        ]);

        // when two tags are specified
        $response = $this->json('GET', "/api/v1/digiverses?page=1&limit=10&tags={$tag1->id},{$tag2->id},{$tag3->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse3->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse6->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 6);

        // when a single tag is specified
        $response = $this->json('GET', "/api/v1/digiverses?page=1&limit=10&tags={$tag1->id},");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);

        // when filtering is done by keyword
        $response = $this->json('GET', '/api/v1/digiverses?page=1&limit=10&keyword=title1');
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 2);

        // when filtering is done by creators
        $response = $this->json('GET', "/api/v1/digiverses?page=1&limit=10&creators={$user1->id},{$user2->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse3->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse6->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 6);

        $response = $this->json('GET', "/api/v1/digiverses?page=1&limit=10&creators={$user1->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse3->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 3);

        $response = $this->json('GET', "/api/v1/digiverses?page=1&limit=10&creators={$user2->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse6->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 3);
    }

    public function test_retrieve_all_created_digiverses_works_with_correct_parameters()
    {
        $user1 = User::factory()->create();
        $user1->assignRole(Roles::USER);
        $this->be($user1);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $digiverse1 = Collection::factory()
        ->state([
            'title' => 'title1',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->unavailable()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();
        $digiverse2 = Collection::factory()
        ->state([
            'title' => 'title2',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->unavailable()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();
        $digiverse3 = Collection::factory()
        ->state([
            'title' => 'title3',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->unavailable()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();

        $digiverse4 = Collection::factory()
        ->state([
            'description' => 'title1',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->unavailable()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();
        $digiverse5 = Collection::factory()
        ->state([
            'description' => 'title2',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->unavailable()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();
        $digiverse6 = Collection::factory()
        ->state([
            'description' => 'title3',
        ])
        ->for($user1, 'owner')
        ->digiverse()
        ->unavailable()
        ->hasAttached(
            Asset::factory()->count(1),
            [
            'id' => Str::uuid(),
            'purpose' => 'cover'
            ]
        )
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user1->id
            ])
        )
        ->create();

        // when no filtering is set
        $response = $this->json('GET', '/api/v1/account/digiverses?page=1&limit=10');
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'digiverses' => [
                    DigiverseMock::STANDARD_DIGIVERSE_RESPONSE['data']['digiverse'],
                ],
                'current_page',
                'items_per_page',
                'total',
            ]
        ])
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse3->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse6->id
        ]);

        // when two tags are specified
        $response = $this->json('GET', "/api/v1/account/digiverses?page=1&limit=10&tags={$tag1->id},{$tag2->id},{$tag3->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse3->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse6->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 6);

        // when a single tag is specified
        $response = $this->json('GET', "/api/v1/account/digiverses?page=1&limit=10&tags={$tag1->id},");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse2->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse5->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 4);

        // when filtering is done by keyword
        $response = $this->json('GET', '/api/v1/account/digiverses?page=1&limit=10&keyword=title1');
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $digiverse1->id
        ])
        ->assertJsonFragment([
            'id' => $digiverse4->id
        ]);
        $this->assertTrue(count($response->getData()->data->digiverses) === 2);
    }
}
