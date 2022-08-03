<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Http\Request;

test('user who is not signed in can vote', function(){
            $user = Models\User::factory()->create();
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $option = Models\ContentPollOption::where('content_poll_id', $poll->id)->first();
            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
            ];

            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardGetVoteResponse());

             //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
            'content_poll_id' => $poll->id,
            'content_poll_option_id' => $option->id,
            ]);
});

test('user who is signed in can vote', function()
{       
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $option = Models\ContentPollOption::where('content_poll_id', $poll->id)->first();
            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
                'voter_id' => $user->id,
            ];

            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardGetVoteResponse());

            //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
            'content_poll_id' => $poll->id,
            'content_poll_option_id' => $option->id,
            'voter_id' => $user->id,
            ]);

});

it('returns a 404 when invalid inputs are used', function()
{   
        $user = Models\User::factory()->create();
        $this->be($user);
        $content = Models\Content::factory()
        ->create();

        $poll = Models\ContentPoll::factory()
        ->for($content, 'content')
        ->create();

        $pollOptions = Models\ContentPollOption::factory()
        ->for($poll, 'poll')
        ->count(2)
        ->create();

        $option = Models\ContentPollOption::where('content_poll_id', $poll->id)->first();
        $request = [
            'content_poll_id' => $poll->id,
            'content_poll_option_id' => $option->id,
            'voter_id' => $user->id,
        ];
        $response = $this->json('POST', "/api/v1/polls/-1/vote", $request);
        $response->assertStatus(400);
});

test('user cannot vote more than once', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $option = Models\ContentPollOption::where('content_poll_id', $poll->id)->first();

            $createVote = Models\ContentPollVote::factory()
            ->for($option, 'pollOption')
            ->for($poll, 'poll')
            ->for($user, 'voter')
            ->create();

            $votes = Models\ContentPollVote::where('content_poll_id', $poll->id)
            ->where('content_poll_option_id', $option->id)->first();

            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
                'voter_id' => $user->id,
                'ip' => $votes->ip,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(302);
});

test('user can change vote', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $option1 = Models\ContentPollOption::where('content_poll_id', $poll->id)->first();
            $option2 = Models\ContentPollOption::where('content_poll_id', $poll->id)->skip(1)->first();

            $createVote = Models\ContentPollVote::factory()
            ->for($option1, 'pollOption')
            ->for($poll, 'poll')
            ->for($user, 'voter')
            ->create();

            $votes = Models\ContentPollVote::where('content_poll_id', $poll->id)
            ->where('content_poll_option_id', $option1->id)->first();

            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option2->id,
                'voter_id' => $user->id,
                'ip' => $votes->ip,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardGetVoteResponse());

            //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option2->id,
                'voter_id' => $user->id,
                'ip' => $votes->ip,
            ]);
});

test('voting ends after polls is closed', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create([
                'closes_at' => now()->subHours(1),
            ]);

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            $option = Models\ContentPollOption::where('content_poll_id', $poll->id)->first();
            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
                'voter_id' => $user->id,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(302);
});