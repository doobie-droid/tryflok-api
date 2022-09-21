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

        $contentCommentComment = Models\ContentCommentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_comment_id' => $contentComment->id,
        ]);

        $response = $this->json('POST', "/api/v1/content-comment-comments/{$contentCommentComment->id}/like");
        $response->assertStatus(200);
        $content_comment_comment_like = $response->getData()->data->contentCommentComment;
        $this->assertEquals(count($content_comment_comment_like->likes), 1);
        $this->assertEquals($content_comment_comment_like->likes[0]->content_comment_comment_id, $contentCommentComment->id);
        $this->assertEquals($content_comment_comment_like->likes[0]->user_id, $user->id);

        $this->assertDatabaseHas('content_comment_comment_likes', [
            'user_id' => $user->id,
            'content_comment_comment_id' => $contentCommentComment->id,
        ]);

        $contentCommentCommentLike = Models\ContentCommentCommentLike::where('content_comment_comment_id', $contentCommentComment->id)
        ->where('user_id', $user->id)
        ->first();

        $this->assertTrue($contentCommentCommentLike->count() === 1);
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

        $contentCommentComment = Models\ContentCommentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_comment_id' => $contentComment->id,
        ]);

        $response = $this->json('POST', "/api/v1/content-comment-comments/{$contentCommentComment->id}/like");   
        $response->assertStatus(401);

        $this->assertDatabaseMissing('content_comment_comment_likes', [
            'user_id' => $user->id,
            'content_comment_comment_id' => $contentCommentComment->id,
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

        $contentCommentComment = Models\ContentCommentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_comment_id' => $contentComment->id,
        ]);

        $response = $this->json('POST', "/api/v1/content-comment-comments/{$contentCommentComment->id}/like");   
        $response->assertStatus(200);
        $content_comment_comment_like = $response->getData()->data->contentCommentComment;
        $this->assertEquals(count($content_comment_comment_like->likes), 1);
        $this->assertEquals($content_comment_comment_like->likes[0]->content_comment_comment_id, $contentCommentComment->id);
        $this->assertEquals($content_comment_comment_like->likes[0]->user_id, $user->id);

        $contentCommentCommentLike = Models\ContentCommentCommentLike::where('content_comment_comment_id', $contentCommentComment->id)
        ->where('user_id', $user->id)
        ->first();

        $this->assertTrue($contentCommentCommentLike->count() === 1);
});