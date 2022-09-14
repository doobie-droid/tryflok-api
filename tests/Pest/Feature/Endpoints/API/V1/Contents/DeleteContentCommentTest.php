<?php 

use App\Models;
use Tests\MockData;

test('only selected comment is deleted', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $comment1 = Models\ContentComment::create([
        'user_id' => $user->id,
        'comment' => 'A content comment',
        'content_id' => $content->id,
        ]);

        $comment2 = Models\ContentComment::create([
                'user_id' => $user->id,
                'comment' => 'A content comment',
                'content_id' => $content->id,
        ]);

        $request = [
                'type' => 'content',
        ];

        $response = $this->json('DELETE', "/api/v1/comments/{$comment1->id}", $request);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('content_comments', [
                'id' => $comment1->id,
                'comment' => $comment1->comment,
                'user_id' => $user->id,
                'content_id' => $content->id,
        ]);
        
        $this->assertDatabaseHas('content_comments', [
                'id' => $comment2->id,
                'comment' => $comment2->comment,
                'user_id' => $user->id,
                'content_id' => $content->id,
        ]);
});

test('comment is not deleted if user does not own comment', function()
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

        $request = [
                'type' => 'content',
        ];

        $response = $this->json('DELETE', "/api/v1/comments/{$comment->id}", $request);
        $response->assertStatus(400);

        $this->assertDatabaseHas('content_comments', [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_id' => $user2->id,
                'content_id' => $content->id,
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

        $request = [
                'type' => 'content',
        ];

        $response = $this->json('DELETE', "/api/v1/comments/{$comment->id}", $request);
        $response->assertStatus(401);

        $this->assertDatabaseHas('content_comments', [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_id' => $user2->id,
                'content_id' => $content->id,
        ]);
});

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

        $request = [
                'type' => 'comment',
        ];

        $response = $this->json('DELETE', "/api/v1/comments/{$commentComment->id}", $request);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('content_comment_comments', [
                'id' => $commentComment->id,
                'comment' => $commentComment->comment,
                'user_id' => $user->id,
                'content_comment_id' => $comment->id,
        ]);
});

test('delete comment works for content comment', function()
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

        $request = [
                'type' => 'content',
        ];

        $response = $this->json('DELETE', "/api/v1/comments/{$comment->id}", $request);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('content_comments', [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_id' => $user->id,
                'content_id' => $comment->id,
        ]);
});