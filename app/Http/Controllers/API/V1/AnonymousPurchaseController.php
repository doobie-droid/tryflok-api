<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constants\Constants;
use App\Models\Collection;
use App\Models\Content;
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
                'first_name' => ['required', 'string'],
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
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $total_amount_in_dollars = 0;
            foreach ($request->items as $item) {
                $price = Price::where('id', $item['price']['id'])->first();
                //validate amount is equal to total number of tickets
                $number_of_tickets = 1;
                if (isset($item['number_of_tickets'])) {
                    $number_of_tickets = $item['number_of_tickets'];
                }

                if ($item['price']['amount'] != ($price->amount * $number_of_tickets )) {
                    return $this->respondBadRequest('Amount does not match total item to be purchased');
                }
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
                    return $this->respondBadRequest('You selected an item that does not exist.');
                }
                //add total price
                $total_amount_in_dollars = bcadd($total_amount_in_dollars, $price->amount, 2);
            }

            Log::info("Anonymous attempted purchase began");
            $payment_verified = false;
            $amount_in_dollars = 0;
            $fee = 0;
            $expected_flk_based_on_amount = 0;
            $provider_id = '';

            switch ($request->provider) {
                case 'flutterwave':
                    $flutterwave = new Flutterwave;
                    $req = $flutterwave->verifyTransaction($request->provider_response['transaction_id']);
                    if (($req->status === 'success' && $req->data->status === 'successful')) {
                        $amount_in_dollars = bcdiv($req->data->amount, Constants::NAIRA_TO_DOLLAR, 2);
                        $expected_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                        $fee = bcdiv($req->data->app_fee, Constants::NAIRA_TO_DOLLAR, 2);
                        $provider_id = $req->data->id;
                        $payment_verified = true;
                    }
                    break;
                case 'stripe':
                    $stripe = new StripePayment;
                    $amount_in_dollars = bcdiv($request->amount_in_cents, 100, 2);
                    $expected_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                    $req = $stripe->chargeViaToken($request->amount_in_cents, $request->provider_response['id']);

                    if (($req->status === 'succeeded' && $req->paid === true)) {
                        $fee = bcdiv(bcadd(bcmul(0.029, $req->amount, 2), 30, 2), 100, 2); //2.9% + 30c convert to dollar
                        $amount_in_dollars = bcdiv($req->amount, 100, 2);
                        $expected_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
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

            AnonymousPurchaseJob::dispatch([
                'total_amount' => $amount_in_dollars,
                'total_fees' => 0,
                'payer_email' => $request->email,
                'payer_first_name' => $request->first_name,
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
}
