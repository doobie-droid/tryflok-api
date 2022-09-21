<?php 

use App\Models;
use Tests\MockData;

test('create content comment comment works', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();
    $comment = Models\ContentComment::create([
        'comment' => 'A comment',
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);

    $request = [
        'comment' => 'A comment',
    ];
    $response = $this->json('POST', "/api/v1/content-comments/{$comment->id}/comments", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentCommentResponse());

    $this->assertDatabaseHas('content_comment_comments', [
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);
});

test('user can have more than one content comment comment', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();
    $comment = Models\ContentComment::factory()
    ->create([
        'user_id' => $user->id,
    ]);

    $commentComment = Models\ContentCommentComment::factory()
    ->create([
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);

    $request = [
        'comment' => 'A new comment',
    ];

    $response = $this->json('POST', "/api/v1/content-comments/{$comment->id}/comments", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentCommentResponse());

    $this->assertDatabaseHas('content_comment_comments', [
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);

    $comments = Models\ContentCommentComment::where('user_id', $user->id)->get();
    $this->assertEquals($comments->count(), 2);
});

it('returns a 401 when user is not signed in', function()
{
    $user = Models\User::factory()->create();
    $content = Models\Content::factory()->create();
    $comment = Models\ContentComment::factory()
    ->create([
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
    $request = [
        'comment' => 'A comment',
    ];

    $response = $this->json('POST', "/api/v1/content-comments/{$comment->id}/comments", $request);
    $response->assertStatus(401); 
});

it('returns a 400 if content_comment does not exist', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();
    $comment = Models\ContentComment::factory()
    ->create([
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
    $request = [
        'comment' => 'A comment',
    ];

    $response = $this->json('POST', "/api/v1/content-comments/asdfhjkl/comments", $request);
    $response->assertStatus(400); 
});
