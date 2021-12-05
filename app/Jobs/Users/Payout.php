<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Payout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $total_benefactor = 0;
        $total_referral = 0;
        $this->user->sales()->where('added_to_payout', 0)->chunk(100000, function ($sales) use (&$total_benefactor, &$total_referral) {
            foreach ($sales as $sale) {
                $total_benefactor = bcadd($total_benefactor, $sale->benefactor_share,6);
                $total_referral = bcadd($total_referral, $sale->referral_bonus,6);
                $sale->added_to_payout = 1;
                $sale->save();
            }
        });

        $total_payout = bcadd($total_benefactor, $total_referral,2);

        if ($total_payout > 0) {
            $this->user->payouts()->create([
                'amount' => $total_payout,
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
