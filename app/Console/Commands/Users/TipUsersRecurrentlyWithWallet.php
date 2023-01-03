<?php

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;
use App\Models\UserTip;
use App\Jobs\Users\TipUsersRecurrentlyWithWallet as TipUsersRecurrentlyWithWalletJob;
use Illuminate\Support\Facades\Log;

class TipUsersRecurrentlyWithWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:tip-users-recurrently-with-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tip users recurrently with wallet';

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
        try{
            UserTip::
                where('is_active', 1)
                ->where('provider', 'wallet')
                ->chunk(1000, function ($userTips) {                
                // foreach ($userTips as $userTip) {
                    TipUsersRecurrentlyWithWalletJob::dispatchNow($userTips);
                // }
            });

        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
