<?php 

use App\Models;
use Tests\MockData;

it('returns 401 when user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->create();

        $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
        $poll = $content->polls()
        ->create([
            'question' => 'question',
            'closes_at' => $date,
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        $optionValues =
        [
            'option 1', 
            'option 2'
        ];
        for ($i = 0; $i < count($optionValues); $i++)
        {
            $options = [
                    'content_poll_id' => $poll->id,
                    'option' => $optionValues[$i],
            ];
            $option = $poll->pollOptions()->create($options);
        }         
            $response = $this->json('DELETE', "/api/v1/polls/{$poll->id}");
            $response->assertStatus(401);
});

test('delete account works', function()
{       

        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->create();

        $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
        $poll = $content->polls()
        ->create([
            'question' => 'question',
            'closes_at' => $date,
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        $optionValues =
        [
            'option 1', 
            'option 2'
        ];
        for ($i = 0; $i < count($optionValues); $i++)
        {
            $options = [
                    'content_poll_id' => $poll->id,
                    'option' => $optionValues[$i],
            ];
            $option = $poll->pollOptions()->create($options);
        }         
            $response = $this->json('DELETE', "/api/v1/polls/{$poll->id}");
            $response->assertStatus(200);
});