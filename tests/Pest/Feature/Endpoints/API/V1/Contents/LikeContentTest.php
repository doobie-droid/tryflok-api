<?php 

use App\Models;
use Tests\MockData;

test('user who is signed in can like content', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $contentLikes = Models\ContentLike::factory()
        ->for($content, 'content')
        ->count(3)
        ->create();

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/like");
        dd($response);
        $response->assertStatus(200);

        $this->assertDatabaseHas('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);
})->only();

test('user who is not signed in cannot like a content', function()
{
        $user = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->create();

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/like");     
        $response->assertStatus(401);

        $this->assertDatabaseMissing('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);
});

test('user cannot like a content more than once', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $content->likes()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/like");     
        $response->assertStatus(400);
});