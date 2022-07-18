<?php 

use App\Models;
use Tests\MockData;

test('list digiverse review works', function()

{           
        $content = Models\Content::factory()->noDigiverse()->create();
        $digiverse = Models\Collection::factory()
                        ->digiverse()
                        ->create();
        $digiverse_review = $digiverse->reviews()->create([
                        'user_id' => $digiverse->user_id,
                        'comment' => 'A comment',
                        'rating' => 5,
                        ]);

            $response = $this->json('GET', "/api/v1/digiverses/{$digiverse->id}/reviews");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\Review::generateGetReviewResponse());
           $this->assertEquals($response->getData()->data->reviews->data[0]->user_id, $digiverse->user_id);
            
});

it('returns a 400 when invalid digiverse ID is supplied', function()
{           
            $response = $this->json('GET', "/api/v1/digiverses/{-1}/reviews");
            $response->assertStatus(400);
});
