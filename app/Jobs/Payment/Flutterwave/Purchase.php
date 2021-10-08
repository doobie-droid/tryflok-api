<?php

namespace App\Jobs\Payment\Flutterwave;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Services\Payment\Providers\Flutterwave\Flutterwave as FlutterwavePayment;

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
        $flutterwave = new FlutterwavePayment;
        $req = $flutterwave->verifyTransaction($this->provider_response['transaction_id']);
        if (($req->status === "success" && $req->data->status === "successful")) {
            PurchaseJob::dispatch([
                'total_amount' => $req->data->amount,
                'total_fees' => $req->data->app_fee,
                'provider' => 'flutterwave',
                'provider_id' => $req->data->id,
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
