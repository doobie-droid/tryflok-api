<?php

namespace App\Jobs\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\WalletTransaction;

class FundWallet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user, $wallet, $provider, $provider_id, $akc, $amount, $fee;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->wallet = $data['wallet'];
        $this->provider = $data['provider'];
        $this->provider_id = $data['provider_id'];
        $this->akc = $data['akc'];
        $this->amount = $data['amount'];
        $this->fee = $data['fee'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $newWalletBalance = bcadd($this->wallet->balance, $this->akc, 2);
        $walletTransaction = WalletTransaction::create([
            'public_id' => uniqid(rand()),
            'wallet_id' => $this->wallet->id,
            'amount' => $this->akc,
            'balance' => $newWalletBalance,
            'transaction_type' => 'fund',
            'details' => 'Fund wallet with ' . $this->akc . ' AKC via ' . $this->provider,
        ]);
        
        $this->wallet->balance = $newWalletBalance;
        $this->wallet->save();

        $walletTransaction->payments()->create([
            'payer_id' => $this->user->id,
            'payee_id' => $this->user->id,
            'amount' => $this->amount,
            'payment_processor_fee' => $this->fee,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
        ]);

        //TO DO: email user that they have increased their wallet balance
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
