<?php

namespace App\Console\Commands\Payments;

use App\Jobs\Payment\CheckPayoutTransferStatus as CheckPayoutTransferStatusJob;
use App\Models\Payout;
use Illuminate\Console\Command;

class CheckPayoutTransferStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:check-payout-transfer-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for payouts that have been initiated and update status';

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
        Payout::whereNotNull('reference')->where('claimed', 0)->chunk(10000, function ($payouts) {
            foreach ($payouts as $payout) {
                CheckPayoutTransferStatusJob::dispatch([
                    'payout' => $payout,
                ]);
            }
        });
        return 0;
    }
}
