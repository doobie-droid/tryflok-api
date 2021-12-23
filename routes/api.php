<?php

use App\Constants\Constants;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TagResource;
use App\Models\Category;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1', 'namespace' => 'V1'], function () {
    Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
        Route::post('register', 'AuthController@register');
        Route::post('login', 'AuthController@login');
        Route::post('social-sign-in', 'AuthController@socialMediaSignIn');
        Route::post('otp-login', 'AuthController@loginViaOtp');
        Route::patch('email', 'AuthController@verifyEmail');
        Route::patch('password/forgot', 'AuthController@forgotPassword');
        Route::patch('password/reset', 'AuthController@resetPassword');
        Route::get('unauthenticated', function () {
            return response()->json([
                'status' => false,
                'status_code' => 401,
                'message' => 'Invalid token provided',
            ], 401);
        })->name('unauthenticated');
    });

    Route::get('languages', function () {
        return response()->json([
            'message' => 'Languages retrieved successfully',
            'status' => true,
            'status_code' => 200,
            'data' => [
                'languages' => Cache::rememberForever('request:languages', function () {
                    return Language::orderBy('name')->get();
                }),
            ],
        ]);
    });

    Route::get('countries', function () {
        return response()->json([
            'message' => 'Countries retrieved successfully',
            'status' => true,
            'status_code' => 200,
            'data' => [
                'countries' => Cache::rememberForever('request:countries', function () {
                    return Country::orderBy('name')->get();
                }),
            ],
        ]);
    });

    Route::get('continents', function () {
        return response()->json([
            'message' => 'Continents retrieved successfully',
            'status' => true,
            'status_code' => 200,
            'data' => [
                'continents' => Cache::rememberForever('request:continents', function () {
                    return Continent::orderBy('name')->get();
                }),
            ],
        ]);
    });

    Route::get('categories', function (Request $request) {
        $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
        $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
        if ($limit > Constants::MAX_ITEMS_LIMIT) {
            $limit = Constants::MAX_ITEMS_LIMIT;
        }
        $search = urldecode($request->query('search', ''));

        $categories = Category::where('name', 'LIKE', "%{$search}%")->orderBy('name')->paginate($limit, ['*'], 'page', $page);
        return response()->json([
            'message' => 'Categories retrieved successfully',
            'status' => true,
            'status_code' => 200,
            'data' => [
                'categories' => CategoryResource::collection($categories),
                'current_page' => (int) $categories->currentPage(),
                'items_per_page' => (int) $categories->perPage(),
                'total' => (int) $categories->total(),
            ],
        ]);
    });

    Route::get('tags', function (Request $request) {
        $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
        $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
        if ($limit > Constants::MAX_ITEMS_LIMIT) {
            $limit = Constants::MAX_ITEMS_LIMIT;
        }
        $search = urldecode($request->query('search', ''));

        $tags = Tag::where('name', 'LIKE', "%{$search}%")->orderBy('name')->paginate($limit, ['*'], 'page', $page);
        return response()->json([
            'message' => 'Tags retrieved successfully',
            'status' => true,
            'status_code' => 200,
            'data' => [
                'categories' => TagResource::collection($tags),
                'current_page' => (int) $tags->currentPage(),
                'items_per_page' => (int) $tags->perPage(),
                'total' => (int) $tags->total(),
            ],
        ]);
    });

    Route::group(['prefix' => 'contents'], function () {
        Route::get('trending', 'ContentController@getTrending');

        Route::post('{id}/views', 'ContentController@addViews');
        Route::get('{id}', 'ContentController@getSingle');
        Route::get('{id}/reviews', 'ContentController@getReviews');
        Route::get('{id}/assets', 'ContentController@getAssets');
    });

    Route::group(['prefix' => 'digiverses'], function () {
        Route::get('/', 'CollectionController@getAll');
        Route::get('{id}', 'CollectionController@getDigiverse');
        Route::get('{digiverse_id}/contents', 'ContentController@getDigiverseContents');
        Route::get('{id}/reviews', 'CollectionController@getReviews');
    });

    Route::group(['prefix' => 'reviews'], function () {
        Route::get('{id}/reviews', 'ReviewController@getReviews');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'UserController@getAll');
        Route::get('{id}', 'UserController@getSingle');
    });

    Route::group(['prefix' => 'payments'], function () {
        Route::get('flutterwave/banks', 'PaymentController@getFlutterwaveBanks');
        Route::get('flutterwave/banks/branches', 'PaymentController@getFlutterwaveBankBranches');
        Route::get('stripe/connect', 'UserController@addStripePaymentAccount');
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'v1', 'namespace' => 'V1'], function () {
        Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => 'authorize_admin'], function () {
            Route::get('dashboard', 'AdministratorController@dashboard');

            Route::group(['prefix' => 'categories'], function () {
                Route::post('/', 'CategoryController@create');
                Route::patch('{public_id}', 'CategoryController@update');
            });
        });

        Route::group(['prefix' => 'assets'], function () {
            Route::post('/', 'AssetController@uploadFile');
        });

        Route::group(['prefix' => 'approvals'], function () {
            Route::get('/', 'ApprovalController@getAll');
            Route::post('/', 'ApprovalController@create');
            Route::patch('{public_id}', 'ApprovalController@update');
            Route::post('{public_id}/messages', 'ApprovalController@sendApprovalMessage');
            Route::get('{public_id}/messages', 'ApprovalController@getApprovalMessages');
        });

        Route::group(['prefix' => 'users',], function () {
            Route::patch('{id}/follow', 'UserController@followUser');
            Route::patch('{id}/unfollow', 'UserController@unfollowUser');
            Route::get('{id}/revenues', 'UserController@getRevenues');
            Route::post('{id}/tip', 'UserController@tipUser');
        });

        Route::group(['prefix' => 'account'], function () {
            Route::get('/', 'UserController@getAccount');
            Route::delete('/', 'UserController@deleteAccount');
            Route::get('dashboard', 'UserController@getDashboardDetails');
            Route::get('digiverses', 'CollectionController@getUserCreatedDigiverses');
            Route::get('notifications', 'UserController@getNotifications');
            Route::patch('notifications', 'UserController@markAllNotificationsAsRead');
            Route::get('signed-cookie', 'UserController@getSignedCookies');
            Route::get('approval-requests', 'ApprovalController@getUserRequests');
            Route::get('subscriptions', 'SubscriptionController@getUserSubscriptions');
            Route::patch('fund-wallet', 'WalletController@fundWallet');
            Route::post('wallet-pay', 'WalletController@payViaWallet');
            Route::get('wallet-transactions', 'WalletController@getTransactions');
            Route::post('profile', 'UserController@updateBasicData');
            Route::put('password', 'UserController@updatePassword');
            Route::patch('token', 'UserController@refreshToken');
            Route::post('wishlist', 'UserController@addItemsToWishList');
            Route::delete('wishlist', 'UserController@removeItemsFromWishlist');
            Route::get('wishlist', 'UserController@getWishlist');
            Route::get('purchased-items', 'UserController@getPurchasedItems');
            Route::post('cart', 'UserController@addItemsToCart');
            Route::delete('cart', 'UserController@removeItemsFromCart');
            Route::get('cart', 'UserController@getCartItems');
            Route::get('auth-otp', 'UserController@generateAuthOtp');
            Route::get('payout', 'UserController@getPayouts');
            Route::patch('payout', 'UserController@cashoutPayout');
            Route::post('payment-account', 'UserController@addPaymentAccount');
            Route::get('payment-account', 'UserController@getPaymentAccount');
            Route::delete('payment-account', 'UserController@removePaymentAccount');
        });

        Route::group(['prefix' => 'subscriptions'], function () {
            Route::patch('{id}', 'SubscriptionController@toggleAutorenew');
        });

        Route::group(['prefix' => 'contents'], function () {
            Route::post('/', 'ContentController@create');
            Route::patch('{id}', 'ContentController@update');

            Route::get('{id}/insights', 'ContentController@getContentInsights');

            Route::post('{id}/issues', 'ContentController@createIssue');
            Route::put('{id}/issues', 'ContentController@updateIssue');
            Route::patch('{id}/issues', 'ContentController@publishIssue');
            Route::get('{id}/issues', 'ContentController@getIssues');

            Route::post('{id}/subscription', 'ContentController@subscribeToContent');
            Route::delete('{id}/subscription', 'ContentController@unsubscribeFromContent');

            Route::post('{id}/live', 'ContentController@startLive');
            Route::patch('{id}/live', 'ContentController@joinLive');
            Route::patch('{id}/leave-live', 'ContentController@leaveLive');
            Route::delete('{id}/live', 'ContentController@endLive');

            Route::post('{id}/attach-media', 'ContentController@attachMediaToContent');
        });

        Route::group(['prefix' => 'issues'], function () {
            Route::get('{id}', 'ContentController@getSingleIssue');
        });

        Route::group(['prefix' => 'digiverses'], function () {
            Route::post('/', 'CollectionController@createDigiverse');
            Route::patch('{id}', 'CollectionController@updateDigiverse');
        });

        Route::group(['prefix' => 'reviews'], function () {
            Route::post('/', 'ReviewController@create');
        });

        Route::group(['prefix' => 'payments'], function () {
            Route::post('free', 'PaymentController@freeItems');
        });
    });
});
