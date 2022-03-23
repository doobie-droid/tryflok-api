<?php

namespace App\Jobs\Content;

use App\Constants\Constants;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeChallengeWinner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $challenge;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($challenge)
    {
        $this->challenge = $challenge;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $winner_share = (float) $this->challenge->metas()->where('key', 'winner_share')->first()->value;
        $moderator_share = (float) $this->challenge->metas()->where('key', 'moderator_share')->first()->value;
        $loser_share = (float) $this->challenge->metas()->where('key', 'loser_share')->first()->value;
        $total_pot = (float) $this->challenge->challengeContributions()->sum('amount');
        $total_pot = (float) bcdiv($total_pot, 100, 6); // convert from Flok cowries to USD for sales table
        $moderator = $this->challenge->owner;

        $players = [];

        foreach ($this->challenge->challengeContestants as $contestant) {
            $votes = (int) $this->challenge->challengeVotes()->where('contestant_id', $contestant->user_id)->count();
            $players[] = ['contestant' => $contestant->contestant, 'votes' => $votes];
        }

        $winner = NULL;
        $loser = NULL;
        if ($players[0]['votes'] === $players[1]['votes']) {
            $winner_share = bcdiv(100 - $moderator_share, 2, 2);
            $loser_share = $winner_share;
            $winner = $players[0]['contestant'];
            $loser = $players[1]['contestant'];
        } else if ($players[0]['votes'] > $players[1]['votes']) {
            $winner = $players[0]['contestant'];
            $loser = $players[1]['contestant'];
        } else {
            $winner = $players[1]['contestant'];
            $loser = $players[0]['contestant'];
        }


        // winner share will always be greater than 0
        $winner_take = bcdiv($winner_share * $total_pot, 100, 4);
        $this->grantParticipantEarnings($winner, $winner_take);

        if ($moderator_share > 0) {
            $moderator_take = bcdiv($moderator_share * $total_pot, 100, 4);
            $this->grantParticipantEarnings($moderator, $moderator_take);
        }

        if ($loser_share > 0) {
            $loser_take = bcdiv($loser_share * $total_pot, 100, 4);
            $this->grantParticipantEarnings($loser, $loser_take);
        }

        $this->challenge->challenge_winner_computed = 1;
        $this->challenge->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

    private function grantParticipantEarnings($participant, $amount)
    {
        $platform_charge = Constants::NORMAL_CREATOR_CHARGE;
        if ($participant->user_charge_type === 'non-profit') {
            $platform_charge = Constants::NON_PROFIT_CREATOR_CHARGE;
        }
        $platform_share = bcmul($amount, $platform_charge, 6);
        $participant_share = bcmul($amount, 1 - $platform_charge, 6);

        $participant->revenues()->create([
            'revenueable_type' => 'content',
            'revenueable_id' => $this->challenge->id,
            'amount' => $amount,
            'payment_processor_fee' => 0,
            'platform_share' => $platform_share,
            'benefactor_share' => $participant_share,
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);

        if ($participant->referrer()->exists()) {
            $participant->referrer->revenues()->create([
                'revenueable_type' => 'content',
                'revenueable_id' => $this->challenge->id,
                'amount' => $amount,
                'payment_processor_fee' => 0,
                'platform_share' => $platform_share,
                'benefactor_share' => 0,
                'referral_bonus' => bcmul($amount, Constants::REFERRAL_BONUS, 6),
                'revenue_from' => 'referral',
            ]);
        }
    }
}
