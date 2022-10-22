<?php

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;
use App\Models\AnonymousPurchase;
use Illuminate\Support\Facades\Log;
use App\Jobs\Users\NotifyAnonymousUsers as NotifyAnonymousUsersJob;

class MailAnonymousUsersForLiveEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:mail-anonymous-users-for-live-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends reminder emails to anonymous users before live events start';

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
           AnonymousPurchase::
           chunk(1000, function ($anonymousPurchases) {                
                foreach ($anonymousPurchases as $anonymousPurchase) {
                NotifyAnonymousUsersJob::dispatch($anonymousPurchase);
                }
            });
        } catch (\Exception $exception) {
            throw $exception;
        }
        return Command::SUCCESS;
    }
}
