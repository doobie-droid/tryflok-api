<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Foundation\Testing\WithFaker;
uses(WithFaker::class);

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

        $this->pollOptions = Models\ContentPollOption::factory()
        ->for($this->poll, 'poll')
        ->count(2)
        ->create();

        foreach ($this->pollOptions as $this->option)
            {
                $this->votes = Models\ContentPollVote::factory()
                ->for($this->option, 'pollOption')
                ->for($this->poll, 'poll')
                ->count(5)
                ->create();
            }
});

test('get poll works', function()
{   
                $this->be($this->user);
                $response = $this->json('GET', "/api/v1/polls/{$this->poll->id}");
                $response->assertStatus(200)
                ->assertJsonStructure(MockData\ContentPoll::generateStandardGetResponse());

});

it('returns a 404 with invalid poll ID', function()
{
                $this->be($this->user);
                $response = $this->json('GET', "/api/v1/polls/-1");
                $response->assertStatus(400);
});

test('users who are not signed in can see poll details', function()
{
            $response = $this->json('GET', "/api/v1/polls/{$this->poll->id}");
            $response->assertStatus(200)
            ->assertJsonStructure(MockData\ContentPoll::generateStandardGetResponse());
});