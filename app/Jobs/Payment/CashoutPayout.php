<?php

namespace App\Jobs\Payment;

use App\Jobs\Users\NotifyNoPaymentAccount;
use App\Services\Payment\Payment as PaymentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CashoutPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $payout;
    private $payment_account;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payout)
    {
        $this->payout = $payout;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        $this->payment_account = $this->payout->user->paymentAccounts()->first();
        $this->payout->increasePayoutCashoutAttempts();
        if (is_null($this->payment_account)) {
            if ($this->payout->failedNotificationNotSent(12)) {
                $this->sendNoPaymentAccountNotification();
            }
            return;
        }
        $this->payout->setHandler($this->payment_account->provider);
        $amount = ceil(floatval($this->payout->amount));
        $paymentProvider = new PaymentProvider($this->payment_account->provider);
        $resp = $paymentProvider->transferFundsToRecipient($this->payment_account, $amount);
        switch ($this->payment_account->provider) {
            case 'flutterwave':
                if ($resp->status === 'success') {
                    $this->payout->markAsCompleted($resp->data->id);
                } else {
                    $this->payout->resetCashoutAttept();
                }
                break;
            case 'stripe':
                if (
                    isset($resp->destination) && 
                    $resp->destination === $this->payment_account->identifier
                ) {
                    $this->payout->markAsCompleted($resp->id);
                } else {
                    $this->payout->resetCashoutAttept();
                }
                break;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

    private function sendNoPaymentAccountNotification()
    {
        NotifyNoPaymentAccount::dispatch([
            'payout' => $this->payout,
            'user' => $this->payout->user,
        ]);
        $this->payout->failed_notification_sent = now();
        $this->payout->save();
    }
}
