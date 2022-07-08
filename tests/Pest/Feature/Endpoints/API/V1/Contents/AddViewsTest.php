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
            'id' => '1',
            'user_id' => $user->id,
            'viewable_type' => 'content',
            'viewable_id' => $content->id,
        ];
        $expected_response_structure = MockData\View::generateAddViewResponse();
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/views", $request);
        dd($response->getData()->data);
        $response->assertStatus(200)
        ->assertJsonStructure($expected_response_structure);

        $this->assertDatabaseHas('views', [
            'id' => $request['id'],
            'user_id' => $user->id,
            'viewable_type' => 'content',
            'viewable_id' => $content->id,

        ]);
});




