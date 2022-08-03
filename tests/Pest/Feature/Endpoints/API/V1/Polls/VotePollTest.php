<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Http\Request;

beforeEach(function()
{
            $this->user = Models\User::factory()->create();
            $this->content = Models\Content::factory()
            ->create();

            $this->poll = Models\ContentPoll::factory()
            ->for($this->content, 'content')
            ->create();

            $this->pollOptions = Models\ContentPollOption::factory()
            ->for($this->poll, 'poll')
            ->count(2)
            ->create();

            $this->option = Models\ContentPollOption::where('content_poll_id', $this->poll->id)->first();

            $this->request = [
                'content_poll_id' => $this->poll->id,
                'content_poll_option_id' => $this->option->id,
            ];
});


test('user who is not signed in can vote', function(){

            $response = $this->json('POST', "/api/v1/polls/{$this->poll->id}/vote", $this->request);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardGetVoteResponse());

             //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
            'content_poll_id' => $this->poll->id,
            'content_poll_option_id' => $this->option->id,
            ]);
});

test('user who is signed in can vote', function()
{       
            $this->be($this->user);

            $response = $this->json('POST', "/api/v1/polls/{$this->poll->id}/vote", $this->request);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardGetVoteResponse());

            //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
            'content_poll_id' => $this->poll->id,
            'content_poll_option_id' => $this->option->id,
            'voter_id' => $this->user->id,
            ]);

});

it('returns a 404 when invalid inputs are used', function()
{   
        $this->be($this->user);
        $response = $this->json('POST', "/api/v1/polls/-1/vote", $this->request);
        $response->assertStatus(400);
});

test('user who is signed cannot vote more than once', function()
{
            $this->be($this->user);

            $createVote = Models\ContentPollVote::factory()
            ->for($this->option, 'pollOption')
            ->for($this->poll, 'poll')
            ->for($this->user, 'voter')
            ->create();

            $votes = Models\ContentPollVote::where('content_poll_id', $this->poll->id)
            ->where('content_poll_option_id', $this->option->id)->first();

            $response = $this->json('POST', "/api/v1/polls/{$this->poll->id}/vote", $this->request);
            $response->assertStatus(302);
});

test('voting ends after polls is closed', function()
{
            $this->be($this->user);

            $poll = Models\ContentPoll::factory()
            ->for($this->content, 'content')
            ->create([
                'closes_at' => now()->subHours(1),
            ]);

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $this->request);
            $response->assertStatus(302);
});