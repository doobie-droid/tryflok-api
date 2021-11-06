<?php

namespace Tests\Feature\Digiverse;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\MockData\User as UserMock;
use Tests\MockData\Digiverse as DigiverseMock;
use App\Models\User;
use App\Models\Collection;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

class RetrieveTest extends TestCase
{
    use DatabaseTransactions, WithFaker;
    const STANDARD_DIGIVERSE_RESPONSE = [
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
                'ratings_count',
                'ratings_average',
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
    ];

    public function test_retrieve_digiverse_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);

        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover'
        ])
        ->hasAttached(Tag::factory()->count(1), [
            'id' => Str::uuid(),
        ])
        ->has(Price::factory()->subscription()->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $user->id
            ])
        )
        ->create();

        $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}");
        $response->assertStatus(200)->assertJsonStructure(self::STANDARD_DIGIVERSE_RESPONSE);
    }
}
