<?php

namespace App\Console\Commands\Payments;

use App\Jobs\Payment\CashoutPayout as CashoutPayoutJob;
use App\Models\Payout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CashoutPayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flok:cashout-payouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cashout payouts that have not been claimed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        DB::beginTransaction();
        try {
            Payout::where('claimed', 0)
                ->where(function ($query) {
                    $query->whereNull('last_payment_request')
                        ->orWhere('last_payment_request', '<=', now()->subHours(3));
                })
                ->where('cancelled_by_admin', 0)
                ->chunk(100000, function ($payouts) {
                    foreach ($payouts as $payout) {
                        $payout->last_payment_request = now();
                        $payout->save();
                        CashoutPayoutJob::dispatch($payout);
                    }
                });
            DB::commit();
        }   catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
        
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
