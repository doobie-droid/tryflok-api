<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Http\Resources\UserResource;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Price;
use App\Models\Content;
use App\Models\Collection;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Payment\Payment as PaymentProvider;
use App\Services\Payment\Providers\Stripe\Stripe as StripePayment;
use App\Jobs\Payment\FundWallet as FundWalletJob;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Constants\Constants;


class WalletController extends Controller
{
    public function fundWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'provider' => ['required', 'string', 'regex:(flutterwave|apple|stripe)'],
                'provider_response' => ['required'],
                'provider_response.product_id' => ['required_if:provider,apple'],
                'provider_response.receipt_data' => ['required_if:provider,apple', 'string'],
                'provider_response.transaction_id' => ['required_if:provider,flutterwave'],
                'provider_response.id' => ['required_if:provider,stripe', 'string'],
                'amount_in_cents' => ['required_if:provider,stripe', 'integer'],
                'expected_akc_amount' => ['required', 'integer', 'min:1'],
            ]);
    
            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            //the provider is being built in the cases in case an invalid provider passes through validation
            switch ($request->provider) {
                case "flutterwave":
                    $flutterwave = new PaymentProvider($request->provider);
                    $req = $flutterwave->verifyTransaction($request->provider_response['transaction_id']);
                    if (($req->status === "success" && $req->data->status === "successful")) {
                        $amount_in_dollars = bcdiv($req->data->amount, 505,2);
                        $expected_akc_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                        $min_variation = $expected_akc_based_on_amount - bcmul($expected_akc_based_on_amount, bcdiv(3,100,2), 2);
                        $max_variation = $expected_akc_based_on_amount + bcmul($expected_akc_based_on_amount,bcdiv(3,100,2), 2);
                        if ($request->expected_akc_amount < $min_variation || $request->expected_akc_amount > $max_variation) {
                            return $this->respondBadRequest("AKC conversion is not correct. Expects +/-3% of " . $expected_akc_based_on_amount . " for " . $req->data->amount . " Naira but got " . $request->expected_akc_amount);
                        }
                        FundWalletJob::dispatch([
                            'user' => $request->user(),
                            'wallet' => $request->user()->wallet()->first(),
                            'provider' => $request->provider,
                            'provider_id' => $req->data->id,
                            'amount' => $amount_in_dollars,
                            'akc' => $request->expected_akc_amount,
                            'fee' => bcdiv($req->data->app_fee, 505, 2)
                        ]);
                    } else {
                        return $this->respondBadRequest("Invalid transaction id provided for flutterwave");
                    }
                    break;
                case "stripe":
                    $stripe = new StripePayment;
                    $amount_in_dollars = bcdiv($request->amount_in_cents, 100,2);
                    $expected_akc_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                    $min_variation = $expected_akc_based_on_amount - bcmul($expected_akc_based_on_amount, bcdiv(3,100,2), 2);
                    $max_variation = $expected_akc_based_on_amount + bcmul($expected_akc_based_on_amount,bcdiv(3,100,2), 2);

                    if ($request->expected_akc_amount < $min_variation || $request->expected_akc_amount > $max_variation) {
                        return $this->respondBadRequest("AKC conversion is not correct. Expects +/-3% of " . $expected_akc_based_on_amount . " for " . $request->amount_in_cents . " cents but got " . $request->expected_akc_amount);
                    }

                    $req = $stripe->chargeViaToken($request->amount_in_cents, $request->provider_response['id']);
                    if (($req->status === "succeeded" && $req->paid === true)) {
                        FundWalletJob::dispatch([
                            'user' => $request->user(),
                            'wallet' => $request->user()->wallet()->first(),
                            'provider' => $request->provider,
                            'provider_id' => $req->id,
                            'amount' => $amount_in_dollars,
                            'akc' => $request->expected_akc_amount,
                            'fee' => bcdiv(bcadd(bcdiv(bcmul(2.9, $request->amount_in_cents, 2), 100, 2), 30, 2), 100, 2),//2.9 % + 30,
                        ]);
                    } else {
                        return $this->respondBadRequest("Invalid token id provided for stripe");
                    }
                    break;
                case "apple":
                    $apple = new PaymentProvider($request->provider);
                    $req = $apple->verifyTransaction($request->provider_response['receipt_data']);
                    if ($req->status === 0) {
                        if ($request->provider_response['product_id'] !== $req->receipt->in_app[0]->product_id) {
                            return $this->respondBadRequest("Product ID supplied [" . $request->provider_response['product_id'] . "] is not same that was paid for [" . $req->receipt->in_app[0]->product_id . "].");
                        }
                        $product_ids_to_amount = [
                            '250_akc' => 3,
                            '500_akc' => 6,
                            '1000_akc' => 12,
                            '3000_akc' => 36,
                            '5000_akc' => 60,
                            '10000_akc' => 120,
                        ];
                        $ekc_to_dollar = bcadd(1, bcdiv((30), 100 - 30), 2);
                        $amount_in_dollars = $product_ids_to_amount[$request->provider_response['product_id']];
                        FundWalletJob::dispatch([
                            'user' => $request->user(),
                            'wallet' => $request->user()->wallet()->first(),
                            'provider' => $request->provider,
                            'provider_id' => $req->receipt->in_app[0]->transaction_id,
                            'amount' => $amount_in_dollars,
                            'akc' => $request->expected_akc_amount,
                            'fee' => bcdiv(bcmul(30, $amount_in_dollars, 2), 100, 2),
                        ]);
                    } else {
                        return $this->respondBadRequest("Invalid payment receipt provided for apple pay");
                    }
                    break;
                default:
                    return $this->respondBadRequest("Invalid provider specified");
            }
    
            return $this->respondWithSuccess("Payment received successfully");
        }  catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function payViaWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required',],
                'items.*.id' => ['required', 'string', ],
                'items.*.type' => ['required', 'string', 'regex:(collection|content)',],
                'items.*.price' => ['required',],
                'items.*.price.amount' => ['required', 'numeric',],
                'items.*.price.id' => ['required', 'string','exists:prices,id'],
                'items.*.price.interval' => ['sometimes', 'nullable', 'string', 'regex:(month|one-off)',],
                'items.*.price.interval_amount' => ['sometimes', 'nullable', 'numeric',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $total_amount_in_dollars = 0;
            foreach ($request->items as $item) {
                $price = Price::where('id', $item['price']['id'])->first();
                //validate that the content or collection exists
                $itemModel = NULL;
                switch ($item['type']) {
                    case 'content':
                        $itemModel = Content::where('id', $item['id'])->first();
                        break;
                    case 'collection':
                        $itemModel = Collection::where('id', $item['id'])->first();
                        break;
                }
                if (is_null($itemModel)) {
                    return $this->respondBadRequest("You selected an item that does not exist.");
                }
                //add total price
                $total_amount_in_dollars = bcadd($total_amount_in_dollars, $price->amount, 2);
            }
            
            $total_amount_in_dollars = (float) $total_amount_in_dollars;//convert from creator dollars to AKC
            $total_amount_in_akc = (float) bcmul($total_amount_in_dollars, 100, 2);
            $wallet_balance = (float) $request->user()->wallet->balance;
            
            if ($total_amount_in_akc > $wallet_balance) {
                return $this->respondBadRequest("Your wallet balance is too low to make this purchase. Please fund your wallet with Akiddie Cowries and try again.");
            }

            $newWalletBalance = bcsub($request->user()->wallet->balance, $total_amount_in_akc, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $request->user()->wallet->id,
                'amount' => $total_amount_in_akc,
                'balance' => $newWalletBalance,
                'transaction_type' => 'deduct',
                'details' => 'Deduct from wallet to pay for items',
            ]);
            $request->user()->wallet->balance = $newWalletBalance;
            $request->user()->wallet->save();
            PurchaseJob::dispatch([
                'total_amount' => $total_amount_in_dollars,
                'total_fees' => 0,
                'user' => $request->user()->toArray(),
                'provider' => 'wallet',
                'provider_id' => $transaction->public_id,
                'items' => $request->items,
            ]);
            $this->setStatusCode(202);
            return $this->respondWithSuccess("Items queued to be added to user's library.");
        }  catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getTransactions(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $transactions = $request->user()->wallet->transactions()->orderBy('created_at', 'asc')->paginate($limit, array('*'), 'page', $page);

            return $this->respondWithSuccess("Transactions retrieved successfully",[
                'transactions' => $transactions,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }
}
