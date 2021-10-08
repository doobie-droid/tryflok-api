<?php

namespace App\Jobs\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use App\Services\Payment\Providers\Stripe\Stripe;

class CheckPayoutTransferStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $payout;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->payout = $data['payout'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->payout->handler) {
            case "flutterwave":
                $this->handleFlutterwaveCheck();
                break;
            case "stripe":
                $this->handleStripeCheck();
                break;
        }
    }

    private function handleFlutterwaveCheck()
    {
        $flutterwave = new Flutterwave;
        $resp = $flutterwave->getTransferStatus($this->payout->reference);
        if (strtolower($resp->data->status) === "successfull" && $resp->data->is_approved === 1) {
            $this->payout->claimed = 1;
            $this->payout->save();
        }
    }

    private function handleStripeCheck()
    {
        $stripe = new Stripe;
        $resp = $stripe->getTransferStatus($this->payout->reference);
        if (isset($resp->reversed) && $resp->destination === false) {
            $this->payout->claimed = 1;
            $this->payout->save();
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
