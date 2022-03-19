<?php

namespace App\Console\Commands\Contents;

use App\Constants\Constants;
use App\Jobs\Content\ComputeChallengeWinner as ComputeChallengeWinnerJob;
use App\Models\Content;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComputeChallengeWinner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:compute-challenge-winner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate who the winner of a challenge is';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $voting_window = Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;
            Content::where('challenge_winner_computed', 0)
            ->where('is_challenge', 1)
            ->where('live_ended_at', '<=', now()->subMinutes($voting_window))
            ->chunk(100000, function($challenges) {
                foreach ($challenges as $challenge) {
                    ComputeChallengeWinnerJob::dispatch($challenge);
                }
            });
            DB::commit();
        }   catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        return Command::SUCCESS;
    }
}
