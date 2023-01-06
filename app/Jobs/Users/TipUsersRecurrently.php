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
use App\Models\User;
use App\Jobs\Users\AnonymousUserTip as AnonymousUserTipJob;
use Illuminate\Support\Facades\Mail;
use App\Services\Payment\Providers\Stripe\Stripe as StripePayment;
use App\Mail\User\FailedTipMail;


class TipUsersRecurrently implements ShouldQueue
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
            $datas = $this->data;
            foreach($datas as $userTip) 
            {
                $userToTip = User::where('id', $userTip->tippee_user_id)->first();
                $next_tip = false;
                switch ($userTip->tip_frequency) {
                    case 'daily':
                        if($userTip->last_tip <= now()->subDay())
                        {
                            $next_tip = true;
                        }
                        break;
                    case 'weekly':
                        if($userTip->last_tip <= now()->subWeek())
                        {
                            $next_tip = true;
                        }
                        break;                
                    case 'monthly':
                        if($userTip->last_tip <= now()->subMonth())
                        {
                            $next_tip = true;
                        }
                        break;
                    default:
                        Log::info('Invalid tip frequency');
                        continue 2;
                }
                if(! $next_tip)
                {
                    Log::info("Not yet time for next tip");
                    continue;
                }
                $payment_verified = false;                

                switch ($userTip->provider) {
                    case 'flutterwave':
                        $naira_to_dollar = Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
                        $amount = bcdiv($userTip->amount_in_flk, 100, 2) * $naira_to_dollar->value;
                        $tx_ref = date('Ymdhis');
                        $flutterwave = new Flutterwave;
                        $req = $flutterwave->recurrentTipCharge($userTip->card_token, $userTip->tipper_email, $tx_ref, $amount);
                        if (($req->status === 'success' && $req->data->status === 'successful')) {
                            $amount_in_dollars = bcdiv($req->data->amount, $naira_to_dollar->value, 2);
                            $actual_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                            $provider_id = $req->data->id;
                            $fee = bcdiv($req->data->app_fee, $naira_to_dollar->value, 2);
                            $payment_verified = true;
                        }
                        break;

                    case 'stripe':
                        $amount_in_cents = bcmul($userTip->amount_in_flk, 1.03, 2);
                        $stripe = new StripePayment;
                        // Charge the Customer instead of the card:
                        $charge = $stripe->createCharge($amount_in_cents, 'usd', $userTip->customer_id);
                        if ($charge->status === 'succeeded')
                            {
                                $fee = bcdiv(bcadd(bcmul(0.029, $charge->amount, 2), 30, 2), 100, 2); //2.9% + 30c convert to dollar
                                $amount_in_dollars = bcdiv($charge->amount, 100, 2);
                                $provider_id = $charge->id;
                                $actual_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                                $payment_verified = true;
                            }  
                        break;
                    default:
                    Log::info('Invalid provider');
                    continue 2;
                }                
                if(! $payment_verified)
                {
                    Log::info("Payment not verified");
                    //send mail to user
                    $this->sendFailedTipMail($userTip->tipper_email, $userToTip);
                    continue;
                }
                AnonymousUserTipJob::dispatch([
                    'tippee_id' => $userToTip->id,
                    'flk' => $actual_flk_based_on_amount,
                    'email' => $userTip->tipper_email,
                    'last_tip' => $userTip->last_tip,
                    'originating_content_id' => $userTip->originating_content_id,
                    'originating_client_source' => $userTip->originating_client_source,
                    'originating_currency' => $userTip->originating_currency,
                    'id' => $userTip->id,
                    'provider' => $userTip->provider,
                    'card_token' => $userTip->card_token,
                    'customer_id' => $userTip->customer_id,
                    'fee' => $fee,
                    'provider_id' => $provider_id,
                    'tip_frequency' => '',
                ]);
            }
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }

    public function sendFailedTipMail($email, $userToTip)
    {
        $message = "Sorry, we could not tip {$userToTip->username} because the payment provider did not verify the payment";
        Mail::to($email)->send(new FailedTipMail([
        'email' => $email,
        'message' => $message,
        'user' => '',
    ]));
    }
}
