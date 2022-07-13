<?php

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

beforeEach(function ()
{
    $this->referrer = Models\User::factory()->create();
        $this->moderator = Models\User::factory()
        ->state([
            'referrer_id' => $this->referrer->id,
        ])
        ->create();
        $this->winner = Models\User::factory()->state([
            'referrer_id' => $this->referrer->id,
        ])->create();
        $this->loser = Models\User::factory()->state([
            'referrer_id' => $this->referrer->id,
        ])->create();
        $this->voter1 = Models\User::factory()->create();
        $this->voter2 = Models\User::factory()->create();
        $this->voter3 = Models\User::factory()->create();
});

test('compute challenge works when there is a clear winner', function(){
    $platform_charge = Constants\Constants::NORMAL_CREATOR_CHARGE;

        $pot_size = 30;
        $moderator_share = 10;
        $winner_share = 60;
        $loser_share = 30;
        $voting_window = Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;

        $challenge = Models\Content::factory()
        ->for($this->moderator, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded(now()->subMinutes($voting_window))
        ->setChallengeContestants([$this->winner, $this->loser], ['accept' => 2])
        ->setChallengeDetails($pot_size, 0, $moderator_share, $winner_share, $loser_share)
        ->setChallengeVoters([$this->voter1, $this->voter2], $this->winner->id)
        ->setChallengeVoters([$this->voter3], $this->loser->id)
        ->setChallengeContributors([$this->voter1, $this->voter2, $this->voter3], 1000)
        ->create();

        $this->artisan('flok:compute-challenge-winner')->assertSuccessful();

        $this->assertDatabaseHas('contents', [
            'id' => $challenge->id,
            'challenge_winner_computed' => 1,
        ]);

        $winner_take = bcdiv($winner_share * $pot_size, 100, 2);
        $winner_net = bcmul($winner_take, 1 - $platform_charge, 2);
        $winner_platform_share = bcmul($winner_take, $platform_charge, 2);
        $winner_referrer_share = bcmul($winner_take, Constants\Constants::REFERRAL_BONUS, 2);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->winner->id,
            'amount' => $winner_take,
            'platform_share' => $winner_platform_share,
            'benefactor_share' => $winner_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => $winner_take,
            'platform_share' => $winner_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $winner_referrer_share,
            'revenue_from' => 'referral',
        ]);

        $loser_take = bcdiv($loser_share * $pot_size, 100, 2);
        $loser_net = bcmul($loser_take, 1 - $platform_charge, 2);
        $loser_platform_share = bcmul($loser_take, $platform_charge, 2);
        $loser_referrer_share = bcmul($loser_take, Constants\Constants::REFERRAL_BONUS, 2);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->loser->id,
            'amount' => $loser_take,
            'platform_share' => $loser_platform_share,
            'benefactor_share' => $loser_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => $loser_take,
            'platform_share' => $loser_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $loser_referrer_share,
            'revenue_from' => 'referral',
        ]);

        $moderator_take = bcdiv($moderator_share * $pot_size, 100, 2);
        $moderator_net = bcmul($moderator_take, 1 - $platform_charge, 2);
        $moderator_platform_share = bcmul($moderator_take, $platform_charge, 2);
        $moderator_referrer_share = bcmul($moderator_take, Constants\Constants::REFERRAL_BONUS, 2);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->moderator->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => $moderator_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $moderator_referrer_share,
            'revenue_from' => 'referral',
        ]);
});

