<?php 

use App\Models;
use Tests\MockData;

test('add views works', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();
        
        
        $request = [
            'id' => 1,
            'user_id' => $user->id,
            'viewable_type' => 'content',
            'viewable_id' => $content->id,
        ];
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/views", $request);
        $response->assertStatus(200);

        $this->assertDatabaseHas('views', [
            'user_id' => $user->id,
            'viewable_type' => 'content',
            'viewable_id' => $content->id,

        ]);
});




