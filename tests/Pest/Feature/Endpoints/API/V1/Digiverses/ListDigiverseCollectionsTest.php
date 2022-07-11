<?php 

use App\Constants;
use App\Models;
use Tests\MockData;

test('list digiverse collection works', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);   
        
        $tag = Models\Tag::factory()->create();
        $content = Models\Content::factory()->noDigiverse()->create();
       $collection =  Models\Collection::factory()
                        ->digiverse()
                        ->setTags([$tag])
                        ->setContents([$content])
                        ->count(4)
                        ->create();

        // $digiverse = Models\Collection::factory()->digiverse()->create();
        // $collection = $digiverse->collections()->whereNull('archived_at');

        $response = $this->json('GET', "/api/v1/digiverses/{$collection_id}/collections");
        $response->assertStatus(200);
        // $collections = $response->getData()->data->collections;
        // $this->assertEquals($collections, []);
});
        