test('compute challenge works when there is a tie', function()
{
    $platform_charge = Constants\Constants::NORMAL_CREATOR_CHARGE;

        $pot_size = 30;
        $moderator_share = 10;
        $winner_share = 60;
        $loser_share = 30;
        $voting_window = Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;

        $challenge = Models\Content::factory()
        ->for($this->moderator, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded(now()->subMinutes($voting_window))
        ->setChallengeContestants([$this->winner, $this->loser], ['accept' => 2])
        ->setChallengeDetails($pot_size, 0, $moderator_share, $winner_share, $loser_share)
        ->setChallengeVoters([$this->voter1], $this->winner->id)
        ->setChallengeVoters([$this->voter2], $this->loser->id)
        ->setChallengeContributors([$this->voter1, $this->voter2, $this->voter3], 1000)
        ->create();

        $this->artisan('flok:compute-challenge-winner')->assertSuccessful();

        $this->assertDatabaseHas('contents', [
            'id' => $challenge->id,
            'challenge_winner_computed' => 1,
        ]);

        $winner_take = bcdiv(45 * $pot_size, 100, 6);
        $winner_net = bcmul($winner_take, 1 - $platform_charge, 6);
        $winner_platform_share = bcmul($winner_take, $platform_charge, 6);
        $winner_referrer_share = bcmul($winner_take, Constants\Constants::REFERRAL_BONUS, 6);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->winner->id,
            'amount' => round($winner_take, 2),
            'platform_share' => round($winner_platform_share, 2),
            'benefactor_share' => round($winner_net, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => round($winner_take, 2),
            'platform_share' => round($winner_platform_share, 2),
            'benefactor_share' => 0,
            'referral_bonus' => round($winner_referrer_share, 2),
            'revenue_from' => 'referral',
        ]);

        $loser_take = bcdiv(45 * $pot_size, 100, 6);
        $loser_net = bcmul($loser_take, 1 - $platform_charge, 6);
        $loser_platform_share = bcmul($loser_take, $platform_charge, 6);
        $loser_referrer_share = bcmul($loser_take, Constants\Constants::REFERRAL_BONUS, 6);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->loser->id,
            'amount' => round($loser_take, 2),
            'platform_share' => round($loser_platform_share, 2),
            'benefactor_share' => round($loser_net, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => round($loser_take, 2),
            'platform_share' => round($loser_platform_share, 2),
            'benefactor_share' => 0,
            'referral_bonus' => round($loser_referrer_share, 2),
            'revenue_from' => 'referral',
        ]);

        $moderator_take = bcdiv($moderator_share * $pot_size, 100, 6);
        $moderator_net = bcmul($moderator_take, 1 - $platform_charge, 6);
        $moderator_platform_share = bcmul($moderator_take, $platform_charge, 6);
        $moderator_referrer_share = bcmul($moderator_take, Constants\Constants::REFERRAL_BONUS, 6);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->moderator->id,
            'amount' => round($moderator_take, 2),
            'platform_share' => round($moderator_platform_share, 2),
            'benefactor_share' => round($moderator_net, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => round($moderator_take, 2),
            'platform_share' => round($moderator_platform_share, 2),
            'benefactor_share' => 0,
            'referral_bonus' => round($moderator_referrer_share, 2),
            'revenue_from' => 'referral',
        ]);
});

test('compute challenge works when loser share is zero', function()
{
    $platform_charge = Constants\Constants::NORMAL_CREATOR_CHARGE;

        $pot_size = 30;
        $moderator_share = 10;
        $winner_share = 90;
        $loser_share = 0;
        $voting_window = Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;

        $challenge = Models\Content::factory()
        ->for($this->moderator, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded(now()->subMinutes($voting_window))
        ->setChallengeContestants([$this->winner, $this->loser], ['accept' => 2])
        ->setChallengeDetails($pot_size, 0, $moderator_share, $winner_share, $loser_share)
        ->setChallengeVoters([$this->voter1, $this->voter2], $this->winner->id)
        ->setChallengeVoters([$this->voter3], $this->loser->id)
        ->setChallengeContributors([$this->voter1, $this->voter2, $this->voter3], 1000)
        ->create();

        $this->artisan('flok:compute-challenge-winner')->assertSuccessful();

        $this->assertDatabaseHas('contents', [
            'id' => $challenge->id,
            'challenge_winner_computed' => 1,
        ]);

        $winner_take = bcdiv($winner_share * $pot_size, 100, 2);
        $winner_net = bcmul($winner_take, 1 - $platform_charge, 2);
        $winner_platform_share = bcmul($winner_take, $platform_charge, 2);
        $winner_referrer_share = bcmul($winner_take, Constants\Constants::REFERRAL_BONUS, 2);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->winner->id,
            'amount' => $winner_take,
            'platform_share' => $winner_platform_share,
            'benefactor_share' => $winner_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => $winner_take,
            'platform_share' => $winner_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $winner_referrer_share,
            'revenue_from' => 'referral',
        ]);

        $loser_take = bcdiv($loser_share * $pot_size, 100, 2);
        $loser_net = bcmul($loser_take, 1 - $platform_charge, 2);
        $loser_platform_share = bcmul($loser_take, $platform_charge, 2);
        $loser_referrer_share = bcmul($loser_take, Constants\Constants::REFERRAL_BONUS, 2);
        $this->assertDatabaseMissing('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->loser->id,
            'amount' => $loser_take,
            'platform_share' => $loser_platform_share,
            'benefactor_share' => $loser_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseMissing('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => $loser_take,
            'platform_share' => $loser_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $loser_referrer_share,
            'revenue_from' => 'referral',
        ]);

        $moderator_take = bcdiv($moderator_share * $pot_size, 100, 2);
        $moderator_net = bcmul($moderator_take, 1 - $platform_charge, 2);
        $moderator_platform_share = bcmul($moderator_take, $platform_charge, 2);
        $moderator_referrer_share = bcmul($moderator_take, Constants\Constants::REFERRAL_BONUS, 2);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->moderator->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => $moderator_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $this->referrer->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $moderator_referrer_share,
            'revenue_from' => 'referral',
        ]);
});