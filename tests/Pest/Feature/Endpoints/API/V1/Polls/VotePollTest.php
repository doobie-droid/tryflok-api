<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Http\Request;


// beforeEach(function()
// {
//             $this->user = Models\User::factory()->create();
//             $this->content = Models\Content::factory()
//             ->for($this->user, 'owner')
//             ->create();

//             $this->poll = Models\ContentPoll::factory()
//             ->for($this->user, 'owner')
//             ->for($this->content, 'content')
//             ->create();

//             $this->pollOption = Models\ContentPollOption::factory()
//             ->for($this->poll, 'poll')
//             ->count(2)
//             ->create();

//             $this->option = $this->poll->pollOptions()->first();
// });
test('user who is not signed in can vote', function(){
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($user, 'owner')
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
            $response->assertStatus(200);

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
            ->for($user, 'owner')
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($user, 'owner')
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
            $response->assertStatus(200);

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
        ->for($user, 'owner')
        ->create();

        $poll = Models\ContentPoll::factory()
        ->for($user, 'owner')
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
            ->for($user, 'owner')
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($user, 'owner')
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            foreach ($pollOptions as $option)
            {
                $votes = Models\ContentPollVote::factory()
                ->for($option, 'pollOption')
                ->for($poll, 'poll')
                ->count(5)
                ->create();
            }

            $option = $option->where('content_poll_id', $poll->id)->first();
            $votes = Models\ContentPollVote::where('content_poll_id', $poll->id)->where('content_poll_option_id', $option->id)->first();
            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
                'voter_id' => $user->id,
                'ip' => $votes->ip,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(302);
})->only();

test('user can change vote', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($user, 'owner')
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            foreach ($pollOptions as $option)
            {
                $votes = Models\ContentPollVote::factory()
                ->for($option, 'pollOption')
                ->for($poll, 'poll')
                ->count(5)
                ->create();
            }

            $option = $option->where('content_poll_id', $poll->id)->first();
            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
                'voter_id' => $user->id,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(302);
})->skip();

test('voting ends after polls is closed', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($user, 'owner')
            ->for($content, 'content')
            ->create();

            $pollOptions = Models\ContentPollOption::factory()
            ->for($poll, 'poll')
            ->count(2)
            ->create();

            foreach ($pollOptions as $option)
            {
                $votes = Models\ContentPollVote::factory()
                ->for($option, 'pollOption')
                ->for($poll, 'poll')
                ->count(5)
                ->create();
            }

            $option = $option->where('content_poll_id', $poll->id)->first();
            $request = [
                'content_poll_id' => $poll->id,
                'content_poll_option_id' => $option->id,
                'voter_id' => $user->id,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$poll->id}/vote", $request);
            $response->assertStatus(302);
})->skip();