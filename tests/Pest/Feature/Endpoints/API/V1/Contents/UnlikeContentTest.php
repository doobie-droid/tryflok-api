<?php 

use App\Models;
use Tests\MockData;

test('unlike content works', function()
{
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $content->likes()->create([
            'user_id' => $user->id,
        ]);

        $content->likes()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/contents/{$content->id}/like");     
        $response->assertStatus(200);
        $content_like = $response->getData()->data->content;
        $this->assertEquals(count($content_like->likes), 0);

        $this->assertDatabaseMissing('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);
        $this->assertDatabaseHas('content_likes', [
            'user_id' => $user2->id,
            'content_id' => $content->id,
        ]);
});

it('does not work when content id is invalid', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $content->likes()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/contents/-1/like");     
        $response->assertStatus(400);

        $this->assertDatabaseHas('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);
});