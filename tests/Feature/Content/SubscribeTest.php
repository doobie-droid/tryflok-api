<?php

namespace Tests\Feature\Content;

use App\Constants\Roles;
use App\Models\Collection;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

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
