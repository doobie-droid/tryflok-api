<?php 

use App\Models;
use Tests\MockData;

test('user who is signed in can like content', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/like");
        $response->assertStatus(200);
        $content_like = $response->getData()->data->content;
        $this->assertEquals(count($content_like->likes), 1);
        $this->assertEquals($content_like->likes[0]->content_id, $content->id);
        $this->assertEquals($content_like->likes[0]->user_id, $user->id);

        $this->assertDatabaseHas('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        $contentLike = Models\ContentLike::where('content_id', $content->id)
        ->where('user_id', $user->id)
        ->first();

        $this->assertTrue($contentLike->count() === 1);
});

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
        $response->assertStatus(200);
        $content_like = $response->getData()->data->content;
        $this->assertEquals(count($content_like->likes), 1);
        $this->assertEquals($content_like->likes[0]->content_id, $content->id);
        $this->assertEquals($content_like->likes[0]->user_id, $user->id);

        $this->assertDatabaseHas('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        $contentLike = Models\ContentLike::where('content_id', $content->id)
        ->where('user_id', $user->id)
        ->first();

        $this->assertTrue($contentLike->count() === 1);
});
test('notification is sent to owner of content', function()
{
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create([
            'user_id' => $user2,
        ]);
        $like = $content->likes()->create([
            'user_id' => $user->id,
        ]);
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/like");  
        $response->assertStatus(200);

        $this->assertDatabaseHas('content_likes', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $user2->id,
            'notifier_id' => $user->id,
            'notificable_type' => 'content',
            'message' => "@{$user->username} just liked your content: {$content->title}",
        ]);
});