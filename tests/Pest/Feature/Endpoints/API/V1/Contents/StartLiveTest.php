<?php 

use App\Models;

test('start live works', function()
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
                'uid',
            ],
        ]);

        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'live_status' => 'active',
        ]);
        $this->assertTrue($content->refresh()->scheduled_date->lte(now()));

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $user2->id,
            'notifier_id' => $user->id,
            'notificable_type' => 'content',
            'notificable_id' => $content->id,
            'message' => "@{$user->username} has started a new live",
        ]);
});

test('start live does not work for non live content', function()
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
});

test('start live does not work if not creator live content', function()
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
});