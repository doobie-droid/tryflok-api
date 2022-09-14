<?php 

use App\Models;
use Tests\MockData;

test('delete content comment works', function()
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
        $response = $this->json('DELETE', "/api/v1/contents/{$comment->id}/comments");
        dd($response);
        $response->assertStatus(200);
});