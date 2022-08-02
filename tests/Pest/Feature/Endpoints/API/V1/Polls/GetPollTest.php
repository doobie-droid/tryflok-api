<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Foundation\Testing\WithFaker;
uses(WithFaker::class);

test('get poll works', function()
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
                    ->for($option, 'votes')
                    ->for($poll, 'poll')
                    ->count(5)
                    ->create();
                }
              
               
                $response = $this->json('GET', "/api/v1/polls/{$poll->id}");
                $response->assertStatus(200);

                foreach ($votes as $vote)
                {
                        $this->assertDatabaseHas('content_poll_votes', [
                        'content_poll_id' => $poll->id,
                        'content_poll_option_id' => $option->id,
                        ]);
                }

});