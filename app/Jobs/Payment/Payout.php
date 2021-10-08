<?php

namespace App\Jobs\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\Payment as PaymentProvider;

class Payout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $payout, $payment_account;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->payout = $data['payout'];
        $this->payment_account = $data['payment_account'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $amount = floatval($this->payout->amount);
        $amount = ceil($amount);
        $paymentProvider = new PaymentProvider($this->payment_account->provider);
        $resp = $paymentProvider->transferFundsToRecipient($this->payment_account, $amount);
        switch ($this->payment_account->provider) {
            case "flutterwave":
                if ($resp->status === "success") {
                    $this->payout->reference = $resp->data->id;
                    $this->payout->save();
                } else {
                    $this->payout->handler = NULL;
                    $this->payout->last_payment_request = NULL;
                    $this->payout->save();
                    //notify user that payout attempt failed
                }
                break;
            case "stripe":
                if (isset($resp->destination) && $resp->destination === $this->payment_account->identifier) {
                    $this->payout->reference = $resp->id;
                    $this->payout->claimed = 1;
                    $this->payout->save();
                } else {
                    $this->payout->handler = NULL;
                    $this->payout->last_payment_request = NULL;
                    $this->payout->save();
                }
                break;
        }
    }

    public function failed(\Throwable $exception)
    {
        $this->payout->handler = NULL;
        $this->payout->last_payment_request = NULL;
        $this->payout->save();
        Log::error($exception);
        //TO DO: mail the user telling them the payout failed?
    }
}
