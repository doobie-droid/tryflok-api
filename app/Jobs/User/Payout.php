<?php

namespace App\Jobs\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Payout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $start, $user, $isFirstPayout;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->start = $data['start'];
        $this->user = $data['user'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = now();
        $total_benefactor = 0;
        $total_referral = 0;
        $this->user->sales()->whereDate('created_at', '>=', $this->start)->whereDate('created_at', '<=', $now)->where('added_to_payout', 0)->chunk(10000, function ($sales) use (&$total_benefactor, &$total_referral) {
            foreach ($sales as $sale) {
                $total_benefactor = bcadd($total_benefactor, $sale->benefactor_share,6);
                $total_referral = bcadd($total_referral, $sale->referral_bonus,6);
                $sale->added_to_payout = 1;
                $sale->save();
            }
        });

        $total_payout = bcadd($total_benefactor, $total_referral,6);

        $this->user->payouts()->create([
            'public_id' => uniqid(rand()),
            'amount' => $total_payout,
            'start' => $this->start,
            'end' => $now,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
