<?php 

use App\Models;
use Tests\MockData;

test('list content review works', function()
{
    $user = Models\User::factory()->create();
    

    $review = Models\Review::factory()
    ->for($user, 'user')
    ->create();
    $this->be($user);

    dd($review->id);

    $response = $this->json('GET', "/api/v1/reviews/{$review->id}/reviews");
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Review::generatelistReviewResponse());
});