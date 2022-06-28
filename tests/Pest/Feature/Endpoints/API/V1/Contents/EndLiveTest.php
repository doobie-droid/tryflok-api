<?php 

use App\Models;

test('end live does not work if not creator live content', function()
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

        $this->assertTrue(is_null($content->refresh()->live_ended_at));
});

test('end live works', function()
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
        $this->assertFalse(is_null($content->refresh()->live_ended_at));
});