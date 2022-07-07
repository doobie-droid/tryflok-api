<?php 

use App\Models;
use Tests\MockData;

test('list content review works', function()
{           
            $user = Models\User::factory()->create();
            $content = Models\Content::factory()
            ->create();

            Models\Review::create([
            'user_id' => $user->id,
            'reviewable_type' => 'content',
            'reviewable_id' => $content->id,

            ]);

            $response = $this->json('GET', "/api/v1/contents/{$content->id}/reviews");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Review::generateGetReviewResponse());
            $this->assertEquals($response->getData()->data->reviews->data[0]->user_id, $user->id);
            
});

it('returns a 404 when invalid content ID is supplied', function()
{           
            $response = $this->json('GET', "/api/v1/contents/{-1}/reviews");
            $response->assertStatus(400);
});