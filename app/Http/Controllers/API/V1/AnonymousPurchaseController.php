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

class AnonymousPurchaseController extends Controller
{
    public function purchases (Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'email' => ['required', 'string', 'email', 'max:255'],
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
            $wallet_balance = (float) $user->wallet->balance;

            AnonymousPurchaseJob::dispatch([
                'total_amount' => $total_amount_in_dollars,
                'total_fees' => 0,
                'purchaser_email' => $request->email,
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
}
