<?php 

use App\Models;
use Tests\MockData;

test('list content review works', function()
{

    $review = Models\Review::factory()->create();
    
    $response = $this->json('GET', "/api/v1/reviews/{$review->id}/reviews?page=1&limit=2");
    dd($response);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Review::generatelistReviewResponse());
});