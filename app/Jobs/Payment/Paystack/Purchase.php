<?php

namespace App\Jobs\Payment\Paystack;

use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Services\Payment\Providers\Paystack\Paystack as PaystackPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Purchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $provider_response, $user, $items;
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $paystack = new PaystackPayment;
        $req = $paystack->verifyTransaction($this->provider_response['reference']);
        if ((($req->status === true || $req->status === "true" ) && $req->data->status === "success")) {
            PurchaseJob::dispatch([
                'total_amount' => $req->data->amount / 100,
                'total_fees' => $req->data->fees / 100,
                'provider' => 'paystack',
                'provider_id' => $req->data->reference,
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
