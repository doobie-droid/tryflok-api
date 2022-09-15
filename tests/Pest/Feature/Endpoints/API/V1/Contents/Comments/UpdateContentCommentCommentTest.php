<?php 

use App\Models;
use Tests\MockData;

test('update content comment works for content comment comment', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();

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
        'comment' => 'updated comment',
    ];

    $response = $this->json('PATCH', "/api/v1/content-comment-comments/{$commentComment->id}", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentCommentResponse());

    $this->assertDatabaseHas('content_comment_comments', [
        'id' => $commentComment->id,
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);

    $this->assertDatabaseMissing('content_comment_comments', [
        'id' => $commentComment->id,
        'comment' => $commentComment->comment,
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);
});

it('does not work if user is not owner of comment comment', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();

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

    $request = [
        'comment' => 'updated comment',
    ];

    $response = $this->json('PATCH', "/api/v1/content-comment-comments/{$commentComment->id}", $request);
    $response->assertStatus(400);

    $this->assertDatabaseHas('content_comments', [
        'id' => $comment->id,
        'comment' => $comment->comment,
        'user_id' => $user2->id,
        'content_id' => $content->id,
    ]);
});

it('does not work if user is not signed in', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $content = Models\Content::factory()->create();

    $comment = Models\ContentComment::create([
        'user_id' => $user2->id,
        'comment' => 'A content comment',
        'content_id' => $content->id,
    ]);
    $commentComment = Models\ContentCommentComment::create([
        'user_id' => $user->id,
        'comment' => 'A content comment',
        'content_comment_id' => $comment->id,
    ]);

    $request = [
        'comment' => 'updated comment',
    ];

    $response = $this->json('PATCH', "/api/v1/content-comment-comments/{$commentComment->id}", $request);
    $response->assertStatus(401);

    $this->assertDatabaseHas('content_comments', [
        'id' => $comment->id,
        'comment' => $comment->comment,
        'user_id' => $user2->id,
        'content_id' => $content->id,
    ]);
});

test('only selected comment is updated', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();

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

    $request = [
        'comment' => 'updated comment',
    ];

    $response = $this->json('PATCH', "/api/v1/content-comment-comments/{$commentComment1->id}", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentCommentResponse());

    $this->assertDatabaseHas('content_comment_comments', [
        'id' => $commentComment1->id,
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);
    $this->assertDatabaseHas('content_comment_comments', [
        'id' => $commentComment2->id,
        'comment' => $commentComment2->comment,
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);
});