<?php

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;
use App\Models\UserTip;
use App\Jobs\Users\TipUsersRecurrentlyWithFlutterwave as TipUsersRecurrentlyWithFlutterwaveJob;
use Illuminate\Support\Facades\Log;

class TipUsersRecurrentlyWithFlutterwave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:tip-users-recurrently-with-flutterwave';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tip users recurrently with flutterwave';

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
                where('status', 'active')
                ->where('provider', 'flutterwave')
                ->chunk(1000, function ($userTips) {                
                TipUsersRecurrentlyWithFlutterwaveJob::dispatchNow($userTips);
            });

        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
