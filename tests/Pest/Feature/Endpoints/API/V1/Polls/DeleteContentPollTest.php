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

        $this->options = Models\ContentPollOption::factory()
        ->for($this->poll, 'poll')
        ->count(2)
        ->create();
});

it('returns 401 when user is not signed in', function()
{

            $response = $this->json('DELETE', "/api/v1/polls/{$this->poll->id}");
            $response->assertStatus(401);
});

test('delete poll does not work if user is not the owner', function()
{
            $this->be($this->user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $response = $this->json('DELETE', "/api/v1/polls/{$poll->id}");
            $response->assertStatus(400);
});

test('delete poll does not work with invalid input', function()
{
            $this->be($this->user);

            $response = $this->json('DELETE', "/api/v1/polls/-1");
            $response->assertStatus(400);
});

test('delete poll works', function()
{       
            $this->be($this->user);

            $option = $this->poll->pollOptions()->first();

            $response = $this->json('DELETE', "/api/v1/polls/{$this->poll->id}");
            $response->assertStatus(200);
            $this->assertDatabaseMissing('content_polls', [
                'question' => $this->poll->question,
                'closes_at' => $this->poll->closes_at,
                'user_id' => $this->content->user_id,
                'content_id' =>$this->content->id,
            ]);
       
            $this->assertDatabaseMissing('content_poll_options', [
            'content_poll_id' => $this->poll->id,
            'option' => $option['option'][0],
            ]);

            $this->assertDatabaseMissing('content_poll_options', [
            'content_poll_id' => $this->poll->id,
            'option' => $option['option'][1],
            ]);
});