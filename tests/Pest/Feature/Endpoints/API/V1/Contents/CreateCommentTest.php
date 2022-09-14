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
        'type' => 'comment',
    ];
    $response = $this->json('POST', "/api/v1/contents/{$comment->id}/comments", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentCommentResponse());

    $this->assertDatabaseHas('content_comment_comments', [
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_comment_id' => $comment->id,
    ]);
});

test('create content  comment works', function()
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
        'comment' => 'A new comment',
        'type' => 'content',
    ];
    $response = $this->json('POST', "/api/v1/contents/{$content->id}/comments", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentResponse());

    $this->assertDatabaseHas('content_comments', [
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);
});

test('user can have more than one content comment', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();
    $comment = Models\ContentComment::factory()
    ->create([
        'user_id' => $user->id,
    ]);

    $request = [
        'comment' => 'A comment',
        'type' => 'content'
    ];

    $response = $this->json('POST', "/api/v1/contents/{$content->id}/comments", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateCreateCommentResponse());

    $this->assertDatabaseHas('content_comments', [
        'comment' => $request['comment'],
        'user_id' => $user->id,
        'content_id' => $content->id,
    ]);

    $comments = Models\ContentComment::where('user_id', $user->id)->get();
    $this->assertEquals($comments->count(), 2);
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
        'type' => 'comment'
    ];

    $response = $this->json('POST', "/api/v1/contents/{$comment->id}/comments", $request);
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
    $request = [
        'comment' => 'A comment',
    ];

    $response = $this->json('POST', "/api/v1/contents/{$content->id}/comments", $request);
    $response->assertStatus(401); 
});

it('returns a 400 if content does not exist', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $content = Models\Content::factory()->create();
    $request = [
        'comment' => 'A comment',
    ];

    $response = $this->json('POST', "/api/v1/contents/asdfhjkl/comments", $request);
    $response->assertStatus(400); 
});