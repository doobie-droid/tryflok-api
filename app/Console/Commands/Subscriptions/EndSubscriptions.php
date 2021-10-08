<?php

namespace App\Console\Commands\Subscriptions;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Jobs\Subscriptions\EndSubscription as EndSubscriptionJob;

class EndSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:end-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End subscriptions';

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
        Subscription::where('status', 'active')->where('end', '<=', now())->chunk(10000, function ($subscriptions) {
            foreach ($subscriptions as $subscription) {
                EndSubscriptionJob::dispatch($subscription);
            }
        });
        return 0;
    }
}
