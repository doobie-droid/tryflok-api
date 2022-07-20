<?php 

use App\Models;
use Tests\MockData;

test('list review review works', function()
{
    $review = Models\Review::factory()->create();
    $review_review = $review->reviews()->create([
        'user_id' => $review->user->id,
        'comment' => 'A comment',
        'rating' => 5,
    ]);
    $response = $this->json('GET', "/api/v1/reviews/{$review->id}/reviews");
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Review::generatelistReviewResponse());
});

it('does not return review when an invalid review id is submitted', function()
{
    $review = Models\Review::factory()->create();
    $review_review = $review->reviews()->create([
        'user_id' => $review->user->id,
        'comment' => 'A comment',
        'rating' => 5
    ]);
    $response = $this->json('GET', "/api/v1/reviews/-1/reviews");
    $response->assertStatus(400);

    //when no id is submitted
    $response = $this->json('GET', "/api/v1/reviews/reviews");
    $response->assertStatus(404);
});