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

        $contentComment->likes()->create([
            'user_id' => $user->id,
        ]);

        $contentComment->likes()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/content-comments/{$contentComment->id}/like");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('content_comment_likes', [
            'user_id' => $user->id,
            'content_comment_id' => $contentComment->id,
        ]);
        $this->assertDatabaseHas('content_comment_likes', [
            'user_id' => $user2->id,
            'content_comment_id' => $contentComment->id,
        ]);
});

it('does not work when content comment id is invalid', function()
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

        $response = $this->json('DELETE', "/api/v1/content-comments/asdgfhjgk/like");
        $response->assertStatus(400);
});