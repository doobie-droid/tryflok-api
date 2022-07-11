<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\Payment\Flutterwave\Purchase as FlutterwavePurchaseHandler;
use App\Jobs\Payment\Paystack\Purchase as PaystackPurchaseHandler;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Jobs\Payment\Stripe\Purchase as StripePurchaseHandler;
use App\Models\Collection;
use App\Models\Content;
use App\Services\Payment\Payment as PaymentProvider;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function getFlutterwaveBanks(Request $request)
    {
        try {
            $country_code = $request->query('country_code', 'NG');
            $allowed_country_codes = ['ng', 'gh', 'ug', 'tz'];
            if (! in_array(strtolower($country_code), $allowed_country_codes)) {
                return $this->respondBadRequest('Country is not supported for Flutterwave Payouts');
            }
            $flutterwave = new Flutterwave;
            $resp = $flutterwave->getBanks(['country_code' => $country_code]);
            return $this->respondWithSuccess('Banks retrieved successfully', [
                'banks' => $resp->data,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getFlutterwaveBankBranches(Request $request)
    {
        try {
            $id = $request->query('id');
            $flutterwave = new Flutterwave;
            $resp = $flutterwave->getBankBranch($id);
            if (isset($resp->status) && $resp->status === 'success') {
                return $this->respondWithSuccess('Bank branches retrieved successfully', [
                    'branches' => $resp->data,
                ]);
            } else {
                return $this->respondBadRequest('An invalid bank code was supplied');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function validateBankDetailsViaFlutterwave(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'account_number' => ['required', 'string'],
                'bank_code' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $flutterwave = new Flutterwave;
            $resp = $flutterwave->validateAccountNumber($request->account_number, $request->bank_code);
            if (isset($resp->status) && $resp->status === 'success') {
                return $this->respondWithSuccess('Account details resolved successfully', $resp->data);
            } else {
                return $this->respondBadRequest('Unable to resolve bank details');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function flutterwaveWebhook(Request $request)
    {
        try {
            Log::info($request->data);

            $flutterwave = new Flutterwave;
            $req = $flutterwave->verifyTransaction($request->data['id']);
            Log::info(json_encode($req));
        } catch(\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError("Oops, an error occurred. Please try again later.");
        }
    }

    public function stripeWebhook(Request $request)
    {
        try {
            Log::info($request->data);
        } catch(\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError("Oops, an error occurred. Please try again later.");
        }
    }

    public function applePayWebhook(Request $request)
    {
        try {
            Log::info($request->data);
        } catch(\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError("Oops, an error occurred. Please try again later.");
        }
    }

    /* public function paystackWebhook(Request $request)
     {
         try {
             $paystack = new PaymentProvider('paystack');
             $req = $paystack->verifyTransaction($request->data['reference']);
             if (($req->status && $req->data->status == "success")) {
                 //delegate the process
                 PaystackDelegator::dispatch($request->input());
                 return $this->respondWithSuccess("Payment received successfully");
             } else {
                 Log::info("Invalid Transaction Was Attempted");
                 Log::info($request);
                 return $this->respondBadRequest("Oops, an error occurred. Please try again later.");
             }
         } catch(\Exception $exception) {
             Log::error($exception);
             return $this->respondInternalError("Oops, an error occurred. Please try again later.");
         }
     }*/

    public function testPaymentWebhook(Request $request)
    {
        if (! App::environment('testing') && ! App::environment('local')) {
            return $this->respondBadRequest('End-point is not available');
        }
        try {
            $paystack = new PaymentProvider('test');
            $req = $paystack->verifyTransaction($request->data['reference']);
            if (($req->status && $req->data->status == 'success')) {
                //delegate the process
                PaystackDelegator::dispatch($request->input());
                return $this->respondWithSuccess('Payment received successfully');
            } else {
                Log::info('Invalid Transaction Was Attempted');
                Log::info($request);
                return $this->respondBadRequest('Oops, an error occurred. Please try again later.');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }


    public function processPaymentForProviders(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'provider' => ['required', 'string', 'in:flutterwave,paystack,stripe'],
                'provider_response' => ['required'],
                'user' => ['required'],
                'user.public_id' => ['required', 'string', 'exists:users,public_id'],
                'items' => ['required'],
                'items.*.public_id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
                'items.*.price' => ['required'],
                'items.*.price.amount' => ['required', 'numeric'],
                'items.*.price.public_id' => ['required', 'string'],
                'items.*.price.interval' => ['required', 'string', 'in:year,month,week,day,one-off'],
                'items.*.price.interval_amount' => ['required', 'numeric'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            switch ($request->provider) {
                case 'paystack':
                    PaystackPurchaseHandler::dispatch([
                        'provider_response' => $request->provider_response,
                        'user' => $request->user,
                        'items' => $request->items,
                    ]);
                    break;
                case 'flutterwave':
                    FlutterwavePurchaseHandler::dispatch([
                        'provider_response' => $request->provider_response,
                        'user' => $request->user,
                        'items' => $request->items,
                    ]);
                    break;
                case 'stripe':
                    StripePurchaseHandler::dispatch([
                        'provider_response' => $request->provider_response,
                        'dollar_amount' => $request->stripe_dollar_amount,//is in cents
                        'user' => $request->user,
                        'items' => $request->items,
                    ]);
                    break;
                default:
                    return $this->respondBadRequest('Invalid provider specified');
            }
            return $this->respondWithSuccess('Payment received successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function freeItems(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
                'items.*.price' => ['required'],
                'items.*.price.amount' => ['required', 'numeric', 'min:0', 'max:0'],
                'items.*.price.id' => ['required', 'string', 'exists:prices,id'],
                'items.*.price.interval' => ['required', 'string', 'in:monthly,one-off'],
                'items.*.price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            //check that Item is actually free. That means it's collection will also be free if it is a content
            $itemsThatCanBeAddedForFree = [];
            foreach ($request->items as $item) {
                if ($item['type'] == 'content') {
                    $itemModel = Content::where('id', $item['id'])->first();
                    if (! is_null($itemModel)) {
                        $digivierse = $itemModel->collections()->first();
                        $digiversePrice = $digivierse->prices()->first();
                        $itemPrice = $itemModel->prices()->where('id', $item['price']['id'])->first();
                        if (! is_null($itemPrice) && ! is_null($digiversePrice) && $digiversePrice->amount === 0 && $itemPrice->amount === 0) {
                            $itemsThatCanBeAddedForFree[] = $item;
                        }
                    }
                } else {
                    $itemModel = Collection::where('id', $item['id'])->first();
                    $itemPrice = $itemModel->prices()->first();
                    $itemPrice = $itemModel->prices()->where('id', $item['price']['id'])->first();
                    if (! is_null($itemPrice) && $itemPrice->amount == 0) {
                        $itemsThatCanBeAddedForFree[] = $item;
                    }
                }
            }

            PurchaseJob::dispatch([
                'total_amount' => 0,
                'total_fees' => 0,
                'user' => $request->user()->toArray(),
                'provider' => 'local',
                'provider_id' => 'FREE-' . Str::random(6),
                'items' => $itemsThatCanBeAddedForFree,
            ]);

            return $this->respondAccepted("Item queued to be added to user's library.");
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
