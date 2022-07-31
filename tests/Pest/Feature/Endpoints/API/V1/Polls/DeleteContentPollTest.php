<?php 

use App\Models;
use Tests\MockData;

it('returns 401 when user is not signed in', function()
{
            $user = Models\User::factory()->create();
            $content = Models\Content::factory()
            ->for($user, 'owner')
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($user, 'owner')
            ->for($content, 'content')
            ->create();

            Models\ContentPollOption::factory()
            ->for($poll, 'content_poll')
            ->count(2)
            ->create();

            $response = $this->json('DELETE', "/api/v1/polls/{$poll->id}");
            $response->assertStatus(401);
});

test('delete poll does not work if user is not the owner', function()
{
            $user = Models\User::factory()->create();
            $this->be($user);
            $content = Models\Content::factory()
            ->create();

            $poll = Models\ContentPoll::factory()
            ->for($content, 'content')
            ->create();

            Models\ContentPollOption::factory()
            ->for($poll, 'content_poll')
            ->count(2)
            ->create();

            $response = $this->json('DELETE', "/api/v1/polls/{$poll->id}");
            $response->assertStatus(400);
});

test('delete poll does not work with invalid input', function()
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

            Models\ContentPollOption::factory()
            ->for($poll, 'content_poll')
            ->count(2)
            ->create();

            $response = $this->json('DELETE', "/api/v1/polls/-1");
            $response->assertStatus(400);
});

test('delete poll works', function()
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

            Models\ContentPollOption::factory()
            ->for($poll, 'content_poll')
            ->count(2)
            ->create();

            $response = $this->json('DELETE', "/api/v1/polls/{$poll->id}");
            $response->assertStatus(200);
});