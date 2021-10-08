<?php

namespace App\Jobs\Payment\Paystack;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Payment\Purchase as PurchaseJob;

class Delegator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $data;
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
        switch ($this->data['event']) {
            case 'charge.success':
                $this->handleChargeSuccess();
                break;
            default:
                Log::info($this->data);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

    protected function handleChargeSuccess()
    {
        $data = [
            'total_amount' => $this->data['data']['amount'] / 100, //paystack deals in kobo
            'total_fees' => $this->data['data']['fees'] / 100,
            'provider' => 'paystack',
            'provider_id' => $this->data['data']['reference'],
        ]; 
        if (
            $this->data['data']['metadata'] != NULL && 
            $this->data['data']['metadata'] != 0 && 
            is_array($this->data['data']['metadata']) &&
            array_key_exists('payment_data',$this->data['data']['metadata'])
        ) {
            $data['user'] = $this->data['data']['metadata']['payment_data']['user'];
            $data['items'] = $this->data['data']['metadata']['payment_data']['items'];
            PurchaseJob::dispatch($data);
        } else {
            Log::error("Could not complete the transaction. Meta data is NULL");
            Log::error($this->data);
        }
    }
}
