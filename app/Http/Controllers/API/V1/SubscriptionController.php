<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function getUserSubscriptions(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $user_id = $request->user()->id;
            $subscriptions = Subscription::with('subscriptionable', 'subscriptionable.cover', 'subscriptionable.prices', 'subscriptionable.prices.continent', 'subscriptionable.prices.country', 'subscriptionable.categories')->where('status', 'active')->whereHas('userable', function (Builder $query) use ($user_id) {
                $query->where('user_id', $user_id);
            })->get();

            return $this->respondWithSuccess('Subscriptions retrieved successfully', [
                'subscriptions' => $subscriptions,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function update(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['public_id' => $public_id]), [
                'public_id' => ['required', 'string', 'exists:subscriptions,public_id'],
                'auto_renew' => ['sometimes', 'required', 'numeric', 'integer', 'min:0', 'max:1'],
            ]);
            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $user_id = $request->user()->id;
            $subscription = Subscription::with('subscriptionable', 'subscriptionable.cover', 'subscriptionable.owner', 'subscriptionable.prices', 'subscriptionable.prices.continent', 'subscriptionable.prices.country', 'subscriptionable.categories')->where('public_id', $public_id)->whereHas('userable', function (Builder $query) use ($user_id) {
                $query->where('user_id', $user_id);
            })->first();

            if (is_null($subscription)) {
                return $this->respondBadRequest('You do not have permission to update this subscription');
            }

            if (isset($request->auto_renew) && ! is_null($request->auto_renew)) {
                $subscription->auto_renew = $request->auto_renew;
            }

            $subscription->save();

            return $this->respondWithSuccess('Subscription updated successfully', [
                'subscription' => $subscription,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
