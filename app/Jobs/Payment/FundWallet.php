<?php

namespace App\Jobs\Payment;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FundWallet implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $user;
    private $wallet;
    private $provider;
    private $provider_id;
    private $flk;
    private $amount;
    private $fee;

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
        $this->flk = $data['flk'];
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
        $newWalletBalance = bcadd($this->wallet->balance, $this->flk, 2);
        $walletTransaction = WalletTransaction::create([
            'public_id' => uniqid(rand()),
            'wallet_id' => $this->wallet->id,
            'amount' => $this->flk,
            'balance' => $newWalletBalance,
            'transaction_type' => 'fund',
            'details' => 'Fund wallet with ' . $this->flk . ' FLK via ' . $this->provider,
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
