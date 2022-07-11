<?php

use App\Models;
use Tests\MockData;

test('list collections works', function()
{   
    $user = Models\User::factory()->create(); 
    $collection = Models\Collection::factory()
    ->create();
    $this->be($user);  
        
    $response = $this->json('GET', "/api/v1/collections/{$collection->id}");
    $response->assertStatus(200)
    ->assertJsonStructure([
        'data' => [
            'collection' => [
                'id',
                'title',
                'description',
                'user_id', // owner of collection
                'type', // book, series, channel, digiverse
                'is_available',
                'approved_by_admin',
                'show_only_in_collections',
                'views',
            ]
        ]
    ]
    );
});