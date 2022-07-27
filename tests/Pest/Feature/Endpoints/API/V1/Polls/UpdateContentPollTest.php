<?php 

use App\Models;
use Tests\MockData;


test('poll cannot be updated if user does not own poll', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();
            
            $poll = Models\ContentPoll::factory()->create();

            $request = [
                'question' => 'new question',
                'closes_at' => now()->addHours(5),
                'option' => [
                    0 => 'new option 1',
                    1 =>'option 2',
                ],
            ];
            $response = $this->json('PATCH', "/api/v1/polls/{$poll->id}", $request);     
            $response->assertStatus(400);
});

test('poll is not updated with invalid inputs', function()
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

        $request = [
            'question' => 'new question',
            'closes_at' => $date,
            'option' => [
                'new option 1',
                'new option 2'
            ]
        ];
        $response = $this->json('PATCH', "/api/v1/polls/-1", $request);
        $response->assertStatus(400);
});

test('poll is not updated if user is owner but not signed in', function()
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

            $request = [
                'question' => 'new question',
                'closes_at' => $date,
                'option' => [
                    'new option 1',
                    'new option 2'
                ]
            ];
            $response = $this->json('PATCH', "/api/v1/polls/{$poll->id}", $request);
            $response->assertStatus(401);
});

test('poll is updated with valid inputs if user is owner of poll', function()
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

            $request = [
                'question' => 'new question',
                'closes_at' => $date,
                'option' => [
                    'new option 1',
                    'new option 2'
                ]
            ];
            $response = $this->json('PATCH', "/api/v1/polls/{$poll->id}", $request);
            $response->assertStatus(200);
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