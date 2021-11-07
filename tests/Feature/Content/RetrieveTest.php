<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;
use Tests\MockData\Content as ContentMock;
use App\Constants\Constants;

class RetrieveTest extends TestCase
{
    use DatabaseTransactions, WithFaker;
    private function generateSingleContent($user)
    {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $content = Content::factory()
        ->state([
            'type' => 'audio',
            'title' => 'title before update',
            'description' => 'description before update',
            'is_available' => 1,
        ])
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ])
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->hasAttached(
            Collection::factory()->digiverse(),
            [
                'id' => Str::uuid(),
            ],
        )
        ->has(Price::factory()->state([
            'amount' => 10,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ])->count(1))
        ->for($user, 'owner')
        ->create();

        $price = $content->prices()->first();
        $asset = $content->assets()->first();
        $cover = $content->cover()->first();
        
        return [
            'content' => $content,
            'cover' => $cover,
            'asset' => $asset,
            'tags' => [
                $tag1, 
                $tag2,
            ],
            'price' => $price,
        ];
    }

    public function test_retrieve_single_content_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $test_data = $this->generateSingleContent($user);
        $content = $test_data['content'];
        $response = $this->json('GET', "/api/v1/contents/{$content->id}");
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_CONTENT_RESPONSE);
    }

    public function test_retrieve_all_digiverse_contents_fails_with_invalid_parameters()
    {
        $digiverse = Collection::factory()->digiverse()->create();
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=-10");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?limit=ere");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?limit=-30");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
            
        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?tags=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0'
            ]
        ]);

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?creators=fdfr3-3434f-434,dfdrg-2323-frf");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'creators.0'
            ]
        ]);
    }

    public function test_retrieve_all_digiverse_contents_works_with_correct_parameters()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();

        $digiverse = Collection::factory()->digiverse()->create();

        $content1 = Content::factory()
        ->state([
            'title' => 'title1',
        ])
        ->for($user, 'owner')
        ->hasAttached($digiverse,
        [
            'id' => Str::uuid(),
        ])
        ->audio()
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();
        $content2 = Content::factory()
        ->state([
            'title' => 'title2',
        ])
        ->for($user, 'owner')
        ->hasAttached($digiverse,
        [
            'id' => Str::uuid(),
        ])
        ->audio()
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();
        $content3 = Content::factory()
        ->state([
            'title' => 'title3',
        ])
        ->for($user, 'owner')
        ->hasAttached($digiverse,
        [
            'id' => Str::uuid(),
        ])
        ->audio()
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();

        $content4 = Content::factory()
        ->state([
            'description' => 'title1',
        ])
        ->for($user, 'owner')
        ->hasAttached($digiverse,
        [
            'id' => Str::uuid(),
        ])
        ->audio()
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();
        $content5 = Content::factory()
        ->state([
            'description' => 'title2',
        ])
        ->for($user, 'owner')
        ->hasAttached($digiverse,
        [
            'id' => Str::uuid(),
        ])
        ->audio()
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();
        $content6 = Content::factory()
        ->state([
            'description' => 'title3',
        ])
        ->for($user, 'owner')
        ->hasAttached($digiverse,
        [
            'id' => Str::uuid(),
        ])
        ->audio()
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached($tag3, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();

        // when no filtering is set
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'contents' => [
                    ContentMock::STANDARD_CONTENT_RESPONSE['data']['content'],
                ],
                'current_page',
                'items_per_page',
                'total',
            ]
        ])
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content3->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ])
        ->assertJsonFragment([
            'id' => $content6->id
        ]);

        // when two tags are specified
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&tags={$tag1->id},{$tag2->id},{$tag3->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content3->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ])
        ->assertJsonFragment([
            'id' => $content6->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 6);

        // when a single tag is specified
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&tags={$tag1->id},");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 4);

        // when filtering is done by keyword
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&keyword=title1");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 2);

        // when filtering is done by creators
        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/contents?page=1&limit=10&creators={$user->id}");
        $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $content1->id
        ])
        ->assertJsonFragment([
            'id' => $content2->id
        ])
        ->assertJsonFragment([
            'id' => $content3->id
        ])
        ->assertJsonFragment([
            'id' => $content4->id
        ])
        ->assertJsonFragment([
            'id' => $content5->id
        ])
        ->assertJsonFragment([
            'id' => $content6->id
        ]);
        $this->assertTrue(count($response->getData()->data->contents) === 6);
    }
}
