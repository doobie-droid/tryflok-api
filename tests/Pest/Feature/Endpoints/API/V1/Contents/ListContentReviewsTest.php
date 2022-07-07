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
           // dd($response);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Content::generateGetReviewResponse());
});