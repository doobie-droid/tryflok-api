<?php

namespace App\Jobs\Payment;

use App\Constants\Constants;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\User;
use App\Models\Userable;
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
        //get model of related objects
        $payer = User::where('id', $this->data['user']['id'])->first();
        if (is_null($payer)) {
            //in case model was deleted
            Log::error('Could not complete the transaction because user does not exist or invalid id supplied');
            Log::error($this->data);
            return;
        }
        foreach ($this->data['items'] as $item) {
            switch ($item['type']) {
                case 'content':
                    $itemModel = Content::where('id', $item['id'])->first();
                    break;
                case 'collection':
                    $itemModel = Collection::where('id', $item['id'])->first();
                    break;
                default:
                    Log::error('Could not complete the transaction because item type was not specified');
                    Log::error($item);
                    continue 2;
            }

            if (is_null($itemModel)) {
                //in case model was deleted
                Log::error('Could not complete the transaction because content has been deleted or invalid id supplied');
                Log::error($item);
                continue;
            }

            $price = Price::where('id', $item['price']['id'])->first();
            if (is_null($price)) {
                //in case model was deleted
                Log::error('Could not complete the transaction because price has been deleted or invalid id supplied');
                Log::error($item);
                continue;
            }

            $amount = $item['price']['amount'];
            if ($this->data['total_fees'] > 0) {
                $fee = bcmul(bcdiv($amount, $this->data['total_amount'], 6), $this->data['total_fees'], 6);
            } else {
                $fee = 0;
            }

            //checkout item from cart
            $cartItem = Cart::where('cartable_type', $item['type'])->where('cartable_id', $itemModel->id)->where('user_id', $payer->id)->where('checked_out', 0)->first();
            if (! is_null($cartItem)) {
                $cartItem->checked_out = 1;
                $cartItem->status = 'completed';
                $cartItem->save();
            } else {
                Cart::create([
                    'user_id' => $payer->id,
                    'cartable_id' => $itemModel->id,
                    'cartable_type' => $item['type'],
                    'quantity' =>  1,
                    'checked_out' => 1,
                    'status' => 'completed',
                ]);
            }

            //record payment on payment table
            $payment = $itemModel->payments()->create([
                'payer_id' => $payer->id,
                'payee_id' => $itemModel->owner->id,
                'amount' => $amount,
                'payment_processor_fee' => $fee,
                'provider' => $this->data['provider'],
                'provider_id' => $this->data['provider_id'],
            ]);

            //record sales for the benefactors of this item
            $net_amount = $amount;
            $platform_share = bcdiv(bcmul($net_amount, Constants::PLATFORM_SHARE, 6), 100, 2);
            $creator_share = bcdiv(bcmul($net_amount, Constants::CREATOR_SHARE, 6), 100, 2);
            foreach ($itemModel->benefactors as $benefactor) {
                $benefactor->user->sales()->create([
                    'saleable_type' => $item['type'],
                    'saleable_id' => $itemModel->id,
                    'amount' => $amount,
                    'payment_processor_fee' => $fee,
                    'platform_share' => bcsub($platform_share, $fee, 2),
                    'benefactor_share' => bcdiv(bcmul($creator_share, $benefactor->share, 6), 100, 2),
                    'referral_bonus' => 0,
                ]);
            }
            //record sales for the referrer of the item
            if ($itemModel->owner->referrer()->exists()) {
                $itemModel->owner->referrer->sales()->create([
                    'saleable_type' => $item['type'],
                    'saleable_id' => $itemModel->id,
                    'amount' => $amount,
                    'payment_processor_fee' => $fee,
                    'platform_share' => bcsub($platform_share, $fee, 6),
                    'benefactor_share' => 0,
                    'referral_bonus' => bcdiv(bcmul(bcsub($platform_share, $fee, 6), 2.5, 6), 100, 2),
                ]);
            }

            //check if item exists in userables as a parent item (cos it's always going to trickle to children)
            $parentUserable = Userable::where('userable_type', $item['type'])->where('userable_id', $itemModel->id)->where('user_id', $payer->id)->whereNull('parent_id')->first();
            if (is_null($parentUserable)) {
                //add content/collection to userables
                $parentUserable = Userable::create([
                    'user_id' => $payer->id,
                    'status' => 'available',
                    'userable_type' => $item['type'],
                    'userable_id' => $itemModel->id,
                ]);
            } else {
                //update the already exisitng content/collection in userables
                $parentUserable->status = 'available';
                $parentUserable->save();
            }

            //if subscription create subscription record
            if ($item['type'] === 'collection' && $price->interval  === 'monthly') {
                $start = now();
                $cloneOfStart = clone $start;
                $end = $cloneOfStart->add($price->interval_amount, 'month');
                $itemModel->subscriptions()->create([
                    'userable_id' => $parentUserable->id,
                    'price_id' => $price->id,
                    'start' => $start,
                    'end' => $end,
                    'auto_renew' => 0,
                ]);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
