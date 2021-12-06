<?php

namespace App\Jobs\Subscriptions;

use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\Userable;
use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EndSubscription implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $subscription;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->subscription->status = 'disabled';
        $this->subscription->save();
        $parentUserable = Userable::where('id', $this->subscription->userable_id)->first();
        if (is_null($parentUserable)) {
            Log::error('Invalid userable_id supplied');
            Log::error($this->subscription);
            return;
        }
        // check that owner of content is still available
        //if owner has been deleted prevent renewal of the content
        //attempt to renew subscription
        if ($this->subscription->auto_renew == 1) {
            $user = $parentUserable->user()->first();
            $price = Price::where('id', $this->subscription->price_id)->first();
            $item = null;
            switch ($this->subscription->subscriptionable_type) {
                case 'collection':
                    $item = Collection::where('id', $this->subscription->subscriptionable_id)->first();
                    break;
                case 'content':
                    $item = Content::where('id', $this->subscription->subscriptionable_id)->first();
                    break;
            }
            $user_wallet_balance = (float) $user->wallet->balance;//wallet is in AKC
            $item_price = (float) bcmul($price->amount, 100, 6);//converting dollars to AKC.
            if (($item_price < $user_wallet_balance) && ! is_null($item)) {
                $newWalletBalance = bcsub($user->wallet->balance, $item_price, 2);
                $transaction = WalletTransaction::create([
                    'wallet_id' => $user->wallet->id,
                    'amount' => $item_price,
                    'balance' => $newWalletBalance,
                    'transaction_type' => 'deduct',
                    'details' => 'Deduct from wallet to renew subscription for ' . $item->title,
                ]);
                $user->wallet->balance = $newWalletBalance;
                $user->wallet->save();
                PurchaseJob::dispatch([
                    'total_amount' => $price->amount,
                    'total_fees' => 0,
                    'user' => $user->toArray(),
                    'provider' => 'wallet',
                    'provider_id' => $transaction->id,
                    'items' => [
                        [
                            'id' => $item->id,
                            'type' => $this->subscription->subscriptionable_type,
                            'price' => [
                                'id' => $price->id,
                                'amount' => $price->amount,
                                'interval' => $price->interval,
                                'interval_amount' => $price->interval_amount,
                            ],
                        ],
                    ],
                ]);
                //TO DO: mail user that their auto-renewal was succesful
                return;
            }
        }

        //renew of subscription failed, go ahead to end subscription
        $parentUserable->status = 'subscription-ended';
        $parentUserable->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
