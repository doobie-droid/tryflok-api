<?php 

use App\Models;
use Tests\MockData;

test('show collection works', function()
{
    $user = Models\User::factory()->create(); 
    $collection = Models\Collection::factory()
    ->create();
    $this->be($user);  
        
    $response = $this->json('GET', "/api/v1/collections/{$collection->id}");
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Collection::generateGetResponse());
});

test('show collection does not work with invalid collection id', function()
{
    $user = Models\User::factory()->create(); 
    $collection = Models\Collection::factory()
    ->create();
    $this->be($user);  
        
    $response = $this->json('GET', "/api/v1/collections/-1");
    $response->assertStatus(400);
});

test('user not logged in can view collection', function(){
    $collection = Models\Collection::factory()
    ->create();
        
    $response = $this->json('GET', "/api/v1/collections/{$collection->id}");
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Collection::generateGetResponse());
});