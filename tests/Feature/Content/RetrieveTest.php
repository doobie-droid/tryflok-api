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
}
