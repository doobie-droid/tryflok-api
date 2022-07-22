<?php 

use App\Models;

test('create polls works', function()
{
       // $user = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->create();


        $poll = $content->polls()->create();

        $request = [
            'question' => 'question',
            'closes_at' => '26/07/2022',
            'content_id' => $content->id,
            'user_id' => $content->user_id,
            'option' => 'options',
            'content_poll_id' => $poll->id,
        ];

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/polls", $request);
        dd($reponse);
//        $response->assertStatus(200);
})->skip();