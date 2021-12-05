<?php

namespace App\Jobs\Payment\Stripe;

use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Services\Payment\Providers\Stripe\Stripe as StripePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Purchase implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $provider_response;
    public $user;
    public $items;
    public $amount;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->provider_response = $data['provider_response'];
        $this->user = $data['user'];
        $this->items = $data['items'];
        $this->amount = bcadd(0, $data['dollar_amount'], 0);//is in cents
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $stripe = new StripePayment();
        $req = $stripe->chargeViaToken($this->amount, $this->provider_response['id']);
        if (($req->status === 'succeeded' && $req->paid === true)) {
            //Stripe for USD deals in cents
            //we process all Stripe transactoins in USD
            $amount = bcmul(bcdiv($req->amount, 100, 6), 450, 6);//450 is exchange rate of dollar to naira
            $fee = bcmul(bcdiv(bcadd(bcdiv(bcmul(2.9, $req->amount, 6), 100, 6), 30, 6), 100, 6), 450, 6);//2.9 % + 30 cents, divide by 100 to get dollar equivalent, multiply by 450 to get naira
            PurchaseJob::dispatch([
                'total_amount' => $amount,
                'total_fees' => $fee,
                'provider' => 'stripe',
                'provider_id' => $req->id,
                'user' => $this->user,
                'items' => $this->items,
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
