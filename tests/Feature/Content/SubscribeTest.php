<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;
use Tests\MockData\Content as ContentMock;

class SubscribeTest extends TestCase
{
    use DatabaseTransactions, WithFaker;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_subscribe_to_content_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse
            ,
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();

        //asset user gets attached
        $response = $this->json('POST', "/api/v1/contents/{$newsletter->id}/subscription");
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_subscriber', [
            'content_id' => $newsletter->id,
            'user_id' => $user->id,
        ]);
        $this->assertTrue($newsletter->subscribers()->count() === 1);
        
        // assert not duplicate entry is made
        $response = $this->json('POST', "/api/v1/contents/{$newsletter->id}/subscription");
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_subscriber', [
            'content_id' => $newsletter->id,
            'user_id' => $user->id,
        ]);
        $this->assertTrue($newsletter->subscribers()->count() === 1);
    }

    public function test_unsubscribe_from_content_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse
            ,
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();

        $response = $this->json('POST', "/api/v1/contents/{$newsletter->id}/subscription");
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_subscriber', [
            'content_id' => $newsletter->id,
            'user_id' => $user->id,
        ]);
        $this->assertTrue($newsletter->subscribers()->count() === 1);
        
        $response = $this->json('DELETE', "/api/v1/contents/{$newsletter->id}/subscription");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('content_subscriber', [
            'content_id' => $newsletter->id,
            'user_id' => $user->id,
        ]);
        $this->assertTrue($newsletter->subscribers()->count() === 0);
    }
}
