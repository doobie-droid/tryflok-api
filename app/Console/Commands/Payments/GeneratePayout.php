<?php

namespace App\Console\Commands\Payments;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\Users\Payout as PayoutJob;

class GeneratePayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:generate-payouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Payouts for users';

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
        User::whereHas('sales', function (Builder $query) {
            $query->where('added_to_payout', 0);
        })->chunk(100000, function ($users) {
            foreach ($users as $user) {
                PayoutJob::dispatch([
                    'user' => $user,
                ]);
            }
        });
        return Command::SUCCESS;
    }
}
