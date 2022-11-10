<?php

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;
use App\Models\AnonymousPurchase;
use App\Jobs\Users\LinkAnonymousPurchaseToUsers as LinkAnonymousPurchaseToUsersJob;

class LinkAnonymousPurchasesToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:link-anonymous-purchases-to-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link anonymous purchases to users';

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
                whereNull('link_user_id')
                ->chunk(1000, function ($anonymous_purchases) {                
                foreach ($anonymous_purchases as $anonymous_purchase) {
                LinkAnonymousPurchaseToUsersJob::dispatch($anonymous_purchase);
                }
            });
        } catch (\Exception $exception) {
            throw $exception;
        }
        return Command::SUCCESS;
    }
}
