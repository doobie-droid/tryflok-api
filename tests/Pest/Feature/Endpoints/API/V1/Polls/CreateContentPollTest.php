<?php 

use App\Models;
use Tests\MockData;


it('returns a 401 error when a user is not signed in', function()
{
            $user = Models\User::factory()->create();
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();

            $request = [
                'question' => 'question',
                'closes_at' => now()->addHours(5),
                'content_id' => $content->id,
                'user_id' => $content->user_id,
                'option' => [
                    0 => 'option 1',
                    1 =>'option 2',
                ],
            ];
            $response = $this->json('POST', "/api/v1/contents/{$content->id}/poll", $request);     
            $response->assertStatus(401);
});

test('poll is not created if signed in user is not the owner of the content', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $request = [
                'question' => 'question',
                'closes_at' => now()->addHours(5),
                'content_id' => $content->id,
                'user_id' => $content->user_id,
                'option' => [
                    0 => 'option 1',
                    1 =>'option 2',
                ],
            ];
            $response = $this->json('POST', "/api/v1/contents/{$content->id}/poll", $request);     
            $response->assertStatus(400);
});

test('poll is created if signed in user is owner of the content', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->for($user, 'owner')
        ->create();

        $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
        $request = [
            'question' => 'question',
            'closes_at' => $date,
            'user_id' => $content->user_id,
            'option' => [
                0 => 'option 1',
                1 =>'option 2',
            ],
        ];
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/poll", $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\ContentPoll::generateStandardCreateResponse());

        //check that content_polls table is populated with the right entries
        $this->assertDatabaseHas('content_polls', [
            'question' => $request['question'],
            'closes_at' => $request['closes_at'],
            'user_id' => $content->user_id,
            'content_id' =>$content->id,
        ]);

        //check that submitted options are in the content_poll_options table
        $poll = $content->polls()->first();
        $this->assertDatabaseHas('content_poll_options', [
        'content_poll_id' => $poll->id,
        'option' => $request['option'][0],
        ]);

        $this->assertDatabaseHas('content_poll_options', [
        'content_poll_id' => $poll->id,
        'option' => $request['option'][1],
        ]);
        
        //check that there are no duplicate options
        $option_1_count = Models\ContentPollOption::where('option',  $request['option'][0])->count();
        $option_2_count = Models\ContentPollOption::where('option',  $request['option'][1])->count();

        $this->assertEquals(1, $option_1_count);
        $this->assertEquals(1, $option_2_count);
        
});
