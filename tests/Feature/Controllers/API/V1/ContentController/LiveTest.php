<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Jobs\Content\DispatchNotificationToFollowers as DispatchNotificationToFollowersJob;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class LiveTest extends TestCase
{
    use DatabaseTransactions;

    public function test_start_live_works()
    {
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();

        $user->followers()->syncWithoutDetaching([
            $user2->id => [
                'id' => Str::uuid(),
            ],
        ]);

        $this->be($user);
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->liveAudio()
        ->futureScheduledDate()
        ->create();

        $this->assertTrue($content->scheduled_date->gt(now()));
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'rtc_token',
                'rtm_token',
                'channel_name',
                'uid'
            ]
        ]);

        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'live_status' => 'active',
        ]);
        $this->assertTrue($content->refresh()->scheduled_date->lte(now()));

        $this->assertDatabaseHas('notifications', [
            'notifier_id' => $user->id,
            'notificable_type' => 'content',
            'notificable_id' => $content->id,
            'message' => "@{$user->username} has started a new live",
        ]);
    }

    public function test_start_live_does_not_work_for_non_live_content()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->audio()
        ->create();

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);

        $this->assertDatabaseMissing('contents', [
            'id' => $content->id,
            'live_status' => 'active',
        ]);
    }

    public function test_start_live_does_not_work_if_not_creator_live_content()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);

        $this->assertDatabaseMissing('contents', [
            'id' => $content->id,
            'live_status' => 'active',
        ]);
    }

    public function test_join_live_does_not_work_if_channel_not_started()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);
    }

    public function test_join_live_works()
    {
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $this->be($user2);
        $this->json('POST', "/api/v1/contents/{$content->id}/live");

        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'rtc_token',
                'rtm_token',
                'channel_name',
                'uid',
                'subscribers_count',
            ]
        ]);
    }

    public function test_end_live_does_not_work_if_not_creator_live_content()
    {
        $user = Models\User::factory()->create();
        
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $this->be($user2);
        $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $this->be($user);
        $response = $this->json('DELETE', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);

        $this->assertDatabaseMissing('contents', [
            'id' => $content->id,
            'live_status' => 'ended',
        ]);
    }

    public function test_end_live_works()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->liveAudio()
        ->create();

        $this->json('POST', "/api/v1/contents/{$content->id}/live");

        $response = $this->json('DELETE', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200);

        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'live_status' => 'ended',
        ]);
    }
}
