<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Http\Resources\UserResource;
use App\Services\Storage\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Userable;
use App\Models\Content;
use App\Models\Collection;
use App\Models\Cart;
use App\Models\Configuration;
use App\Models\PaymentAccount;
use App\Models\Payout;
use App\Models\Notification;
use App\Events\User\ConfirmEmail as ConfirmEmailEvent;
use App\Jobs\User\Payout as PayoutJob;
use PragmaRX\Countries\Package\Countries as PragmarxCountries;
use App\Jobs\Payment\Payout as PayoutToCreatorJob;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\UserResourceWithSensitive;
use \Carbon\Carbon;
use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use App\Http\Resources\NotificationResource;
use App\Constants\Constants;

class UserController extends Controller
{
    public function getAll(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            $search = urldecode($request->query('search', ''));
            $role = urldecode($request->query('role', ''));

            $users = User::where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')->orWhere('email', 'LIKE', $search . '%');
            });
            
            if ($role != "" && !is_null($role)) {
                $users = $users->whereHas('roles', function (Builder $query) use ($role) {
                    $query->where('name', $role);
                });
            }
           
            $users = $users->with('roles', 'profile_picture')->orderBy('created_at', 'asc')
            ->paginate($limit, array('*'), 'page', $page);

            return $this->respondWithSuccess("Users retrieved successfully",[
                'users' => UserResource::collection($users),
                'current_page' => $users->currentPage(),
                'items_per_page' => $users->perPage(),
                'total' => $users->total(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getSingle(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $user = User::with('roles', 'profile_picture')->where('id', $id)->first();
            return $this->respondWithSuccess("User retrieved successfully", [
                'user' => new UserResource($user),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getAccount(Request $request)
    {
        try {
            $user = User::with('roles', 'profile_picture', 'wallet')->where('id', $request->user()->id)->first();
            return $this->respondWithSuccess("Account retrieved successfully", [
                'user' => new UserResourceWithSensitive($user),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old' => ['required', 'string'],
                'password' => ['required', 'string', 'min:4', 'confirmed'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            $user = $request->user();

            if (Hash::check($request->old, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();
                $user = User::with('roles', 'profile_picture', 'wallet')->where('id', $user->id)->first();
                return $this->respondWithSuccess("Password changed successfully", ['user' => new UserResourceWithSensitive($user)]);
            } else {
                return $this->respondBadRequest("Password provided is not correct.");
            }
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function updateBasicData(Request $request)
    {
        try {
            $validator = Validator::make(array_merge($request->all()), [
                'name' => ['sometimes', 'nullable', 'string', ],
                'email' => ['sometimes', 'nullable', 'email', 'unique:users,email', ],
                'dob' => ['sometimes', 'nullable', 'date'],
                'profile_picture' => ['sometimes', 'nullable', 'image', ],
                'creator_bio' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $user = $request->user();

            $user->update($request->only(['name', 'email', 'dob', 'creator_bio']));
            if (!is_null($request->email)) {
                $user->email_token = Str::random(16);
                $user->email_verified = 0;
                $user->save();
                event(new ConfirmEmailEvent($user));
            }

            if ($request->hasFile('profile_picture')) {
                //check if the user has an already existing profile photo
                $profilePicture = $user->profilePicture();
                //if they do, delete
                if (!is_null($profilePicture)) {
                    if ($profilePicture->storage_provider === 'cloudinary') {
                        $storage = new Storage($profilePicture->storage_provider);
                        $storage->delete($profilePicture->storage_provider_id);
                    }
                    $profilePicture->forceDelete();
                }
                //add new image
                $storage = new Storage('cloudinary');
                $uploadedImageData = $storage->upload($request->file('profile_picture')->getRealPath(),'users/profile');
                $user->assets()->create([
                    'storage_provider' => 'cloudinary',
                    'storage_provider_id' => $uploadedImageData['storage_provider_id'],
                    'url' => $uploadedImageData['url'],
                    'purpose' => 'profile-picture',
                    'asset_type' => 'image',
                    'mime_type' => $request->file('profile_picture')->getMimeType(),
                ]);
            }
            $user = User::with('roles', 'profile_picture', 'wallet')->where('id', $user->id)->first();
            return $this->respondWithSuccess("User updated successfully",[
                'user' => new UserResourceWithSensitive($user),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function refreshToken(Request $request)
    {
        $user = User::with('roles', 'profile_picture', 'wallet')->where('id', $request->user()->id)->first();
        return $this->respondWithSuccess("Token refreshed successfully",[
            'user' => new UserResourceWithSensitive($user),
            'token' => auth()->refresh(),
        ]);
    }

    public function addItemsToWishList(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required',],
                'items.*.id' => ['required', 'string', ],
                'items.*.type' => ['required', 'string', 'regex:(collection|content)',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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
                    continue;
                }
                //ensure the user has not purchased the item or added it already
                $wishlistItem = Userable::where('user_id', $request->user()->id)->where('userable_type', $item['type'])->where('userable_id', $itemModel->id)->first();
                if (is_null($wishlistItem)) {
                    Userable::create([
                        'user_id' => $request->user()->id,
                        'status' => 'wishlist',
                        'userable_type' => $item['type'],
                        'userable_id' => $itemModel->id,
                    ]);
                }
            }

            return $this->respondWithSuccess("Items successfully added to wishlist");
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function removeItemsFromWishlist(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required',],
                'items.*.id' => ['required', 'string', ],
                'items.*.type' => ['required', 'string', 'regex:(collection|content)',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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
                    continue;
                }
                //ensure the user has not purchased the item or added it already
                $wishlistItem = Userable::where('user_id', $request->user()->id)->where('userable_type', $item['type'])->where('userable_id', $itemModel->id)->first();
                if (!is_null($wishlistItem)) {
                    $wishlistItem->delete();
                }
            }
            return $this->respondWithSuccess("Items successfully removed from wishlist");
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getWishlist(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();
            if (is_null($user)) {
                return $this->respondBadRequest("Invalid user ID supplied");
            }

            $wishlist = Userable::where('user_id', $user->id)->where('status', 'wishlist')->with('userable', 'userable.prices', 'userable.prices.continent', 'userable.prices.country', 'userable.categories', 'userable.cover')->get();

            return $this->respondWithSuccess("Wishlist retrieved successfully", [
                'wishlist' => $wishlist,
                'user' => new UserResource($user),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getSales(Request $request, $user_id)
    {
        try {
            $user = User::where('id', $user_id)->first();
            if (is_null($user)) {
                return $this->respondBadRequest("Invalid user ID supplied");
            }

            if (
                $request->user()->id !== $user_id &&
                !$request->user()->hasRole(Roles::ADMIN) &&
                !$request->user()->hasRole(Roles::SUPER_ADMIN)
            ) {
                return $this->respondBadRequest("You do not have permission to view this user's sales");
            }

            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;

            $sales = $user->sales()->with('saleable')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            return $this->respondWithSuccess("Sales retrieved successfully",[
                'sales' => $sales,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getNotifications(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $max_items_count = Constants::MAX_ITEMS_LIMIT;
            $validator = Validator::make([
                'page' => $page,
                'limit' => $limit,
            ], [
                'page' => ['required', 'integer', 'min:1',],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}",],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $notifications = $request->user()->notifications()->orderBy('notifications.created_at', 'desc')
            ->paginate($limit, array('*'), 'page', $page);

            return $this->respondWithSuccess('Notifications retrieved successfully',[
                'notifications' => NotificationResource::collection($notifications),
                'current_page' => $notifications->currentPage(),
                'items_per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function markAllNotificationsAsRead(Request $request) {
        try {
            $notifications = $request->user()->notifications()->where('viewed', 0)->get();
            foreach ($notifications as $notification) {
                $notification->viewed = 1;
                $notification->save();
            }

            return $this->respondWithSuccess('Notifications have been successfully marked as read');
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getPurchasedItems(Request $request)
    {
        try {
            $items = Userable::where('user_id', $request->user()->id)->where(function ($query) {
                $query->where('status', 'available')->orWhere('status', 'subscription-ended')->orWhere('status', 'content-deleted');
            })->with('userable', 'userable.cover','userable.prices', 'userable.prices.continent', 'userable.prices.country', 'userable.categories')->with('subscription')->get();

            return $this->respondWithSuccess("Items retrieved successfully", [
                'items' => $items,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function addItemsToCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => ['required',],
                'items.*.id' => ['required', 'string', ],
                'items.*.type' => ['required', 'string', 'regex:(collection|content)',],
                'items.*.quantity' => ['sometimes', 'required', 'numeric', 'min:1',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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
                    continue;
                }
                $cartItem = Cart::where('user_id', $request->user()->id)
                ->where('cartable_id', $itemModel->id)
                ->where('cartable_type', $item['type'])->where('checked_out', 0)->first();
                if (is_null($cartItem)) {
                    Cart::create([
                        'user_id' => $request->user()->id,
                        'cartable_id' => $itemModel->id,
                        'cartable_type' => $item['type'],
                        'quantity' => array_key_exists('quantity', $item) ? $item['quantity'] : 1,
                        'status' => 'in-cart',
                    ]);
                }
            }

            $items = $request->user()->carts()->with('cartable', 'cartable.prices', 'cartable.prices.continent', 'cartable.prices.country', 'cartable.categories', 'cartable.cover')->where('checked_out', 0)->get();

            foreach ($items as $key => $item) {
                if (is_null($item->cartable)) {
                    $item->delete();
                    $items->forget($key);
                } 
            }
            return $this->respondWithSuccess("Cart items added successfully",[
                'cart' => $items,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getCartItems(Request $request)
    {
        try {
            $items = $request->user()->carts()->with('cartable', 'cartable.cover', 'cartable.prices', 'cartable.prices.continent', 'cartable.prices.country', 'cartable.categories')->where('checked_out', 0)->get();

            return $this->respondWithSuccess("Cart items retrieved successfully",[
                'cart' => $items,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function removeItemsFromCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => ['required',],
                'items.*.id' => ['required', 'string', ],
                'items.*.type' => ['required', 'string', 'regex:(collection|content)',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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
                    continue;
                }
                $cartItem = Cart::where('user_id', $request->user()->id)
                ->where('cartable_id', $itemModel->id)
                ->where('cartable_type', $item['type'])->where('checked_out', 0)->first();
                if (!is_null($cartItem)) {
                    $cartItem->delete();
                }
            }

            $items = $request->user()->carts()->with('cartable', 'cartable.cover', 'cartable.prices', 'cartable.prices.continent', 'cartable.prices.country', 'cartable.categories')->where('checked_out', 0)->get();

            return $this->respondWithSuccess("Cart items deleted successfully",[
                'cart' => $items,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function generateAuthOtp(Request $request)
    {
        try {
            //otp lasts only two minutes
            $otp = $request->user()->otps()->create([
                'purpose' => 'authentication',
                'code' => Str::random(10),
                'expires_at' => now()->addSeconds(60),
            ]);

            return $this->respondWithSuccess("Otp retrieved successfully",[
                'otp' => $otp,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function requestPayout(Request $request)
    {
        try {

            $start = Configuration::where('name', 'payout-start')->first();
            $startDate = Carbon::createFromFormat('Y-m-d',  $start->value); 
            $end = Configuration::where('name', 'payout-end')->first();
            $endDate = Carbon::createFromFormat('Y-m-d',  $end->value); 
           
            if (!now()->between($startDate, $endDate)) {
                return $this->respondBadRequest("You can only request for payouts whithin the payout period that happens once a month. The next payout period is " . $startDate->format('l, jS \\of F, Y') . ' to ' . $endDate->format('l, jS \\of F, Y'));
            }

            $lastPayout = $request->user()->payouts()->latest()->first();
            if (!is_null($lastPayout)) {
                $countSales = $request->user()->sales()->whereDate('created_at', '>', $lastPayout->end)->count();
                if ($countSales == 0) {
                    return $this->respondBadRequest("You have not made any sales since last payout so you cannot request a payout");
                }
                $start = clone $lastPayout->end;
            } else {
                $firstSale = $request->user()->sales()->first();
                if (is_null($firstSale)) {
                    return $this->respondBadRequest("You have not made any sales yet so you cannot request a payout");
                }
                $start = clone $firstSale->created_at;
            }

            PayoutJob::dispatch([
                "start" => $start,
                "user" => $request->user(),
            ]);
            $this->setStatusCode(202);
            return $this->respondWithSuccess("Your payout request has been received, it will be displayed in your payouts shortly.");

        }  catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getPayouts(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;

            $payouts = $request->user()->payouts()->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            return $this->respondWithSuccess("Payouts retrieved successfully",[
                'payouts' => $payouts,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function addPaymentAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => ['required', 'string', 'regex:(manual|flutterwave)',],
                'identifier' => ['required', 'string'],
                'country_code' => ['required', 'string'],
                'currency_code' => ['required', 'string'],
                'bank_code' => ['string', 'required_if:provider,flutterwave'],
                'bank_name' => ['string', 'required_if:provider,flutterwave,manual'],
                'branch_code' => ['string', 'required_if:country_code,GH,UG,TZ'],
                'branch_name' => ['string','required_if:country_code,GH,UG,TZ'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            //make sure a valid country code was supplied
            $countries = new PragmarxCountries();
            $country = $countries->where('cca2', $request->country_code)->first();
            if (is_null($country)) {
                return $this->respondBadRequest("Invalid country code supplied");
            }
            //make sure a valid currency code was supplied
            $validCurrency = false;
            foreach ($countries->where('cca2', $request->country_code)->first()->currencies as $currency) {
                if ($request->currency_code === $currency) {
                    $validCurrency = true;
                    break;
                }
            }
            if (!$validCurrency) {
                return $this->respondBadRequest("Invalid currency code supplied");
            }

            if ($request->provider === 'flutterwave' && in_array($request->country_code, ['GH', 'UG', 'TZ'])) {
                $validator = Validator::make($request->all(), [
                    'branch_code' => ['string', 'required'],
                    'branch_name' => ['string', 'required'],
                ]);
    
                if ($validator->fails()) {
                    return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
                }
            }

            $accountData = [
                'identifier' => $request->identifier,
                'provider' => $request->provider,
                'country_code' => $request->country_code,
                'currency_code' => $request->currency_code,
            ];

            if (!is_null($request->bank_name)) {
                $accountData['bank_name'] = $request->bank_name;
            }

            if (!is_null($request->bank_code)) {
                $accountData['bank_code'] = $request->bank_code;
            }

            if (!is_null($request->branch_code)) {
                $accountData['branch_code'] = $request->branch_code;
            }

            if (!is_null($request->branch_name)) {
                $accountData['branch_name'] = $request->branch_name;
            }

            $paymentAccount = $request->user()->paymentAccounts()->create($accountData);

            return $this->respondWithSuccess("Payoutment account created successfully",[
                'payment_account' => $paymentAccount,
                'payment_accounts' => $request->user()->paymentAccounts()->get(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function addStripePaymentAccount(Request $request)
    {
        try {
            $user = User::where('id', $request->query('id'))->first();
            $country = strtoupper($request->query('country', 'US'));
            if (is_null($user)) {
                return $this->respondBadRequest("User does not exist");
            }

            $allowed_country_codes = ['us', 'gb', 'ca', 'au', 'fr', 'in'];
            if (!in_array(strtolower($country), $allowed_country_codes)) {
                return $this->respondBadRequest("Country is not supported for Stripe Payouts. Permitted countries are United States (US), United Kingdom (GB), Canada (CA), Australia (AU), India (IN), and France (FR)");
            }

            $countries = new PragmarxCountries();
            $currency = '';
            if ($countries->where('cca2', $country)->count() === 0) {
                return $this->respondBadRequest("Invalid country code supplied");
            }
            foreach ($countries->where('cca2', $country)->first()->currencies as $countryCurrency) {
                $currency = $countryCurrency;
                break;
            }

            //first check if user already has stripe setup
            $stripeAccount = $user->paymentAccounts()->where('provider', 'stripe')->first();
            \Stripe\Stripe::setApiKey(config('payment.providers.stripe.secret_key'));
            if (is_null($stripeAccount)) {
                
                $account = \Stripe\Account::create([
                    'country' => $country,
                    'type' => 'express',
                    'email' => $user->email,
                ]);
            
                $accountData = [
                    'identifier' => $account->id,
                    'provider' => 'stripe',
                    'country_code' => $country,
                    'currency_code' => $currency,
                ];
                $stripeAccount = $user->paymentAccounts()->create($accountData);
            } else {
                if ($country !== $stripeAccount->country_code) {
                    //delete the account
                    $stripe = new \Stripe\StripeClient(config('payment.providers.stripe.secret_key'));
                    $stripe->accounts->delete($stripeAccount->identifier, []);
                    //create a new one
                    $account = \Stripe\Account::create([
                        'country' => $country,
                        'type' => 'express',
                        'email' => $user->email,
                    ]);
                    //update payment account id
                    $stripeAccount->identifier = $account->id;
                    $stripeAccount->country_code = $country;
                    $stripeAccount->currency_code = $currency;
                    $stripeAccount->save();
                }
            }

            $account_links = \Stripe\AccountLink::create([
                'account' => $stripeAccount->identifier,
                'refresh_url' => env('BACKEND_URL', 'https://api.akiddie.com.ng/') . 'api/v1/payments/stripe/connect?id=' . $user->id . '&country=' . $country,
                'return_url' => env('CREATOR_URL', 'https://creators.akiddie.com.ng/') . 'creator/' . $user->id . '/account',
                'type' => 'account_onboarding',
            ]);
            
            return redirect()->to($account_links->url);
        }  catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }


    public function getPaymentAccount(Request $request) 
    {
        try {
            return $this->respondWithSuccess("Payoutment accounts retrieved successfully",[
                'payment_accounts' => $request->user()->paymentAccounts()->get(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function removePaymentAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'accounts' => ['required',],
                'accounts.*' => ['required', 'string', 'exists:payment_accounts,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            foreach ($request->accounts as $account_id) {
                $accountModel = PaymentAccount::where('id', $account_id)->where('user_id', $request->user()->id)->first();
                $accountModel->delete();
            }

            return $this->respondWithSuccess("Payoutment accounts deleted successfully",[
                'payment_accounts' => $request->user()->paymentAccounts()->get(),
            ]);
        }  catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function cashoutPayout(Request $request)
    {
        try {
            $validator = Validator::make(array_merge($request->all()), [
                'payout_id' => ['required', 'string', 'exists:payouts,id'],
                'payment_account_id' => ['required', 'string', 'exists:payment_accounts,id', ],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            $payout = Payout::where('id', $request->payout_id)->where('user_id', $request->user()->id)->first();
            if (is_null($payout)) {
                return $this->respondBadRequest("The payout provided does not belong to you");
            }

            $payment_account = PaymentAccount::where('id', $request->payment_account_id)->where('user_id', $request->user()->id)->first();
            if (is_null($payment_account)) {
                return $this->respondBadRequest("The payment account provided does not belong to you");
            }

            if ($payout->claimed === 1) {
                return $this->respondBadRequest("This payout has already been claimed");
            }

            //make sure request was not made in the last six hours
            if (!is_null($payout->last_payment_request) && $payout->last_payment_request->gt(now()->subHours(6))) {
                return $this->respondBadRequest("You need to wait 6 hours to make another cashout request for this payout");
            }
            //dispatch to payment provider to payout
            if ($payment_account->provider === "manual") {
                $payout->handler = "manual";
                $payout->last_payment_request = now();
                $payout->save();
            } else {
                $payout->last_payment_request = now();
                $payout->handler = $payment_account->provider;
                $payout->save();
                //dispatch to be handled by provider
                PayoutToCreatorJob::dispatch([
                    'payout' => $payout,
                    'payment_account' => $payment_account,
                ]);
            }

            $this->setStatusCode(202);
            return $this->respondWithSuccess("Details received successfully, you should receive your payout in the next 24 hours");
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    function getSignedCookies(Request $request)
    {
        try {
            $cloudFrontClient = new CloudFrontClient([
                'profile' => 'default',
                'version' => '2014-11-06',
                'region' => 'us-east-1'
            ]);

            $expires = time() + (2 * 60 * 60); //2 hours from now(in seconds)
            $resource = env('PRIVATE_AWS_CLOUDFRONT_URL') . '/*';
            $policy = <<<POLICY
                        {
                            "Statement": [
                                {
                                    "Resource": "{$resource}",
                                    "Condition": {
                                        "DateLessThan": {"AWS:EpochTime": {$expires}}
                                    }
                                }
                            ]
                        }
                        POLICY;
            $result = $cloudFrontClient->getSignedCookie([
                'policy' => $policy,
                'private_key' => base64_decode(env('AWS_CLOUDFRONT_PRIVATE_KEY')),
                'key_pair_id' => env('AWS_CLOUDFRONT_KEY_ID'),
            ]);
            $cookies = '';
            foreach ($result as $key => $value) {
                $cookies = $cookies . $key . '=' . $value . ';';
            }
            return $this->respondWithSuccess("Cookies retrieved successfully", [
                'cookies' => $cookies,
                'expires' => $expires
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }
}
