<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Http\Request;


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

            $this->pollOption = Models\ContentPollOption::factory()
            ->for($this->poll, 'poll')
            ->count(2)
            ->create();

            $this->option = $this->poll->pollOptions()->first();
});
test('vote works without voter id', function(){
            $this->be($this->user);
            $request = [
                'content_poll_id' => $this->poll->id,
                'content_poll_option_id' => $this->option->id,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$this->poll->id}/vote", $request);
            $response->assertStatus(200);

             //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
            'content_poll_id' => $this->poll->id,
            'content_poll_option_id' => $this->option->id,
 ]);

});
test('user who is signed in can vote', function()
{       
            $this->be($this->user);
            $request = [
                'content_poll_id' => $this->poll->id,
                'content_poll_option_id' => $this->option->id,
                'voter_id' => $this->user->id,
            ];
            $response = $this->json('POST', "/api/v1/polls/{$this->poll->id}/vote", $request);
            $response->assertStatus(200);

             //check that submitted votes are in the content_poll_votes table
            $this->assertDatabaseHas('content_poll_votes', [
            'content_poll_id' => $this->poll->id,
            'content_poll_option_id' => $this->option->id,
            'voter_id' => $this->user->id,
            ]);

});

test('user who is not signed in cannot vote', function()
{
        $request = [
            'content_poll_id' => $this->poll->id,
            'content_poll_option_id' => $this->option->id,
            'voter_id' => $this->user->id,
        ];
        $response = $this->json('POST', "/api/v1/polls/{$this->poll->id}/vote", $request);
        $response->assertStatus(401);
});

it('returns a 404 when invalid inputs are used', function()
{   
        $this->be($this->user);
        $request = [
            'content_poll_id' => $this->poll->id,
            'content_poll_option_id' => $this->option->id,
            'voter_id' => $this->user->id,
        ];
        $response = $this->json('POST', "/api/v1/polls/-1/vote", $request);
        $response->assertStatus(400);
});

test('user cannot vote more than once', function()
{

})->skip();

test('voting ends after polls is closed', function()
{
})->skip();