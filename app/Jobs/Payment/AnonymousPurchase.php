<?php

namespace App\Jobs\Payment;

use App\Constants\Constants;
use App\Jobs\Users\NotifySale;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Revenue;
use App\Models\Price;
use App\Models;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Mail\User\JohnnyDrillPurchaseMail;
use App\Mail\User\AnonymousPurchaseMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AnonymousPurchase implements ShouldQueue
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
       //get model of related objects
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

           $amount = $price->amount;
           if ($this->data['total_fees'] > 0) {
               $fee = bcmul(bcdiv($amount, $this->data['total_amount'], 6), $this->data['total_fees'], 6);
           } else {
               $fee = 0;
           }
           $number_of_tickets = 1;
           if (isset($item['number_of_tickets']) && $itemModel->type == 'live-video' || $itemModel->type == 'live-audio') {
                $number_of_tickets = $item['number_of_tickets'];
           } 
           //record payment on payment table
           $payment = $itemModel->payments()->create([
               'payee_id' => $itemModel->owner->id,
               'amount' => $amount * $number_of_tickets,
               'payment_processor_fee' => $fee,
               'provider' => $this->data['provider'],
               'provider_id' => $this->data['provider_id'],
               'payer_email' => $this->data['payer_email']
           ]);

           //record sales for the benefactors of this item
           $net_amount = $amount;
           
           $platform_charge = Constants::NORMAL_CREATOR_CHARGE;
           if ($itemModel->owner->user_charge_type === 'non-profit') {
               $platform_charge = Constants::NON_PROFIT_CREATOR_CHARGE;
           }
           $platform_share = bcmul($net_amount, $platform_charge, 6);
           $creator_share = bcmul($net_amount, 1 - $platform_charge, 6);
           for($i = 0; $i < $number_of_tickets; $i++) {
           foreach ($itemModel->benefactors as $benefactor) {
               $benefactorUser = $benefactor->user->revenues()->create([
                   'revenueable_type' => $item['type'],
                   'revenueable_id' => $itemModel->id,
                   'amount' => $amount,
                   'payment_processor_fee' => $fee,
                   'platform_share' => bcsub($platform_share, $fee, 6),
                   'benefactor_share' => bcdiv(bcmul($creator_share, $benefactor->share, 6), 100, 6),
                   'referral_bonus' => 0,
                   'revenue_from' => 'sale',
               ]);
           }
            $anonymous_purchase = Models\AnonymousPurchase::create([
            'email' => $this->data['payer_email'],
            'name' => $this->data['payer_name'],
            'status' => 'available',
            'access_token' => Str::random(10) . date('Ymd'),
            'anonymous_purchaseable_type' => $item['type'],
            'anonymous_purchaseable_id' => $itemModel->id,
                ]);
            }
            $access_tokens = [];
            $content_url = '';
            $anonymous_purchases = Models\AnonymousPurchase::where('anonymous_purchaseable_id', $itemModel->id)->where('email', $this->data['payer_email'])->get();
            $sales_count = $anonymous_purchases->count();
            foreach( $anonymous_purchases as $anonymous_purchase) {
                    $access_tokens[] = $anonymous_purchase->access_token;
            }
            $avatar_url = $this->getAvatarUrl($sales_count);
            if ($itemModel->type == 'video' || $itemModel->type == 'audio' || $itemModel->type == 'newsletter' || $itemModel->type == 'live-video' || $itemModel->type == 'live-audio') {
                $content_url = "https://www.tryflok.com/contents/". $itemModel->id;
            }

           //record revenue from referral of the item
           if ($itemModel->owner->referrer()->exists()) {
               $itemModelOwner = $itemModel->owner->referrer->revenues()->create([
                   'revenueable_type' => $item['type'],
                   'revenueable_id' => $itemModel->id,
                   'amount' => $amount,
                   'payment_processor_fee' => $fee,
                   'platform_share' => bcsub($platform_share, $fee, 6),
                   'benefactor_share' => 0,
                   'referral_bonus' => bcmul(bcsub($net_amount, $fee, 6), Constants::REFERRAL_BONUS, 6),
                   'revenue_from' => 'referral',
               ]);
           }
        
        //if subscription create subscription record
           if ($item['type'] === 'collection' && $price->interval === 'monthly') {
               $start = now();
               $cloneOfStart = clone $start;
               $end = $cloneOfStart->add($price->interval_amount, 'month');
               $auto_renew = 0;
               if ($price->amount == 0) {
                   $auto_renew = 1;
               }
               $itemModel->subscriptions()->create([
                   'anonymous_purchaseable_id' => $anonymous_purchase->id,
                   'price_id' => $price->id,
                   'start' => $start,
                   'end' => $end,
                   'auto_renew' => $auto_renew,
               ]);
           }
        if ($itemModel->id == env('JOHNNY_DRILL_CONTENT_ID'))
        {
            Mail::to($this->data['payer_email'])->send(new JohnnyDrillPurchaseMail([
            'access_tokens' => $access_tokens,
            'avatar_url' => $avatar_url,
            'sales_count' => $sales_count,
            'name' => $this->data['payer_name'],
            'content_url' => $content_url,
        ]));  
        } else {
            $message = "You've just purchased the content '{$itemModel->title}' on flok, use this token(s) to access the content you purchased on flok!";
            Mail::to($this->data['payer_email'])->send(new AnonymousPurchaseMail([
            'message' => $message,
            'access_tokens' => $access_tokens,
            'name' => $this->data['payer_name'],
            'content_url' => $content_url,
        ]));
        }   
        if ($price->amount > 0) {
            NotifySale::dispatch($itemModel->owner()->first(), $itemModel, $item['type']);
        }
       }
    }

    public function getAvatarUrl($sales_count) 
    {
        $avatar_urls = file(public_path("avatar.csv"));
        $csv_items_count = count($avatar_urls);
        $url_index = $sales_count - 1;
        if ($csv_items_count > $url_index)
        {
            $avatar_url = $avatar_urls[$url_index];
            return $avatar_url;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}