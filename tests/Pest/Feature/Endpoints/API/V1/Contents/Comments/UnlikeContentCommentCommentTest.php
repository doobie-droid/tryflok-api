<?php 

use App\Models;
use Tests\MockData;

test('unlike content comment works', function()
{
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
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

        $contentCommentComment->likes()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/content-comment-comments/{$contentCommentComment->id}/like"); 
        $response->assertStatus(200);
        $this->assertDatabaseMissing('content_comment_likes', [
            'user_id' => $user->id,
            'content_comment_id' => $contentComment->id,
        ]);
});

it('does not work when content comment comment id is invalid', function()
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

        $contentCommentComment->likes()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/content-comment-comments/lkjhagsfd/like");
        $response->assertStatus(400);
});