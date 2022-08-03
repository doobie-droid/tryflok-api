<?php 

use App\Models;
use Tests\MockData;

beforeEach(function()
{
    $this->user = Models\User::factory()->create();
    $this->content = Models\Content::factory()
    ->for($this->user, 'owner')
    ->create();

    $this->date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
    $this->request = [
        'question' => 'question',
        'closes_at' => $this->date,
        'content_id' => $this->content->id,
        'user_id' => $this->content->user_id,
        'options' => [
                0 => 'option 1',
                1 =>'option 2',
            ],
            ];

});


it('returns a 401 error when a user is not signed in', function()
{
            $response = $this->json('POST', "/api/v1/contents/{$this->content->id}/poll", $this->request);     
            $response->assertStatus(401);
});

test('poll is not created if signed in user is not the owner of the content', function()
{
            $this->be($this->user);
            $content = Models\Content::factory()
            ->create();

            $request = [
                'question' => 'question',
                'closes_at' => now()->addHours(5),
                'content_id' => $content->id,
                'user_id' => $content->user_id,
                'options' => [
                    0 => 'option 1',
                    1 =>'option 2',
                ],
            ];
            $response = $this->json('POST', "/api/v1/contents/{$content->id}/poll", $request);     
            $response->assertStatus(400);
});

test('poll is created if signed in user is owner of the content', function()
{
        $this->be($this->user);
        $response = $this->json('POST', "/api/v1/contents/{$this->content->id}/poll", $this->request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\ContentPoll::generateStandardCreateResponse());

        //check that content_polls table is populated with the right entries
        $this->assertDatabaseHas('content_polls', [
            'question' => $this->request['question'],
            'closes_at' => $this->request['closes_at'],
            'user_id' => $this->content->user_id,
            'content_id' =>$this->content->id,
        ]);

        //check that submitted options are in the content_poll_options table
        $poll = $this->content->polls()->first();
        $this->assertDatabaseHas('content_poll_options', [
        'content_poll_id' => $poll->id,
        'option' => $this->request['options'][0],
        ]);

        $this->assertDatabaseHas('content_poll_options', [
        'content_poll_id' => $poll->id,
        'option' => $this->request['options'][1],
        ]);
        
        //check that there are no duplicate options
        $option_1_count = Models\ContentPollOption::where('option',  $this->request['options'][0])->count();
        $option_2_count = Models\ContentPollOption::where('option',  $this->request['options'][1])->count();

        $this->assertEquals(1, $option_1_count);
        $this->assertEquals(1, $option_2_count);
        
});

test('poll is not created if options has duplicate values', function()
{
        $this->be($this->user);

        $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
        $request = [
            'question' => 'question',
            'closes_at' => $date,
            'user_id' => $this->content->user_id,
            'options' => [
                0 => 'option 1',
                1 => 'option 1',
            ],
        ];
        $response = $this->json('POST', "/api/v1/contents/{$this->content->id}/poll", $request);
        $response->assertStatus(400);
});
