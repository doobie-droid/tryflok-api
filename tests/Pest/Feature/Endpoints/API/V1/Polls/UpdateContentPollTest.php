<?php 

use App\Models;
use Tests\MockData;

beforeEach(function()
{
        $this->user = Models\User::factory()->create();
        $this->content = Models\Content::factory()
        ->for($this->user, 'owner')
        ->create();

        $this->poll = Models\ContentPoll::factory()
        ->for($this->user, 'owner')
        ->for($this->content, 'content')
        ->create();

        Models\ContentPollOption::factory()
        ->for($this->poll, 'poll')
        ->count(2)
        ->create();
});


test('poll cannot be updated if user does not own poll', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
            $request = [
            'question' => 'new question',
            'closes_at' => $date,
        ];
            $response = $this->json('PATCH', "/api/v1/polls/{$poll->id}", $request);     
            $response->assertStatus(400);
});

test('poll is not updated with invalid inputs', function()
{
        $this->be($this->user);

        $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
            $request = [
                'question' => 'new question',
                'closes_at' => $date,
            ];
        $response = $this->json('PATCH', "/api/v1/polls/-1", $request);
        $response->assertStatus(400);
});

test('poll is not updated if user is owner but not signed in', function()
{
            $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
            $request = [
                'question' => 'new question',
                'closes_at' => $date,
            ];
            $response = $this->json('PATCH', "/api/v1/polls/{$this->poll->id}", $request);
            $response->assertStatus(401);
});

test('poll is updated with valid inputs if user is owner of poll', function()
{
            $this->be($this->user);

            $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
            $request = [
                'question' => 'new question',
                'closes_at' => $date,
                'content_id' => $this->content->id,
            ];
            $response = $this->json('PATCH', "/api/v1/polls/{$this->poll->id}", $request);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardUpdateResponse());

            //check that content_polls table is populated with the right entries
        $this->assertDatabaseHas('content_polls', [
            'question' => $request['question'],
            'closes_at' => $request['closes_at'],
            'user_id' => $this->content->user_id,
            'content_id' =>$this->content->id,
        ]);
});
test('poll options cannot be updated', function()
{
            $this->be($this->user);

            $date = date('Y-m-d H:i:s', strtotime('+ 5 hours'));
            $request = [
                'question' => 'new question',
                'closes_at' => $date,
                'option' => [
                    'new option 1',
                    'new option 2'
                ],
                'content_id' => $this->content->id,
            ];
            $response = $this->json('PATCH', "/api/v1/polls/{$this->poll->id}", $request);
            $response->assertStatus(400);
});