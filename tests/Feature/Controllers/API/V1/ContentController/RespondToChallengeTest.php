<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class RespondToChallengeTest extends TestCase
{
    use DatabaseTransactions;
   
    public function test_respond_to_challenge_works()
    {
        $user = Models\User::factory()->create();
        $contestant1 = Models\User::factory()->create();
        $contestant2 = Models\User::factory()->create();

        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->liveVideo()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2])
        ->setChallengeDetails()
        ->create();

        $this->be($contestant1);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/respond-to-challenge", [
            'action' => 'accept',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_challenge_contestants', [
            'content_id' => $content->id,
            'user_id' => $contestant1->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $user->id,
            'notifier_id' => $contestant1->id,
            'notificable_type' => 'content',
            'notificable_id' => $content->id,
            'message' => "@{$contestant1->username} has accepted your {$content->title} challenge.",
        ]);

        $this->be($contestant2);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/respond-to-challenge", [
            'action' => 'decline',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_challenge_contestants', [
            'content_id' => $content->id,
            'user_id' => $contestant2->id,
            'status' => 'declined',
        ]);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $user->id,
            'notifier_id' => $contestant2->id,
            'notificable_type' => 'content',
            'notificable_id' => $content->id,
            'message' => "@{$contestant2->username} has declined your {$content->title} challenge.",
        ]);
    }
}
