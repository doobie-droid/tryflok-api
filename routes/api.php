<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Constants\Constants;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TagResource;

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
        Route::get('/unauthenticated', function () {
            return response()->json([
                'status' => false,
                'status_code' => 401,
                'message' => 'Invalid token provided',
            ], 401);
        })->name('unauthenticated');
    });

    Route::get('/languages', function () {
        return response()->json([
            "message" => "Languages retrieved successfully",
            "data" => [
                "languages" => Cache::rememberForever('request:languages', function()
                {
                    return \App\Models\Language::orderBy('name')->get();
                }),
            ]
        ]);
    });

    Route::get('/countries', function () {
        return response()->json([
            "message" => "Countries retrieved successfully",
            "data" => [
                "countries" => Cache::rememberForever('request:countries', function()
                {
                    return \App\Models\Country::orderBy('name')->get();
                }),
            ]
        ]);
    });

    Route::get('/continents', function () {
        return response()->json([
            "message" => "Continents retrieved successfully",
            "data" => [
                "continents" => Cache::rememberForever('request:continents', function()
                {
                    return \App\Models\Continent::orderBy('name')->get();
                }),
            ]
        ]);
    });

    Route::get('/categories', function (Request $request) {
        $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
        $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
        if ($limit > Constants::MAX_ITEMS_LIMIT) {
            $limit = Constants::MAX_ITEMS_LIMIT;
        }
        $search = urldecode($request->query('search', ''));

        $categories = \App\Models\Category::where('name', 'LIKE', '%' . $search . '%')->orderBy('name')->paginate($limit, array('*'), 'page', $page);
        return response()->json([
            "message" => "Categories retrieved successfully",
            "data" => [
                "categories" => CategoryResource::collection($categories),
                'current_page' => $categories->currentPage(),
                'items_per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ]
        ]);
    });

    Route::get('/tags', function (Request $request) {
        $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
        $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
        if ($limit > Constants::MAX_ITEMS_LIMIT) {
            $limit = Constants::MAX_ITEMS_LIMIT;
        }
        $search = urldecode($request->query('search', ''));

        $tags = \App\Models\Tag::where('name', 'LIKE', '%' . $search . '%')->orderBy('name')->paginate($limit, array('*'), 'page', $page);
        return response()->json([
            "message" => "Tags retrieved successfully",
            "data" => [
                "categories" => TagResource::collection($tags),
                'current_page' => $tags->currentPage(),
                'items_per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ]
        ]);
    });

    Route::group(['prefix' => 'contents'], function () {
        Route::get('/{id}', 'ContentController@getSingle');
        Route::get('/{public_id}/reviews', 'ContentController@getReviews');
        Route::get('/{public_id}/free-assets', 'ContentController@getFreeAssets');
    });

    Route::group(['prefix' => 'digiverses'], function () {
        Route::get('/', 'CollectionController@getAll');
        Route::get('/{id}', 'CollectionController@getDigiverse');
        Route::get('/{digiverse_id}/contents', 'ContentController@getDigiverseContents');
        Route::get('/{id}/reviews', 'CollectionController@getReviews');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('{public_id}', 'UserController@getSingle');
        Route::get('{user_public_id}/contents', 'ContentController@getUserCreatedContents');
        Route::get('{user_public_id}/collections', 'CollectionController@getUserCreatedCollections');
        Route::get('{user_public_id}/wishlist', 'UserController@getWishlist');
    });

    Route::group(['prefix' => 'analytics'], function () {
        Route::get('trending', 'AnalyticsController@trending');
    });

    Route::group(['prefix' => 'payments'], function () {
        //Route::post('/paystack', 'PaymentController@paystackWebhook');
        //Route::post('/test', 'PaymentController@testPaymentWebhook');
        Route::get('/flutterwave/banks', 'PaymentController@getFlutterwaveBanks');
        Route::get('/flutterwave/banks/branches', 'PaymentController@getFlutterwaveBankBranches');
        //Route::post('/providers', 'PaymentController@processPaymentForProviders');
        Route::get('/stripe/connect', 'UserController@addStripePaymentAccount');
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'v1', 'namespace' => 'V1'], function () {

        Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => 'authorize_admin'], function () {
            Route::get('/dashboard', 'AdministratorController@dashboard');

            Route::group(['prefix' => 'categories'], function () {
                Route::post('/', 'CategoryController@create');
                Route::patch('/{public_id}', 'CategoryController@update');
            });
        });

        Route::group(['prefix' => 'assets'], function () {
            Route::post('/', 'AssetController@uploadFile');
        });

        Route::group(['prefix' => 'approvals'], function () {
            Route::get('/', 'ApprovalController@getAll');
            Route::post('/', 'ApprovalController@create');
            Route::patch('/{public_id}', 'ApprovalController@update');
            Route::post('/{public_id}/messages', 'ApprovalController@sendApprovalMessage');
            Route::get('/{public_id}/messages', 'ApprovalController@getApprovalMessages');
        });

        Route::group(['prefix' => 'users',], function () {
            Route::get('/', 'UserController@getAll');
            Route::get('{user_public_id}/sales', 'UserController@getSales');
        });

        Route::group(['prefix' => 'account'], function () {
            Route::get('/', 'UserController@getAccount');
            Route::get('/digiverses', 'CollectionController@getUserCreatedDigiverses');
            Route::get('/signed-cookie', 'UserController@getSignedCookies');
            Route::get('/approval-requests', 'ApprovalController@getUserRequests');
            Route::get('/subscriptions', 'SubscriptionController@getUserSubscriptions');
            Route::patch('/fund-wallet', 'WalletController@fundWallet');
            Route::post('/wallet-pay', 'WalletController@payViaWallet');
            Route::get('/wallet-transactions', 'WalletController@getTransactions');
            Route::post('/profile', 'UserController@updateBasicData');//ideally, this should be PUT but php has an issue with collecting files from PUT
            Route::put('/password', 'UserController@updatePassword');
            Route::patch('/token', 'UserController@refreshToken');
            Route::post('/wishlist', 'UserController@addItemsToWishList');
            Route::delete('/wishlist', 'UserController@removeItemsFromWishlist');
            Route::get('/purchased-items', 'UserController@getPurchasedItems');
            Route::post('/cart', 'UserController@addItemsToCart');
            Route::delete('/cart', 'UserController@removeItemsFromCart');
            Route::get('/cart', 'UserController@getCartItems');
            Route::get('/auth-otp', 'UserController@generateAuthOtp');
            Route::post('/payout', 'UserController@requestPayout');
            Route::get('/payout', 'UserController@getPayouts');
            Route::patch('/payout', 'UserController@cashoutPayout');
            Route::post('/payment-account', 'UserController@addPaymentAccount');
            Route::get('/payment-account', 'UserController@getPaymentAccount');
            Route::delete('/payment-account', 'UserController@removePaymentAccount');
        });

        Route::group(['prefix' => 'subscriptions'], function () {
            Route::patch('/{public_id}', 'SubscriptionController@update');
        });

        Route::group(['prefix' => 'contents'], function () {
            Route::post('/', 'ContentController@create');
            Route::patch('/{id}', 'ContentController@update');
            Route::get('/{public_id}/assets', 'ContentController@getAssets');

            Route::post('/{id}/issues', 'ContentController@createIssue');
            Route::patch('/{id}/issues', 'ContentController@updateIssue');
            Route::get('/{id}/issues', 'ContentController@getIssues');

            Route::post('/{id}/subscription', 'ContentController@subscribeToContent');
            Route::delete('/{id}/subscription', 'ContentController@unsubscribeFromContent');
        });

        Route::group(['prefix' => 'issues'], function () {
            Route::get('/{id}', 'ContentController@getSingleIssue');
        });

        Route::group(['prefix' => 'digiverses'], function () {
            Route::post('/', 'CollectionController@createDigiverse');
            Route::patch('/{id}', 'CollectionController@updateDigiverse');
        });

        Route::group(['prefix' => 'reviews'], function () {
            Route::post('/', 'ReviewController@create');
        });

        Route::group(['prefix' => 'views'], function () {
            Route::post('/', 'ReviewController@addViews');
        });

        Route::group(['prefix' => 'payments'], function () {
            Route::post('/free', 'PaymentController@freeItems');
        });
    });
});
