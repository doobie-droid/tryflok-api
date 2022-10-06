<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Jobs\Payment\FundWallet as FundWalletJob;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Payment\Providers\ApplePay\ApplePay;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use App\Services\Payment\Providers\Stripe\Stripe as StripePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class WalletController extends Controller
{
    public function fundWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'username' => ['sometimes', 'string',],
                'provider' => ['required', 'string', 'in:flutterwave,apple,stripe'],
                'provider_response' => ['required'],
                'provider_response.product_id' => ['required_if:provider,apple'],
                'provider_response.receipt_data' => ['required_if:provider,apple', 'string'],
                'provider_response.transaction_id' => ['required_if:provider,flutterwave'],
                'provider_response.id' => ['required_if:provider,stripe', 'string'],
                'amount_in_cents' => ['required_if:provider,stripe', 'integer'],
                'expected_flk_amount' => ['required', 'integer', 'min:1'],
                'fund_type' => ['sometimes', 'string', 'in:tip,self'],
                'funder_name' => ['sometimes', 'nullable', 'string'],
                'fund_note' => ['sometimes', 'nullable', 'string', 'max: 300'],
                'originating_content_id' => ['sometimes', 'nullable', 'string', 'exists:contents,id'],
                'originating_client_source' => ['sometimes', 'nullable', 'string', 'in:web,ios,android'],
                'originating_currency' => ['sometimes', 'nullable', 'string']
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = User::where('email', $request->username)->orWhere('username', $request->username)->first();
            if (is_null($user)) {
                $user = $request->user();
            }

            if (is_null($user)) {
                return $this->respondBadRequest('Please provide a valid username, email, or authentication header');
            }

            Log::info("User attempted purchase began");
            Log::info($user);
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
                case 'apple':
                    $apple = new ApplePay;
                    $product_id = $request->provider_response['product_id'];
                    $req = $apple->verifyTransaction($request->provider_response['receipt_data']);
                    if ($req->status === 0) {
                        if ($product_id !== $req->receipt->in_app[0]->product_id) {
                            Log::info($request);
                            Log::info("Product ID supplied [{$product_id}] is not same that was paid for [{$req->receipt->in_app[0]->product_id}].");
                        } else {
                            $amount_in_dollars = ApplePay::PRODUCT_TO_AMOUNT[$product_id];
                            $expected_flk_based_on_amount = ApplePay::PRODUCT_TO_FLC[$product_id];
                            $fee = bcdiv(bcmul(30, $amount_in_dollars, 2), 100, 2);
                            $provider_id = $req->receipt->in_app[0]->transaction_id;
                            $payment_verified = true;
                        } 
                    }
                    break;
                default:
                    return $this->respondBadRequest('Invalid provider specified');
            }

            if (!$payment_verified) {
                return $this->respondBadRequest('Payment provider did not verify payment');
            }
            $min_variation = $expected_flk_based_on_amount - bcmul($expected_flk_based_on_amount, .03, 2);
            $max_variation = $expected_flk_based_on_amount + bcmul($expected_flk_based_on_amount, 0.03, 2);
            if ($request->expected_flk_amount < $min_variation || $request->expected_flk_amount > $max_variation) {
                return $this->respondBadRequest("Flok Cowrie conversion is not correct. Expects +/-3% of {$expected_flk_based_on_amount} for \${$amount_in_dollars} but got {$request->expected_flk_amount}");
            }
            FundWalletJob::dispatch([
                'user' => $user,
                'wallet' => $user->wallet()->first(),
                'provider' => $request->provider,
                'provider_id' => $provider_id,
                'amount' => $amount_in_dollars,
                'flk' => $request->expected_flk_amount,
                'fee' => $fee,
                'fund_type' => $request->fund_type,
                'funder_name' => $request->funder_name,
                'fund_note' => $request->fund_note,
                'originating_currency' => $request->originating_currency,
                'originating_content_id' => $request->originating_content_id,
                'originating_client_source' => $request->originating_client_source,
            ]);
            
            Log::info("User attempted purchase was successful");
            return $this->respondWithSuccess('Payment received successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function withdrawFromWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'amount_in_flk' => ['required', 'integer', 'min:100'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user_id = $request->user()->id;
            $user = User::
            whereHas('digiversesCreated', function (Builder $query) use ($user_id) {
                $query->where('is_available', 1)
                ->where('approved_by_admin', 1)
                ->whereNull('archived_at')
                ->where('user_id', $user_id)
                ->whereHas('contents', function (Builder $query) use ($user_id){
                    $query->where('is_available', 1)
                    ->where('approved_by_admin', 1)
                    ->whereNull('archived_at')
                    ->where('user_id', $user_id);
                })
                ->orWhereHas('collections', function (Builder $query) use ($user_id) {
                    $query->where('is_available', 1)
                    ->where('approved_by_admin', 1)
                    ->whereNull('archived_at')
                    ->where('user_id', $user_id)
                        ->whereHas('contents', function (Builder $query) use ($user_id){
                            $query->where('is_available', 1)
                            ->where('approved_by_admin', 1)
                            ->whereNull('archived_at')
                            ->where('user_id', $user_id);
                        });  
                });                   
            })
            ->first();

            if ( is_null($user)) {
                return $this->respondBadRequest('You need to have a published content before you can withdraw from your wallet');
            }

            $payment_account = $request->user()->paymentAccounts()->first();
            if (is_null($payment_account)) {
                return $this->respondBadRequest('You need to add a payment account before you can withdraw from your wallet');
            }

            $wallet_balance = (float) $request->user()->wallet->balance;
            $total_amount_in_flk = $request->amount_in_flk;

            if ((float) $total_amount_in_flk > $wallet_balance) {
                return $this->respondBadRequest('Your wallet balance is too low to make this withdrawal');
            }
            
            
            $newWalletBalance = bcsub($request->user()->wallet->balance, $total_amount_in_flk, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $request->user()->wallet->id,
                'amount' => $total_amount_in_flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'deduct',
                'details' => 'Withdrawal from wallet',
            ]);
            $request->user()->wallet->balance = $newWalletBalance;
            $request->user()->wallet->save();
            // create a payout for the user
            $amount_to_withdraw_in_dollars = bcdiv($total_amount_in_flk, 100, 6);
            $request->user()->payouts()->create([
                'amount' => bcmul($amount_to_withdraw_in_dollars, 1 - Constants::WALLET_WITHDRAWAL_CHARGE),
            ]);

            return $this->respondAccepted('Withdrawal successful. You should receive your money soon.');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later');
        }
    }

    public function payViaWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
                'items.*.price' => ['required'],
                'items.*.price.amount' => ['required', 'numeric', 'min:0'],
                'items.*.price.id' => ['required', 'string','exists:prices,id'],
                'items.*.price.interval' => ['required', 'string', 'in:monthly,one-off'],
                'items.*.price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
                'items.*.originating_client_source' => ['sometimes', 'nullable', 'string', 'in:web,ios,android'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $total_amount_in_dollars = 0;
            foreach ($request->items as $item) {
                $price = Price::where('id', $item['price']['id'])->first();
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

            $total_amount_in_dollars = (float) $total_amount_in_dollars;//convert from creator dollars to flk
            $total_amount_in_flk = (float) bcmul($total_amount_in_dollars, 100, 2);
            $wallet_balance = (float) $request->user()->wallet->balance;

            if ($total_amount_in_flk > $wallet_balance) {
                return $this->respondBadRequest('Your wallet balance is too low to make this purchase. Please fund your wallet with Flok Cowries and try again.');
            }

            $newWalletBalance = bcsub($request->user()->wallet->balance, $total_amount_in_flk, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $request->user()->wallet->id,
                'amount' => $total_amount_in_flk,
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
                'provider_id' => $transaction->id,
                'items' => $request->items,
            ]);

            return $this->respondAccepted("Items queued to be added to user's library.");
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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

            $transactions = $request->user()->wallet->transactions()->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Transactions retrieved successfully', [
                'transactions' => $transactions,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
