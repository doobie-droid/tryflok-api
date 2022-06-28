<?php 

use App\Models;

test('join live does not work if channel not started', function()
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
});

test('join live works', function()
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
            ],
        ]);
});