<?php 

use App\Models;
use Tests\MockData;

test('list collection review works', function()

{           
        $collection = Models\Collection::factory()
        ->create();
        $collection_review = $collection->reviews()->create([
                        'user_id' => $collection->user_id,
                        'comment' => 'A comment',
                        'rating' => 5,
                        ]);

            $response = $this->json('GET', "/api/v1/collections/{$collection->id}/reviews");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Review::generateGetReviewResponse());
           $this->assertEquals($response->getData()->data->reviews->data[0]->user_id, $collection->user_id);
            
});

it('returns a 400 when invalid collection ID is supplied', function()
{           
            $response = $this->json('GET', "/api/v1/collections/{-1}/reviews");
            $response->assertStatus(400);
});