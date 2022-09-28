<?php 

use App\Models;
use Tests\MockData;

test('user who is signed in can like content comment', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();
        $contentComment = Models\ContentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_id' => $content->id,
        ]);

        $response = $this->json('POST', "/api/v1/content-comments/{$contentComment->id}/like");
        $response->assertStatus(200);
        $content_comment_like = $response->getData()->data->contentComment;
        $this->assertEquals(count($content_comment_like->likes), 1);
        $this->assertEquals($content_comment_like->likes[0]->content_comment_id, $contentComment->id);
        $this->assertEquals($content_comment_like->likes[0]->user_id, $user->id);

        $this->assertDatabaseHas('content_comment_likes', [
            'user_id' => $user->id,
            'content_comment_id' => $contentComment->id,
        ]);

        $contentCommentLike = Models\ContentCommentLike::where('content_comment_id', $contentComment->id)
        ->where('user_id', $user->id)
        ->first();
        $this->assertEquals($contentCommentLike->count(), 1);
});

test('user who is not signed in cannot like a content comment', function()
{
        $user = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->create();

        $contentComment = Models\ContentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_id' => $content->id,
        ]);

        $response = $this->json('POST', "/api/v1/content-comments/{$contentComment->id}/like");   
        $response->assertStatus(401);

        $this->assertDatabaseMissing('content_comment_likes', [
            'user_id' => $user->id,
            'content_comment_id' => $contentComment->id,
        ]);
});

test('user cannot like a content more than once', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $contentComment = Models\ContentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_id' => $content->id,
        ]);

        $contentComment->likes()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('POST', "/api/v1/content-comments/{$contentComment->id}/like");     
        $response->assertStatus(200);
        $content_comment_like = $response->getData()->data->contentComment;
        $this->assertEquals(count($content_comment_like->likes), 1);
        $this->assertEquals($content_comment_like->likes[0]->content_comment_id, $contentComment->id);
        $this->assertEquals($content_comment_like->likes[0]->user_id, $user->id);

        $this->assertDatabaseHas('content_comment_likes', [
            'user_id' => $user->id,
            'content_comment_id' => $contentComment->id,
        ]);

        $contentCommentLike = Models\ContentCommentLike::where('content_comment_id', $contentComment->id)
        ->where('user_id', $user->id)
        ->first();

        $this->assertTrue($contentCommentLike->count() === 1);
});