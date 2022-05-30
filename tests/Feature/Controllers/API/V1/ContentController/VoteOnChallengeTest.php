<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VoteOnChallengeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vote_on_challenge_fails_for_invalid_input()
    {
        $challenger = Models\User::factory()->create();
        $contestant1 = Models\User::factory()->create();
        $contestant2 = Models\User::factory()->create();
        $user1 = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();

        $pot_size = 1000;

        $this->be($user1);
        // when invalid user id is passed for contestant
        $content1 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content1->id}/vote-on-challenge", [
            'contestant' => 'weererer',
        ]);
        $response->assertStatus(400);
        // when id passed for contestant is not a contestant in the challenge
        $content2 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content2->id}/vote-on-challenge", [
            'contestant' => $user2->id,
        ]);
        $response->assertStatus(400);
        // when content is not a challenge
        $content3 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content3->id}/vote-on-challenge", [
            'contestant' => $contestant1->id,
        ]);
        $response->assertStatus(400);
        // when challenge has not ended
        $content4 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content4->id}/vote-on-challenge", [
            'contestant' => $contestant1->id,
        ]);
        $response->assertStatus(400);
        // when it's been more than X minutes since the challenge ended
        $content5 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded(now()->subMinutes(Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES + 1))
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content5->id}/vote-on-challenge", [
            'contestant' => $contestant1->id,
        ]);
        $response->assertStatus(400);
        // when user has not contributed to the pot
        $content6 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content6->id}/vote-on-challenge", [
            'contestant' => $contestant1->id,
        ]);
        $response->assertStatus(400);
        // when user has already cast vote
        $content7 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size)
        ->setChallengeContributors([$user1])
        ->setChallengeVoters([$user1], $contestant1->id)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content7->id}/vote-on-challenge", [
            'contestant' => $contestant1->id,
        ]);
        $response->assertStatus(400);
    }

    public function test_vote_on_challenge_works()
    {
        $challenger = Models\User::factory()->create();
        $contestant1 = Models\User::factory()->create();
        $contestant2 = Models\User::factory()->create();
        $user = Models\User::factory()->create();

        $pot_size = 1000;

        $this->be($user);
        // when pot is not 0
        $content1 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size)
        ->setChallengeContributors([$user])
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content1->id}/vote-on-challenge", [
            'contestant' => $contestant1->id,
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('content_challenge_votes', [
            'content_id' => $content1->id,
            'voter_id' => $user->id,
            'contestant_id' => $contestant1->id,
        ]);

        // when pot is 0
        $content2 = Models\Content::factory()
        ->for($challenger, 'owner')
        ->liveVideo()
        ->liveEnded()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$contestant1, $contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content2->id}/vote-on-challenge", [
            'contestant' => $contestant2->id,
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('content_challenge_votes', [
            'content_id' => $content2->id,
            'voter_id' => $user->id,
            'contestant_id' => $contestant2->id,
        ]);
    }
}
