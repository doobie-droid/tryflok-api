<?php 

use App\Models;
use Tests\MockData;

test('delete comment works for content comment comment', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $comment = Models\ContentComment::create([
        'user_id' => $user->id,
        'comment' => 'A content comment',
        'content_id' => $content->id,
        ]);

        $commentComment = Models\ContentCommentComment::create([
                'user_id' => $user->id,
                'comment' => 'A content comment',
                'content_comment_id' => $comment->id,
            ]);

        $response = $this->json('DELETE', "/api/v1/content-comment-comments/{$commentComment->id}");
        $response->assertStatus(200);
        $comment_returned = $response->getData()->data->comment;
        $this->assertTrue($comment_returned->deleted_at != null);
});

test('only selected comment comment is deleted', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $comment = Models\ContentComment::create([
        'user_id' => $user->id,
        'comment' => 'A content comment',
        'content_id' => $content->id,
        ]);

        $commentComment1 = Models\ContentCommentComment::create([
                'user_id' => $user->id,
                'comment' => 'A content comment',
                'content_comment_id' => $comment->id,
        ]);

        $commentComment2 = Models\ContentCommentComment::create([
                'user_id' => $user->id,
                'comment' => 'A content comment',
                'content_comment_id' => $comment->id,
        ]);


        $response = $this->json('DELETE', "/api/v1/content-comment-comments/{$commentComment1->id}");
        $response->assertStatus(200);

        $comment_returned = $response->getData()->data->comment;
        $this->assertTrue($comment_returned->deleted_at != null);
        
        $this->assertDatabaseHas('content_comment_comments', [
                'id' => $commentComment2->id,
                'comment' => $commentComment2->comment,
                'user_id' => $user->id,
                'content_comment_id' => $comment->id,
        ]);
});

test('comment comment  is not deleted if user does not own it', function()
{
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $comment = Models\ContentComment::create([
        'user_id' => $user2->id,
        'comment' => 'A content comment',
        'content_id' => $content->id,
        ]);

        $commentComment = Models\ContentCommentComment::create([
                'user_id' => $user2->id,
                'comment' => 'A content comment',
                'content_comment_id' => $comment->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/content-comment-comments/{$commentComment->id}");
        $response->assertStatus(400);

        $this->assertDatabaseHas('content_comment_comments', [
                'id' => $commentComment->id,
                'comment' => $commentComment->comment,
                'user_id' => $user2->id,
                'content_comment_id' => $comment->id,
        ]);
});

test('comment is not deleted if user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->create();

        $comment = Models\ContentComment::create([
        'user_id' => $user2->id,
        'comment' => 'A content comment',
        'content_id' => $content->id,
        ]);

        $commentComment = Models\ContentCommentComment::create([
                'user_id' => $user2->id,
                'comment' => 'A content comment',
                'content_comment_id' => $comment->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/content-comment-comments/{$commentComment->id}");
        $response->assertStatus(401);

        $this->assertDatabaseHas('content_comment_comments', [
                'id' => $commentComment->id,
                'comment' => $commentComment->comment,
                'user_id' => $user2->id,
                'content_comment_id' => $comment->id,
        ]);
});
