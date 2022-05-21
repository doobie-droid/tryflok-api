<?php

namespace Tests\Feature\Console\Commands\Contents;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ComputeChallengeWinner extends TestCase
{
    use DatabaseTransactions;

    public function test_compute_challenge_works_when_there_is_a_clear_winner()
    {
        $referrer = Models\User::factory()->create();
        $moderator = Models\User::factory()
        ->state([
            'referrer_id' => $referrer->id,
        ])
        ->create();
        $winner = Models\User::factory()->state([
            'referrer_id' => $referrer->id,
        ])->create();
        $loser = Models\User::factory()->state([
            'referrer_id' => $referrer->id,
        ])->create();
        $voter1 = Models\User::factory()->create();
        $voter2 = Models\User::factory()->create();
        $voter3 = Models\User::factory()->create();

        $platform_charge = Constants\Constants::NORMAL_CREATOR_CHARGE;

        $pot_size = 30;
        $moderator_share = 10;
        $winner_share = 60;
        $loser_share = 30;
        $voting_window = Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;

        $challenge = Models\Content::factory()
        ->for($moderator, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded(now()->subMinutes($voting_window))
        ->setChallengeContestants([$winner, $loser], ['accept' => 2])
        ->setChallengeDetails($pot_size, 0, $moderator_share, $winner_share, $loser_share)
        ->setChallengeVoters([$voter1, $voter2], $winner->id)
        ->setChallengeVoters([$voter3], $loser->id)
        ->setChallengeContributors([$voter1, $voter2, $voter3], 1000)
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
            'user_id' => $winner->id,
            'amount' => $winner_take,
            'platform_share' => $winner_platform_share,
            'benefactor_share' => $winner_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
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
            'user_id' => $loser->id,
            'amount' => $loser_take,
            'platform_share' => $loser_platform_share,
            'benefactor_share' => $loser_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
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
            'user_id' => $moderator->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => $moderator_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $moderator_referrer_share,
            'revenue_from' => 'referral',
        ]);
    }

    public function test_compute_challenge_works_when_there_is_a_tie()
    {
        $referrer = Models\User::factory()->create();
        $moderator = Models\User::factory()
        ->state([
            'referrer_id' => $referrer->id,
        ])
        ->create();
        $winner = Models\User::factory()->state([
            'referrer_id' => $referrer->id,
        ])->create();
        $loser = Models\User::factory()->state([
            'referrer_id' => $referrer->id,
        ])->create();
        $voter1 = Models\User::factory()->create();
        $voter2 = Models\User::factory()->create();
        $voter3 = Models\User::factory()->create();

        $platform_charge = Constants\Constants::NORMAL_CREATOR_CHARGE;

        $pot_size = 30;
        $moderator_share = 10;
        $winner_share = 60;
        $loser_share = 30;
        $voting_window = Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;

        $challenge = Models\Content::factory()
        ->for($moderator, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded(now()->subMinutes($voting_window))
        ->setChallengeContestants([$winner, $loser], ['accept' => 2])
        ->setChallengeDetails($pot_size, 0, $moderator_share, $winner_share, $loser_share)
        ->setChallengeVoters([$voter1,], $winner->id)
        ->setChallengeVoters([$voter2,], $loser->id)
        ->setChallengeContributors([$voter1, $voter2, $voter3], 1000)
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
            'user_id' => $winner->id,
            'amount' => round($winner_take, 2),
            'platform_share' => round($winner_platform_share, 2),
            'benefactor_share' => round($winner_net, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
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
            'user_id' => $loser->id,
            'amount' => round($loser_take, 2),
            'platform_share' => round($loser_platform_share, 2),
            'benefactor_share' => round($loser_net, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
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
            'user_id' => $moderator->id,
            'amount' => round($moderator_take, 2),
            'platform_share' => round($moderator_platform_share, 2),
            'benefactor_share' => round($moderator_net, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
            'amount' => round($moderator_take, 2),
            'platform_share' => round($moderator_platform_share, 2),
            'benefactor_share' => 0,
            'referral_bonus' => round($moderator_referrer_share, 2),
            'revenue_from' => 'referral',
        ]);
    }

    public function test_compute_challenge_works_when_loser_share_is_zero()
    {
        $referrer = Models\User::factory()->create();
        $moderator = Models\User::factory()
        ->state([
            'referrer_id' => $referrer->id,
        ])
        ->create();
        $winner = Models\User::factory()->state([
            'referrer_id' => $referrer->id,
        ])->create();
        $loser = Models\User::factory()->state([
            'referrer_id' => $referrer->id,
        ])->create();
        $voter1 = Models\User::factory()->create();
        $voter2 = Models\User::factory()->create();
        $voter3 = Models\User::factory()->create();

        $platform_charge = Constants\Constants::NORMAL_CREATOR_CHARGE;

        $pot_size = 30;
        $moderator_share = 10;
        $winner_share = 90;
        $loser_share = 0;
        $voting_window = Constants\Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;

        $challenge = Models\Content::factory()
        ->for($moderator, 'owner')
        ->liveVideo()
        ->isChallenge()
        ->liveEnded(now()->subMinutes($voting_window))
        ->setChallengeContestants([$winner, $loser], ['accept' => 2])
        ->setChallengeDetails($pot_size, 0, $moderator_share, $winner_share, $loser_share)
        ->setChallengeVoters([$voter1, $voter2], $winner->id)
        ->setChallengeVoters([$voter3], $loser->id)
        ->setChallengeContributors([$voter1, $voter2, $voter3], 1000)
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
            'user_id' => $winner->id,
            'amount' => $winner_take,
            'platform_share' => $winner_platform_share,
            'benefactor_share' => $winner_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
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
            'user_id' => $loser->id,
            'amount' => $loser_take,
            'platform_share' => $loser_platform_share,
            'benefactor_share' => $loser_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseMissing('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
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
            'user_id' => $moderator->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => $moderator_net,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'content',
            'revenueable_id' => $challenge->id,
            'user_id' => $referrer->id,
            'amount' => $moderator_take,
            'platform_share' => $moderator_platform_share,
            'benefactor_share' => 0,
            'referral_bonus' => $moderator_referrer_share,
            'revenue_from' => 'referral',
        ]);
    }
}
