<?php 

use App\Constants;
use App\Models;

beforeEach(function()
{
        $this->challenger = Models\User::factory()->create();
        $this->contestant1 = Models\User::factory()->create();
        $this->contestant2 = Models\User::factory()->create();
});

test('vote on challenge fails for invalid input', function()
{
        $user1 = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();

        $pot_size = 1000;

        $this->be($user1);
        // when invalid user id is passed for contestant
        $content1 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content1->id}/vote-on-challenge", [
            'contestant' => 'weererer',
        ]);
        $response->assertStatus(400);
        // when id passed for contestant is not a contestant in the challenge
        $content2 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content2->id}/vote-on-challenge", [
            'contestant' => $user2->id,
        ]);
        $response->assertStatus(400);
        // when content is not a challenge
        $content3 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content3->id}/vote-on-challenge", [
            'contestant' => $this->contestant1->id,
        ]);
        $response->assertStatus(400);
        // when challenge has not ended
        $content4 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content4->id}/vote-on-challenge", [
            'contestant' => $this->contestant1->id,
        ]);
        $response->assertStatus(400);
        // when it's been more than X minutes since the challenge ended
        $content5 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded(now()->subMinutes(Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES + 1))
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content5->id}/vote-on-challenge", [
            'contestant' => $this->contestant1->id,
        ]);
        $response->assertStatus(400);
        // when user has not contributed to the pot
        $content6 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content6->id}/vote-on-challenge", [
            'contestant' => $this->contestant1->id,
        ]);
        $response->assertStatus(400);
        // when user has already cast vote
        $content7 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size)
        ->setChallengeContributors([$user1])
        ->setChallengeVoters([$user1], $this->contestant1->id)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content7->id}/vote-on-challenge", [
            'contestant' => $this->contestant1->id,
        ]);
        $response->assertStatus(400);
});

test('vote on challenge works', function()
{
        $user = Models\User::factory()->create();

        $pot_size = 1000;

        $this->be($user);
        // when pot is not 0
        $content1 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size)
        ->setChallengeContributors([$user])
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content1->id}/vote-on-challenge", [
            'contestant' => $this->contestant1->id,
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('content_challenge_votes', [
            'content_id' => $content1->id,
            'voter_id' => $user->id,
            'contestant_id' => $this->contestant1->id,
        ]);

        // when pot is 0
        $content2 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content2->id}/vote-on-challenge", [
            'contestant' => $this->contestant2->id,
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('content_challenge_votes', [
            'content_id' => $content2->id,
            'voter_id' => $user->id,
            'contestant_id' => $this->contestant2->id,
        ]);
});


