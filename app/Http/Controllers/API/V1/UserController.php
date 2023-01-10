<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Constants\Roles;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\RevenueResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserResourceWithSensitive;
use App\Jobs\Users\NotifyFollow as NotifyFollowJob;
use App\Jobs\Users\NotifyTipping as NotifyTippingJob;
use App\Jobs\Users\SendReferralEmails as SendReferralEmailsJob;
use App\Jobs\Users\AnonymousUserTip as AnonymousUserTipJob;
use App\Jobs\Users\ExportExternalCommunity as ExportExternalCommunityJob;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\Content;
use App\Models\PaymentAccount;
use App\Models\Subscription;
use App\Models\ExternalCommunity;
use App\Models\User;
use App\Models\UserTip;
use App\Models\Userable;
use App\Models\WalletTransaction;
use App\Rules\AssetType as AssetTypeRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PragmaRX\Countries\Package\Countries as PragmarxCountries;
use \Stripe\Account as StripeAccount;
use \Stripe\AccountLink as StripeAccountLink;
use \Stripe\Stripe;
use \Stripe\Customer;
use \Stripe\StripeClient;
use App\Services\Payment\Providers\Flutterwave\Flutterwave;
use App\Services\Payment\Providers\Stripe\Stripe as StripePayment;
use App\Models\Configuration;
use App\Imports\ExternalCommunitiesImport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function listUsers(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(' ', $keyword);
            $keywords = array_diff($keywords, ['']);

            $max_items_count = Constants::MAX_ITEMS_LIMIT;
            $validator = Validator::make([
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
            ], [
                'page' => ['required', 'integer', 'min:1'],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}"],
                'keyword' => ['sometimes', 'string', 'max:200'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $users = User::whereHas('roles', function (Builder $query) {
                $query->where('name', Roles::USER);
            });

            if (! empty($keywords)) {
                $users = $users->where(function ($query) use ($keywords) {
                    $query->where('name', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('username', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('name', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('username', 'LIKE', "%{$keywords[$i]}%");
                    }
                });
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $users = $users->with('roles', 'profile_picture')
            ->with([
                'followers' => function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                },
                'following' => function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                },
            ])
            ->withCount('followers', 'following')
            ->withCount('digiversesCreated')
            ->orderBy('created_at', 'asc')
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Users retrieved successfully', [
                'users' => UserResource::collection($users),
                'current_page' => (int) $users->currentPage(),
                'items_per_page' => (int) $users->perPage(),
                'total' => (int) $users->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function showUser(Request $request, $id)
    {
        try {

            $user = User::where('id', $id)->orWhere('username', $id)->orWhere('email', $id)->first();
   
            if (is_null($user)) {
                return $this->respondBadRequest('User not found');
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $user = User::with('roles', 'profile_picture')
            ->withCount('digiversesCreated')
            ->with([
                'followers' => function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                },
                'following' => function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                },
            ])
            ->with('tags')
            ->withCount('followers', 'following')->where('id', $user->id)->first();
            return $this->respondWithSuccess('User retrieved successfully', [
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addReferrer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $referrer = User::where('email', $request->username)->orWhere('username', $request->username)->first();
            if (is_null($referrer)) {
                return $this->respondBadRequest('Please provide a valid username or email');
            }

            if ($referrer->id === $request->user()->id) {
                return $this->respondBadRequest('You cannot add yourself as a referrer');
            }

            $request->user()->referrer_id = $referrer->id;
            $request->user()->save();

            $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $request->user()->id)->first();
            return $this->respondWithSuccess('Login successful', [
                'user' => new UserResourceWithSensitive($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function referUsers(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'emails' => ['required'],
                'emails.*.email' => ['required', 'email'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = $request->user();

            foreach( $request->emails as $email) {
                SendReferralEmailsJob::dispatch([
                    'email' => $email,
                    'referrer' => $user,
                ]);
            }
            return $this->respondWithSuccess('referral emails have been successfully sent');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function followUser(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($id === $request->user()->id) {
                return $this->respondBadRequest("You cannot follow yourself");
            }

            $user = User::where('id', $id)->first();
            $user->followers()->syncWithoutDetaching([
                $request->user()->id => [
                    'id' => Str::uuid(),
                ],
            ]);

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $user = User::where('id', $user->id)
            ->eagerLoadBaseRelations($user_id)
            ->first();

            NotifyFollowJob::dispatch([
                'follower' => $request->user(),
                'user' => $user,
            ]);
            return $this->respondWithSuccess('You have successfully followed this user', [
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function unfollowUser(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($id === $request->user()->id) {
                return $this->respondBadRequest("You cannot unfollow yourself");
            }

            $user = User::where('id', $id)->first();
            $user->followers()->detach($request->user()->id);

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $user = User::where('id', $user->id)
            ->eagerLoadBaseRelations()
            ->first();
            return $this->respondWithSuccess('You have successfully followed this user', [
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function showAccount(Request $request)
    {
        try {
            $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $request->user()->id)->first();
            return $this->respondWithSuccess('Account retrieved successfully', [
                'user' => new UserResourceWithSensitive($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();
            $request->user()->delete();
            // TO DO: mark all user's contents as unavailable
            // TO DO: ensure that subscribed users cannot renew any subscriptions if user is deleted
            return $this->respondWithSuccess('Account deleted successfully', [
                'user' => new UserResourceWithSensitive($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $user = $request->user();

            if (Hash::check($request->old, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();
                $user = User::with('roles', 'profile_picture', 'wallet', 'referrer')->withCount('digiversesCreated')->where('id', $user->id)->first();
                return $this->respondWithSuccess('Password changed successfully', ['user' => new UserResourceWithSensitive($user)]);
            } else {
                return $this->respondBadRequest('Password provided is not correct.');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function updateBasicData(Request $request)
    {
        try {
            $validator = Validator::make(array_merge($request->all()), [
                'name' => ['sometimes', 'nullable', 'string' ],
                'username' => ['sometimes', 'nullable', 'regex:/^[A-Za-z0-9_]*$/'],
                'dob' => ['sometimes', 'nullable', 'date'],
                'profile_picture' => ['sometimes', 'nullable', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'bio' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            if (isset($request->username) && ! is_null($request->username)) {
                $test_username = User::where('username', $request->username)->first();
                if (! is_null($test_username) && $test_username->id !== $request->user()->id) {
                    return $this->respondBadRequest('Username is already taken');
                }
            }

            $user = $request->user();

            $user->update($request->only(['name', 'email', 'dob', 'bio']));

            if (! is_null($request->username)) {
                $user->username = $request->username;
                $user->save();
            }

            if (! is_null($request->profile_picture)) {
                $oldPicture = $user->profile_picture()->first();
                if (! is_null($oldPicture)) {
                    $user->profile_picture()->detach($oldPicture->id);
                    $oldPicture->delete();
                }
                $user->profile_picture()->attach($request->profile_picture, [
                    'id' => Str::uuid(),
                    'purpose' => 'profile-picture',
                ]);
            }
            $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $user->id)->first();
            return $this->respondWithSuccess('User updated successfully', [
                'user' => new UserResourceWithSensitive($user),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addItemsToWishList(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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

            $wishlist = Userable::where('user_id', $request->user()->id)->where('status', 'wishlist')->with('userable', 'userable.prices', 'userable.prices.continent', 'userable.prices.country', 'userable.tags', 'userable.cover')->get();

            return $this->respondWithSuccess(
                'Items successfully added to wishlist',
                [
                'wishlist' => $wishlist,
                ]
            );
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function removeItemsFromWishlist(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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
                    continue;
                }
                //ensure the user has not purchased the item or added it already
                $wishlistItem = Userable::where('user_id', $request->user()->id)->where('userable_type', $item['type'])->where('userable_id', $itemModel->id)->first();
                if (! is_null($wishlistItem)) {
                    $wishlistItem->delete();
                }
            }

            $wishlist = Userable::where('user_id', $request->user()->id)->where('status', 'wishlist')->with('userable', 'userable.prices', 'userable.prices.continent', 'userable.prices.country', 'userable.tags', 'userable.cover')->get();

            return $this->respondWithSuccess(
                'Items successfully removed from wishlist',
                [
                'wishlist' => $wishlist,
                ]
            );
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getWishlist(Request $request)
    {
        try {
            $wishlist = Userable::where('user_id', $request->user()->id)->where('status', 'wishlist')->with('userable', 'userable.prices', 'userable.prices.continent', 'userable.prices.country', 'userable.tags', 'userable.cover')->get();

            return $this->respondWithSuccess('Wishlist retrieved successfully', [
                'wishlist' => $wishlist,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listRevenues(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;

            $revenues = $request->user()->revenues()->with('revenueable')->where('revenue_from', 'sale')->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('Revenues retrieved successfully', [
                'revenues' => RevenueResource::collection($revenues),
                'current_page' => (int) $revenues->currentPage(),
                'items_per_page' => (int) $revenues->perPage(),
                'total' => (int) $revenues->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                'page' => ['required', 'integer', 'min:1'],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}"],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $notifications = $request->user()->notifications()->with('notifier', 'notifier.profile_picture', 'notificable')->orderBy('notifications.created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Notifications retrieved successfully', [
                'notifications' => NotificationResource::collection($notifications),
                'current_page' => (int) $notifications->currentPage(),
                'items_per_page' => (int) $notifications->perPage(),
                'total' => (int) $notifications->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        try {
            $notifications = $request->user()->notifications()->where('viewed', 0)->get();
            foreach ($notifications as $notification) {
                $notification->viewed = 1;
                $notification->save();
            }

            return $this->respondWithSuccess('Notifications have been successfully marked as read');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getPurchasedItems(Request $request)
    {
        try {
            $items = Userable::where('user_id', $request->user()->id)->where(function ($query) {
                $query->where('status', 'available')->orWhere('status', 'subscription-ended')->orWhere('status', 'content-deleted');
            })->with('userable', 'userable.cover', 'userable.prices', 'userable.prices.continent', 'userable.prices.country', 'userable.tags')->with('subscription')->get();

            return $this->respondWithSuccess('Items retrieved successfully', [
                'items' => $items,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addItemsToCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
                'items.*.quantity' => ['sometimes', 'required', 'numeric', 'min:1', 'max:1'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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

            $items = $request->user()->carts()->with('cartable', 'cartable.prices', 'cartable.prices.continent', 'cartable.prices.country', 'cartable.tags', 'cartable.cover')->where('checked_out', 0)->get();

            foreach ($items as $key => $item) {
                if (is_null($item->cartable)) {
                    $item->delete();
                    $items->forget($key);
                }
            }
            return $this->respondWithSuccess('Cart items added successfully', [
                'cart' => $items,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getCartItems(Request $request)
    {
        try {
            $items = $request->user()->carts()->with('cartable', 'cartable.cover', 'cartable.prices', 'cartable.prices.continent', 'cartable.prices.country', 'cartable.tags')->where('checked_out', 0)->get();

            return $this->respondWithSuccess('Cart items retrieved successfully', [
                'cart' => $items,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function removeItemsFromCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => ['required'],
                'items.*.id' => ['required', 'string' ],
                'items.*.type' => ['required', 'string', 'in:collection,content'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->items as $item) {
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
                    continue;
                }
                $cartItem = Cart::where('user_id', $request->user()->id)
                ->where('cartable_id', $itemModel->id)
                ->where('cartable_type', $item['type'])->where('checked_out', 0)->first();
                if (! is_null($cartItem)) {
                    $cartItem->delete();
                }
            }

            $items = $request->user()->carts()->with('cartable', 'cartable.cover', 'cartable.prices', 'cartable.prices.continent', 'cartable.prices.country', 'cartable.tags')->where('checked_out', 0)->get();

            return $this->respondWithSuccess('Cart items deleted successfully', [
                'cart' => $items,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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

            return $this->respondWithSuccess('Otp retrieved successfully', [
                'otp' => $otp,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getPayouts(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;

            $payouts = $request->user()->payouts()->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('Payouts retrieved successfully', [
                'payouts' => $payouts,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addPaymentAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => ['required', 'string', 'in:manual,flutterwave'],
                'identifier' => ['required', 'string'],
                'country_code' => ['required', 'string'],
                'currency_code' => ['required', 'string'],
                'bank_code' => ['string', 'required_if:provider,flutterwave'],
                'bank_name' => ['string', 'required_if:provider,flutterwave,manual'],
                'branch_code' => ['string', 'required_if:country_code,GH,UG,TZ'],
                'branch_name' => ['string','required_if:country_code,GH,UG,TZ'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            //make sure a valid country code was supplied
            $countries = new PragmarxCountries;
            $country = $countries->where('cca2', $request->country_code)->first();
            if (is_null($country)) {
                return $this->respondBadRequest('Invalid country code supplied');
            }
            //make sure a valid currency code was supplied
            $validCurrency = false;
            foreach ($countries->where('cca2', $request->country_code)->first()->currencies as $currency) {
                if ($request->currency_code === $currency) {
                    $validCurrency = true;
                    break;
                }
            }
            if (! $validCurrency) {
                return $this->respondBadRequest('Invalid currency code supplied');
            }

            if ($request->provider === 'flutterwave' && in_array($request->country_code, ['GH', 'UG', 'TZ'])) {
                $validator = Validator::make($request->all(), [
                    'branch_code' => ['string', 'required'],
                    'branch_name' => ['string', 'required'],
                ]);

                if ($validator->fails()) {
                    return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
                }
            }

            $accountData = [
                'identifier' => $request->identifier,
                'provider' => $request->provider,
                'country_code' => $request->country_code,
                'currency_code' => $request->currency_code,
            ];

            if (! is_null($request->bank_name)) {
                $accountData['bank_name'] = $request->bank_name;
            }

            if (! is_null($request->bank_code)) {
                $accountData['bank_code'] = $request->bank_code;
            }

            if (! is_null($request->branch_code)) {
                $accountData['branch_code'] = $request->branch_code;
            }

            if (! is_null($request->branch_name)) {
                $accountData['branch_name'] = $request->branch_name;
            }

            $paymentAccount = $request->user()->paymentAccounts()->create($accountData);

            return $this->respondWithSuccess('Payoutment account created successfully', [
                'payment_account' => $paymentAccount,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addStripePaymentAccount(Request $request)
    {
        try {
            $user = User::where('id', $request->query('id'))->first();
            $country = strtoupper($request->query('country', 'US'));
            if (is_null($user)) {
                return $this->respondBadRequest('User does not exist');
            }

            $allowed_country_codes = ['us', 'gb', 'ca', 'au', 'fr', 'in'];
            if (! in_array(strtolower($country), $allowed_country_codes)) {
                return $this->respondBadRequest('Country is not supported for Stripe Payouts. Permitted countries are United States (US), United Kingdom (GB), Canada (CA), Australia (AU), India (IN), and France (FR)');
            }

            $countries = new PragmarxCountries;
            $currency = '';
            if ($countries->where('cca2', $country)->count() === 0) {
                return $this->respondBadRequest('Invalid country code supplied');
            }
            foreach ($countries->where('cca2', $country)->first()->currencies as $countryCurrency) {
                $currency = $countryCurrency;
                break;
            }

            //first check if user already has stripe setup
            $stripeAccount = $user->paymentAccounts()->where('provider', 'stripe')->first();
            Stripe::setApiKey(config('payment.providers.stripe.secret_key'));
            if (is_null($stripeAccount)) {
                $account = StripeAccount::create([
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
                    $stripe = new StripeClient(config('payment.providers.stripe.secret_key'));
                    $stripe->accounts->delete($stripeAccount->identifier, []);
                    //create a new one
                    $account = StripeAccount::create([
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

            $account_links = StripeAccountLink::create([
                'account' => $stripeAccount->identifier,
                'refresh_url' => config('flok.backend_url') . 'api/v1/payments/stripe/connect?id=' . $user->id . '&country=' . $country,
                'return_url' => config('flok.frontend_url'),
                'type' => 'account_onboarding',
            ]);

            return redirect()->to($account_links->url);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }


    public function getPaymentAccount(Request $request)
    {
        try {
            return $this->respondWithSuccess('Payoutment accounts retrieved successfully', [
                'payment_accounts' => $request->user()->paymentAccounts()->get(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function removePaymentAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'accounts' => ['required'],
                'accounts.*' => ['required', 'string', 'exists:payment_accounts,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            foreach ($request->accounts as $account_id) {
                $accountModel = PaymentAccount::where('id', $account_id)->where('user_id', $request->user()->id)->first();
                $accountModel->delete();
            }

            return $this->respondWithSuccess('Payoutment accounts deleted successfully', [
                'payment_accounts' => $request->user()->paymentAccounts()->get(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function tipUser(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'amount_in_flk' => ['required', 'numeric', 'min:1', 'max:1000000'],
                'id' => ['required', 'string', 'exists:users,id'],
                'originating_content_id' => ['sometimes', 'nullable', 'string', 'exists:contents,id'],
                'originating_client_source' => ['sometimes', 'nullable', 'string', 'in:web,ios,android'],
                'originating_currency' => ['sometimes', 'nullable', 'string'],
                'tip_frequency' => ['sometimes', 'nullable', 'string', 'in:one-off,daily,weekly,monthly'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($id === $request->user()->id) {
                return $this->respondBadRequest("You cannot tip yourself");
            }

            if ((float) $request->user()->wallet->balance < (float) $request->amount_in_flk) {
                return $this->respondBadRequest("You do not have enough Flok cowries to send {$request->amount_in_flk} FLK");
            }
            DB::beginTransaction();
            $userToTip = User::where('id', $id)->first();
            $amount_in_dollars = bcdiv($request->amount_in_flk, 100, 6);
            $newWalletBalance = bcsub($request->user()->wallet->balance, $request->amount_in_flk, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $request->user()->wallet->id,
                'amount' => $request->amount_in_flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'deduct',
                'details' => "You gifted @{$userToTip->username} {$request->amount_in_flk} Flok Cowries",
            ]);
            $transaction->payments()->create([
                'payer_id' => $request->user()->id,
                'payee_id' => $userToTip->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => 0,
                'provider' => 'wallet',
                'provider_id' => $transaction->id,
            ]);
            $request->user()->wallet->balance = $newWalletBalance;
            $request->user()->wallet->save();

            $platform_charge = Constants::TIPPING_CHARGE;
            if ($userToTip->user_charge_type === 'non-profit') {
                $platform_charge = Constants::TIPPING_CHARGE;
            }
            $platform_share = bcmul($amount_in_dollars, $platform_charge, 6);
            $creator_share = bcmul($amount_in_dollars, 1 - $platform_charge, 6);

            $originating_currency = '';
            $originating_content_id = '';
            $originating_client_source = '';
            $revenue = $userToTip->revenues()->create([
                'revenueable_type' => 'user',
                'revenueable_id' => $userToTip->id,
                'amount' => $amount_in_dollars,
                'payment_processor_fee' => 0,
                'platform_share' => $platform_share,
                'benefactor_share' => $creator_share,
                'referral_bonus' => 0,
                'revenue_from' => 'tip',
                'added_to_payout' => 1,
            ]);

            if ( ! is_null($request->originating_currency)) {
                $originating_currency = $request->originating_currency;
                $revenue->originating_currency = $originating_currency;
            }

            if ( ! is_null($request->originating_content_id)) {
                $originating_content_id = $request->originating_content_id;
                $revenue->originating_content_id = $originating_content_id;
            }

            if ( ! is_null($request->originating_client_source)) {
                $originating_client_source = $request->originating_client_source;
                $revenue->originating_client_source = $originating_client_source;
            }

            $revenue->save();

            if (! is_null($revenue->originating_content_id)) {
                $content_tip_count = $userToTip->revenues()->where('originating_content_id', $revenue->originating_content_id)->where('revenue_from', 'tip')->count();
                $websocket_client = new \WebSocket\Client(config('services.websocket.url'));
                $websocket_client->text(json_encode([
                    'event' => 'app-update-number-of-tips-for-content',
                    'source_type' => 'app',
                    'content_id' => $revenue->originating_content_id,
                    'tips_count' => $content_tip_count
                ]));
                $websocket_client->close();
            }

            $creator_share_in_flk = $creator_share * 100;
            $newWalletBalance = bcadd($userToTip->wallet->balance, $creator_share_in_flk, 2);
            $transaction = WalletTransaction::create([
                'wallet_id' => $userToTip->wallet->id,
                'amount' => $creator_share_in_flk,
                'balance' => $newWalletBalance,
                'transaction_type' => 'fund',
                'details' => "@{$request->user()->username} gifted you {$creator_share_in_flk} Flok Cowries",
            ]);
            $userToTip->wallet->balance = $newWalletBalance;
            $userToTip->wallet->save();
            DB::commit();

            $userTip = UserTip::where('tipper_user_id', $request->user()->id)->where('tippee_user_id', $userToTip->id)->where('is_active', 1)->first();            
            if(! is_null($request->tip_frequency) && $request->tip_frequency != 'one-off' && is_null($userTip))
            {
                $userTip = UserTip::create([
                    'tipper_user_id' => $request->user()->id,
                    'tipper_email' => $request->user()->email,
                    'tippee_user_id' => $userToTip->id,
                    'amount_in_flk' => $request->amount_in_flk,
                    'tip_frequency' => $request->tip_frequency,
                    'originating_currency' => $originating_currency,
                    'originating_client_source' => $originating_client_source,
                    'originating_content_id' => $originating_content_id,
                    'last_tip' => now(),
                    'provider' => 'wallet',
                ]);
            }
            NotifyTippingJob::dispatch([
                'tipper' => $request->user(),
                'tippee' => $userToTip,
                'amount_in_flk' => $creator_share_in_flk,
                'wallet_transaction' => $transaction,
                'tipper_email' => '',
            ]);

            return $this->respondWithSuccess('User has been tipped successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function showDashboardDetails(Request $request)
    {
        try {
            $subcribers_graph_start_date = $request->query('subscribers_graph_start_date', now()->startOfMonth());
            $subcribers_graph_end_date = $request->query('subscribers_graph_end_date', now()->endOfMonth());

            $validator = Validator::make([
                'subscribers_graph_start_date' => $subcribers_graph_start_date,
                'subscribers_graph_end_date' => $subcribers_graph_end_date,
            ], [
                'subscribers_graph_start_date' => ['required', 'date'],
                'subscribers_graph_end_date' => ['required', 'date', 'after_or_equal:subscribers_graph_start_date'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            // get all subscribers
            $digiverses = $request->user()->digiversesCreated()->withCount('subscriptions')->get();
            $total_subscribers_count = 0;
            foreach ($digiverses as $digiverse) {
                $total_subscribers_count = $total_subscribers_count + $digiverse->subscriptions_count;
            }

            // month subscribers
            $digiverses = $request->user()->digiversesCreated()->withCount([
                'subscriptions' => function ($query) {
                    $query->whereDate('created_at', '>=', now()->startOfMonth());
                },
            ])->get();
            $month_subscribers_count = 0;
            foreach ($digiverses as $digiverse) {
                $month_subscribers_count = $month_subscribers_count + $digiverse->subscriptions_count;
            }

            // subscription graph
            $digiverse_ids = $request->user()->digiversesCreated()->pluck('id');
            $subscriptions = Subscription::select(DB::raw('count(id) as subscribers_count, date(created_at) as created_date'))
                                            ->whereDate('created_at', '>=', $subcribers_graph_start_date)
                                            ->whereDate('created_at', '<=', $subcribers_graph_end_date)
                                            ->where('subscriptionable_type', 'collection')
                                            ->whereIn('subscriptionable_id', $digiverse_ids)
                                            ->groupBy('created_date')
                                            ->get()->toArray();

            $subscription_graph = null;
            foreach ($subscriptions as $instance) {
                $subscription_graph[$instance['created_date']] = $instance['subscribers_count'];
            }

            return $this->respondWithSuccess('Dashboard details retrieved successfully', [
                'total_tips' => $request->user()->revenues()->where('revenue_from', 'tip')->sum('benefactor_share'),
                'month_tips' => $request->user()->revenues()->where('revenue_from', 'tip')->whereDate('created_at', '>=', now()->startOfMonth())->sum('benefactor_share'),
                'total_sales' => $request->user()->revenues()->where('revenue_from', 'sale')->sum('benefactor_share'),
                'month_sales' => $request->user()->revenues()->where('revenue_from', 'sale')->whereDate('created_at', '>=', now()->startOfMonth())->sum('benefactor_share'),
                'total_subscribers' => $total_subscribers_count,
                'month_subscribers' => $month_subscribers_count,
                'subscription_graph' => $subscription_graph,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function anonymousUserTip(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'id' => ['required', 'string', 'exists:users,id'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'provider' => ['required', 'string', 'in:flutterwave,stripe'],
                'provider_response' => ['required'],
                'provider_response.transaction_id' => ['required_if:provider,flutterwave'],
                'provider_response.id' => ['required_if:provider,stripe', 'string'],
                'amount_in_cents' => ['required_if:provider,stripe', 'integer'],
                'expected_flk_amount' => ['required', 'integer', 'min:1'],
                'originating_content_id' => ['sometimes', 'nullable', 'string', 'exists:contents,id'],
                'originating_client_source' => ['sometimes', 'nullable', 'string', 'in:web,ios,android'],
                'originating_currency' => ['sometimes', 'nullable', 'string'],
                'tip_frequency' => ['sometimes', 'nullable', 'string', 'in:one-off,daily,weekly,monthly'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            Log::info("Anonymous tipping began");
            $payment_verified = false;
            $amount_in_dollars = 0;
            $fee = 0;
            $expected_flk_based_on_amount = 0;
            $provider_id = '';
            $card_token = '';
            $customer_id = '';
            $naira_to_dollar = Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();

            switch ($request->provider) {
                case 'flutterwave':
                    $flutterwave = new Flutterwave;
                    $req = $flutterwave->verifyTransaction($request->provider_response['transaction_id']);
                    if (($req->status === 'success' && $req->data->status === 'successful')) {
                        $amount_in_dollars = bcdiv($req->data->amount, $naira_to_dollar->value, 2);
                        $actual_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                        $fee = bcdiv($req->data->app_fee, $naira_to_dollar->value, 2);
                        $provider_id = $req->data->id;
                        $card_token = $req->data->card->token;
                        $payment_verified = true;
                    }
                    break;
                case 'stripe':
                    $stripe = new StripePayment;
                    $amount_in_dollars = bcdiv($request->amount_in_cents, 100, 2);
                    $actual_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;

                    //create a customer
                    $customer = $stripe->createCustomer($request->provider_response['id'], $request->email );
                    // Charge the Customer instead of the card:
                    $charge = $stripe->createCharge($request->amount_in_cents, 'usd', $customer->id);
                    if ($charge->status === 'succeeded')
                    {
                        $customer_id = $customer->id;
                        $fee = bcdiv(bcadd(bcmul(0.029, $charge->amount, 2), 30, 2), 100, 2); //2.9% + 30c convert to dollar
                        $amount_in_dollars = bcdiv($charge->amount, 100, 2);
                        $actual_flk_based_on_amount = bcdiv($amount_in_dollars, 1.03, 2) * 100;
                        $provider_id = $charge->id;
                        $payment_verified = true;
                    }                 
                    break;
                default:
                    return $this->respondBadRequest('Invalid provider specified');
            }

            if (!$payment_verified) {
                return $this->respondBadRequest('Payment provider did not verify payment');
            }

            AnonymousUserTipJob::dispatch([
                'email' => $request->email,
                'card_token' => $card_token,
                'customer_id' => $customer_id,
                'tippee_id' => $request->id,
                'provider' => $request->provider,
                'provider_id' => $provider_id,
                'amount' => $amount_in_dollars,
                'flk' => $actual_flk_based_on_amount,
                'fee' => $fee,
                'originating_currency' => $request->originating_currency,
                'originating_content_id' => $request->originating_content_id,
                'originating_client_source' => $request->originating_client_source,
                'tip_frequency' => $request->tip_frequency,
                'last_tip' => '',
            ]);            
            Log::info("Anonymous tipping was successful");
            return $this->respondWithSuccess('User has been tipped successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
    
    public function joinExternalCommunity(Request $request, $id) 
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:users,id'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'name' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $externalCommunity = ExternalCommunity::where('user_id', $request->id)->where('email', $request->email)->first();
            if (is_null($externalCommunity))
            {
                ExternalCommunity::create([
                    'user_id' => $request->id,
                    'email' => $request->email,
                    'name' => $request->name,
                ]);
            }
            return $this->respondWithSuccess('Community joined successfully');           

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        } 
    }
    
    public function leaveExternalCommunity(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:users,id'],
                'email' => ['required', 'string', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            //make sure email matches
            $externalCommunity = ExternalCommunity::where('user_id', $request->id)->where('email', $request->email)->first();
            if (is_null($externalCommunity))
            {
                return $this->respondBadRequest('You cannot leave this community because your email does not match');
            }
            $externalCommunity->delete();
            return $this->respondWithSuccess('Community left successfully');           

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
    
    public function importExternalCommunity(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => ['required', 'max:102400', 'mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt'], // 100MB,
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            Excel::import(new ExternalCommunitiesImport, $request->file);

            return $this->respondWithSuccess('Community imported successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
    
    public function exportExternalCommunity(Request $request)
    {
        try {
            ExportExternalCommunityJob::dispatch([
                'user' => $request->user(),
            ]);
            return $this->respondWithSuccess('Community retrieved successfully');

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function cancelRecurrentTipping(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:users,id'],
                'email' => ['sometimes', 'string', 'email', 'max:255', 'exists:user_tips,tipper_email'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $userTip = '';
            if ($request->user() == null && $request->email == null) {
                return $this->respondBadRequest('Invalid or missing email field');
            }
    
            if ($request->user() != null) {
                $user_id = $request->user()->id;
                $userTip = UserTip::where('tipper_user_id', $user_id)->where('tippee_user_id', $request->id)->where('is_active', 1)->first();
            }
            if ($request->email != null) {
                $email = $request->email;
                $userTip = UserTip::where('tipper_email', $email)->where('tippee_user_id', $request->id)->where('is_active', 1)->first();
            }
            
            if (is_null($userTip))
            {
                return $this->respondBadRequest('You cannot cancel this tip because you did not create it');
            }

            $userTip->is_active = 0;
            $userTip->save();

            return $this->respondWithSuccess('recurrent tipping cancelled successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
