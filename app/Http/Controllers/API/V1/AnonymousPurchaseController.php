<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constants\Constants;
use App\Models\Collection;
use App\Models\Configuration;
use App\Models\Content;
use App\Models\AnonymousPurchase;
use App\Models\Userable;
use App\Models\Price;
use App\Services\Payment\Providers\ApplePay\ApplePay;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use App\Services\Payment\Providers\Stripe\Stripe as StripePayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Jobs\Payment\AnonymousPurchase as AnonymousPurchaseJob;
use Illuminate\Support\Str;
use App\Http\Resources\ContentResource;

class AnonymousPurchaseController extends Controller
{
    public function makePurchase (Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'email' => ['required', 'string', 'email', 'max:255'],
                'name' => ['required', 'string'],
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
                'items.*.price' => ['required'],
                'items.*.price.amount' => ['required', 'numeric', 'min:0'],
                'items.*.price.id' => ['required', 'string','exists:prices,id'],
                'items.*.price.interval' => ['required', 'string', 'in:monthly,one-off'],
                'items.*.price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
                'items.*.number_of_tickets' => ['sometimes', 'nullable', 'min:1', 'integer'],
                'provider' => ['required', 'string', 'in:flutterwave,stripe'],
                'provider_response' => ['required'],
                'provider_response.transaction_id' => ['required_if:provider,flutterwave'],
                'provider_response.id' => ['required_if:provider,stripe', 'string'],
                'amount_in_cents' => ['required_if:provider,stripe', 'integer'],
                'expected_flk_amount' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $total_amount_in_dollars = 0;
            foreach ($request->items as $item) {
                $price = Price::where('id', $item['price']['id'])->first();
                if (is_null($price)) {
                    // price does not exist so this purchase is probably nefarious and we should not add this item to the total purchase
                    continue;
                 }
 
                $number_of_tickets = 1;
                if (isset($item['number_of_tickets'])) {
                    $number_of_tickets = $item['number_of_tickets'];
                }

                /*
                $item['price']['amount'] holds the price for a single item
                The reason why the system design made the amount to be passed was because we wanted to prevent a situation where the user buys item at a specific price and the creator has changed price. We wanted the price to be locked in.
                We are changing this soon though. Only the price ID should be passed because that is what we really need. The reason we need the price ID is that an item can have multiple prices based on location so we want the price the user saw for their location hence the need for the ID.


                // Thus, this line of code is erroneous
                if ($item['price']['amount'] != ($price->amount * $number_of_tickets )) {
                    return $this->respondBadRequest('Amount does not match total item to be purchased');
                }
                */

                //validate that the content or collection exists
                $itemModel = null;
                switch ($item['type']) {
                    case 'content':
                        $itemModel = Content::where('id', $item['id'])->first();
                        break;
                    case 'collection':
                        $itemModel = Collection::where('id', $item['id'])->first();
                        break;
                }
                if (is_null($itemModel)) {
                    //item does not exist so should not be included in total sum and purchase may be nefarious
                   continue;
                }
                $actual_price = $price->amount * $number_of_tickets;
                //add total price
                $total_amount_in_dollars = bcadd($total_amount_in_dollars, $actual_price, 2);
            }

            Log::info("Anonymous attempted purchase began");
            $payment_verified = false;
            $amount_in_dollars = 0;
            $fee = 0;
            $actual_flk_from_amount_paid = 0;
            $expected_flk_based_on_content_price = bcdiv($total_amount_in_dollars, 1.03, 2) * 100;
            $provider_id = '';
            $naira_to_dollar = Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first(); 

            switch ($request->provider) {
                case 'flutterwave':
                    $flutterwave = new Flutterwave;
                    $req = $flutterwave->verifyTransaction($request->provider_response['transaction_id']);
                    if (($req->status === 'success' && $req->data->status === 'successful')) {
                        $amount_in_dollars = bcdiv($req->data->amount, $naira_to_dollar->value, 2);
                        $actual_flk_from_amount_paid = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                        $fee = bcdiv($req->data->app_fee, $naira_to_dollar->value, 2);
                        $provider_id = $req->data->id;
                        $payment_verified = true;
                    }
                    break;
                case 'stripe':
                    $stripe = new StripePayment;
                    $amount_in_dollars = bcdiv($request->amount_in_cents, 100, 2);
                    $actual_flk_from_amount_paid = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                    $req = $stripe->chargeViaToken($request->amount_in_cents, $request->provider_response['id']);

                    if (($req->status === 'succeeded' && $req->paid === true)) {
                        $fee = bcdiv(bcadd(bcmul(0.029, $req->amount, 2), 30, 2), 100, 2); //2.9% + 30c convert to dollar
                        $amount_in_dollars = bcdiv($req->amount, 100, 2);
                        $actual_flk_from_amount_paid = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                        $provider_id = $req->id;
                        $payment_verified = true;
                    }
                    
                    break;
                default:
                    return $this->respondBadRequest('Invalid provider specified');
            }

            // if (!$payment_verified) {
            //     return $this->respondBadRequest('Payment provider did not verify payment');
            // }
            // $min_variation = $expected_flk_based_on_content_price - bcmul($expected_flk_based_on_content_price, .03, 2);
            // $max_variation = $expected_flk_based_on_content_price + bcmul($expected_flk_based_on_content_price, .03, 2);
            // if ($actual_flk_from_amount_paid < $min_variation || $request->expected_flk_amount > $max_variation) {
            //     return $this->respondBadRequest("Flok Cowrie conversion is not correct. Expects +/-3% of {$expected_flk_based_on_content_price} based on total content(s) price [{$total_amount_in_dollars}] but got {$actual_flk_from_amount_paid} from amount paid [{$amount_in_dollars}]");
            // }
            AnonymousPurchaseJob::dispatch([
                'total_amount' => $amount_in_dollars,
                'total_fees' => $fee,
                'total_fees' => $fee,
                'payer_email' => $request->email,
                'payer_name' => $request->name,
                'provider' => $request->provider,
                'provider_id' => Str::uuid(),
                'items' => $request->items,
            ]);

            Log::info("Anonymous attempted purchase was successful");
            return $this->respondWithSuccess('Payment received successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function linkAnonymousPurchase(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'access_tokens' => ['required'],
                'access_tokens.*.access_token' => ['required', 'string', 'exists:anonymous_purchases,access_token'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->access_tokens as $access_token) {
                $anonymous_purchase = AnonymousPurchase::where('access_token', $access_token)->whereNull('link_user_id')->first();

                if (! is_null($anonymous_purchase)) {
                    Userable::create([
                        'user_id' => $request->user()->id,
                        'status' => 'available',
                        'userable_type' => $anonymous_purchase->anonymous_purchaseable_type,
                        'userable_id' => $anonymous_purchase->anonymous_purchaseable_id,
                    ]);

                    $anonymous_purchase->link_user_id = $request->user()->id;
                    $anonymous_purchase->save();
                }
            }            
            return $this->respondWithSuccess('Anonymous Purchase has been successfully linked');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }   
    }
}
