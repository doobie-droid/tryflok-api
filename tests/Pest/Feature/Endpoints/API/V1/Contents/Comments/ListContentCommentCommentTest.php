<?php 

use App\Models;
use Tests\MockData;

test('list content comment comments works', function()
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

            Models\ContentCommentComment::create([
                'user_id' => $user->id,
                'comment' => 'A content comment',
                'content_comment_id' => $contentComment->id,
            ]);

            $response = $this->json('GET', "/api/v1/content-comments/{$contentComment->id}/comments");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Content::generateListCommentResponse());
            $this->assertEquals($response->getData()->data->comments->data[0]->user_id, $user->id);
});