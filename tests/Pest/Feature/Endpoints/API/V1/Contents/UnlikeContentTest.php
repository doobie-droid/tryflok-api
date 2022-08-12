<?php 

use App\Models;
use Tests\MockData;

test('unlike content works', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $content->likes()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->json('DELETE', "/api/v1/contents/{$content->id}/like");     
        $response->assertStatus(200);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_id' => $content->id,
        ]);
});