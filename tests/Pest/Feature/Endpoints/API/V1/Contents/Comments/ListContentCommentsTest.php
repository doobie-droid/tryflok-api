<?php 

use App\Models;
use Tests\MockData;

test('list content comments works', function()
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

            $response = $this->json('GET', "/api/v1/contents/{$content->id}/comments");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Content::generateListCommentResponse());
            $this->assertEquals($response->getData()->data->comments->data[0]->user_id, $user->id);
});

it('returns a 400 when invalid content ID is supplied', function()
{        
            $user = Models\User::factory()->create();
            $this->be($user);   
            $response = $this->json('GET', "/api/v1/contents/{-1}/comments");
            $response->assertStatus(400);
});

it('returns a 401 when user is not signed in', function()
{
            $user = Models\User::factory()->create();
            $content = Models\Content::factory()
            ->create();

            Models\ContentComment::create([
            'user_id' => $user->id,
            'comment' => 'A content comment',
            'content_id' => $content->id,
            ]);

            $response = $this->json('GET', "/api/v1/contents/{$content->id}/comments");
            $response->assertStatus(401);
});