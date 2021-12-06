<?php

namespace Tests\Feature\Content;

use App\Models\Content;
use App\Models\Price;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LiveTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_start_live_works()
    {
        $user = User::factory()->create();
        $this->be($user);
        $content = Content::factory()
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
    }

    public function test_start_live_does_not_work_for_non_live_content()
    {
        $user = User::factory()->create();
        $this->be($user);
        $content = Content::factory()
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
        $user = User::factory()->create();
        $this->be($user);
        $user2 = User::factory()->create();
        $content = Content::factory()
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
        $user = User::factory()->create();
        $this->be($user);
        $user2 = User::factory()->create();
        $content = Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);
    }

    public function test_join_live_works()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $content = Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->has(Price::factory()->state([
            'amount' => 0,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ]))
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
                'uid'
            ]
        ]);
    }

    public function test_end_live_does_not_work_if_not_creator_live_content()
    {
        $user = User::factory()->create();
        
        $user2 = User::factory()->create();
        $content = Content::factory()
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
        $user = User::factory()->create();
        $this->be($user);
        $content = Content::factory()
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
