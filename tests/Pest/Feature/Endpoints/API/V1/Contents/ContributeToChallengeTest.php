<?php

use App\Models;

beforeEach(function () {
    $this->challenger = Models\User::factory()->create();
        $this->contestant1 = Models\User::factory()->create();
        $this->contestant2 = Models\User::factory()->create();
        $this->contributor = Models\User::factory()->create();
        Models\Wallet::factory()
        ->for($this->contributor, 'walletable')
        ->create();
        $this->be($this->contributor);
});

test('contribute to challenge fails for invalid inputs', function()
{
    $pot_size = 1000;
        $minimum_contribution = 10;

        // when pot size is 0
        $content1 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails()
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content1->id}/contribute-to-challenge", [
            'amount' => 500,
        ]);
        $response->assertStatus(400);

        // when one contestant has not accepted the challenge
        $content2 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 1])
        ->setChallengeDetails($pot_size, $minimum_contribution)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content2->id}/contribute-to-challenge", [
            'amount' => 500,
        ]);
        $response->assertStatus(400);

        // when the live has ended
        $content3 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size, $minimum_contribution)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content3->id}/contribute-to-challenge", [
            'amount' => 500,
        ]);
        $response->assertStatus(400);
        // when contribution amount is less than minimum amount
        $content4 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size, $minimum_contribution)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content4->id}/contribute-to-challenge", [
            'amount' => 5,
        ]);
        $response->assertStatus(400);
        // when contribution amount is more than user's wallet balance
        $content5 = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size, $minimum_contribution)
        ->create();
        $response = $this->json('PATCH', "/api/v1/contents/{$content5->id}/contribute-to-challenge", [
            'amount' => (int) $this->contributor->wallet->balance + 1,
        ]);
        $response->assertStatus(400);
});

test('contribute to challenge works', function()
{
    $pot_size = 1000;
        $minimum_contribution = 10;
        $initial_wallet_balance = $this->contributor->wallet->balance;
        $first_contribution = 500;
        $second_contribution = 5;

        $content = Models\Content::factory()
        ->for($this->challenger, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->futureScheduledDate()
        ->setChallengeContestants([$this->contestant1, $this->contestant2], ['accept' => 2])
        ->setChallengeDetails($pot_size, $minimum_contribution)
        ->create();

        //when first contribution is sent
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/contribute-to-challenge", [
            'amount' => $first_contribution,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_challenge_contributions', [
            'content_id' => $content->id,
            'user_id' => $this->contributor->id,
            'amount' => $first_contribution,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->contributor->wallet->id,
            'amount' => $first_contribution,
            'transaction_type' => 'deduct',
            'balance' => $initial_wallet_balance - $first_contribution,
            'details' => "Withdrawal from wallet to contribute to {$content->title} challenge",
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->contributor->wallet->id,
            'balance' => $initial_wallet_balance - $first_contribution,
        ]);
        // when subsequent contribution is sent
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/contribute-to-challenge", [
            'amount' => $second_contribution,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('content_challenge_contributions', [
            'content_id' => $content->id,
            'user_id' => $this->contributor->id,
            'amount' => $first_contribution + $second_contribution,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->contributor->wallet->id,
            'amount' => $second_contribution,
            'transaction_type' => 'deduct',
            'balance' => $initial_wallet_balance - ($first_contribution + $second_contribution),
            'details' => "Withdrawal from wallet to contribute to {$content->title} challenge",
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $this->contributor->wallet->id,
            'balance' => $initial_wallet_balance - ($first_contribution + $second_contribution),
        ]);
});