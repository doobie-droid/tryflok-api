<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use App\Models\Configuration;
use App\Jobs\Users\TipUsersRecurrentlyWithFlutterwave as TipUsersRecurrentlyWithFlutterwaveJob;

class TipUsersRecurrentlyWithFlutterwave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            foreach($this->datas as $data) 
            {
                $payment_verified = false;
                $amount = //convert flk cowrie to NGN
                $tx_ref = date('Ymdhis');
                $naira_to_dollar = Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
                $flutterwave = new Flutterwave;
                $req = $flutterwave->recurrentTipCharge($data['card_token'], $data['email'], $tx_ref, $amount);
                if (($req->status === 'success' && $req->data->status === 'successful')) {
                    $amount_in_dollars = bcdiv($req->data->amount, $naira_to_dollar->value, 2);
                    $expected_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                    $fee = bcdiv($req->data->app_fee, $naira_to_dollar->value, 2);
                    $payment_verified = true;
                }

                if(! $payment_verified)
                {
                    Log::info("Payment not verified");
                    //send mail to user?
                    continue;
                }

                TipUsersRecurrentlyWithFlutterwaveJob::dispatchNow([
                    'tippee_id' => $data['tippee_user_id'],
                    'flk' => $data['amount_in_flk'],
                    'email' => $data['email'],
                    'last_tip' => $data['last_tip'],
                ]);
            }
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
