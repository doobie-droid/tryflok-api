<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class SubscribeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subscribe_to_content_works()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Models\Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse,
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
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Models\Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse,
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
