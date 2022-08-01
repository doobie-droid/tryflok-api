<?php

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
        Route::post('register', 'AuthController@register')->name('register');
        Route::post('login', 'AuthController@login')->name('login');
        Route::post('social-sign-in', 'AuthController@socialMediaSignIn');
        Route::post('otp-login', 'AuthController@loginViaOtp')->name('otp-login');
        Route::patch('email', 'AuthController@verifyEmail');
        Route::patch('password/forgot', 'AuthController@forgotPassword')->name('forgot-password');
        Route::patch('password/reset', 'AuthController@resetPassword')->name('reset-password');
        Route::get('unauthenticated', 'AuthController@respondUnauthenticated')->name('unauthenticated');
    });

    Route::get('languages', 'LanguageController@list')->name('list-languages');
    Route::get('countries', 'CountryController@list')->name('list-countries');
    Route::get('continents', 'ContinentController@list')->name('list-continents');
    Route::get('categories', 'CategoryController@list')->name('list-categories');
    Route::get('tags', 'TagController@list')->name('list-tags');

    Route::group(['prefix' => 'contents'], function () {
        Route::get('trending', 'ContentController@listTrending')->name('list-trending-contents');

        Route::patch('{id}/live', 'ContentController@joinLive');
        
        Route::post('{id}/views', 'ContentController@addViews');
        Route::get('{id}', 'ContentController@show')->name('show-content');
        Route::get('{id}/reviews', 'ContentController@listReviews');
        Route::get('{id}/assets', 'ContentController@listAssets')->name('list-content-assets');

        Route::get('proxy-asset/{path}', 'ContentController@proxyAsset')->where('path', '.*');
    });

    Route::group(['prefix' => 'digiverses'], function () {
        Route::get('/', 'CollectionController@listDigiverses')->name('list-digiverses');
        Route::get('{id}', 'CollectionController@showDigiverse')->name('show-digiverse');
        Route::get('{collection_id}/contents', 'ContentController@listContents')->name('list-digiverse-contents');
        Route::get('{collection_id}/collections', 'CollectionController@listDigiverseCollections');
        Route::get('{id}/reviews', 'CollectionController@listReviews');
    });

    Route::group(['prefix' => 'collections'], function () {
        Route::get('{id}', 'CollectionController@getCollection');
        Route::get('{collection_id}/contents', 'ContentController@getCollectionContents');
        Route::get('{id}/reviews', 'CollectionController@getReviews');
    });

    Route::group(['prefix' => 'reviews'], function () {
        Route::post('/', 'ReviewController@create')->name('create-review');    
        Route::get('{id}/reviews', 'ReviewController@listReviews');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'UserController@listUsers')->name('list-users');
        Route::get('{id}', 'UserController@showUser')->name('show-user');
    });

    Route::group(['prefix' => 'payments'], function () {
        Route::patch('easy-fund-wallet', 'WalletController@fundWallet')->name('easy-fund-wallet');

        Route::group(['prefix' => 'flutterwave'], function () {
            Route::post('validate-bank-details', 'PaymentController@validateBankDetailsViaFlutterwave');
            Route::get('banks', 'PaymentController@getFlutterwaveBanks');
            Route::get('banks/branches', 'PaymentController@getFlutterwaveBankBranches');
            Route::post('webhook', 'PaymentController@flutterwaveWebhook')->name('flutterwave-webhook');
        });

        Route::group(['prefix' => 'stripe'], function () {
            Route::get('connect', 'UserController@addStripePaymentAccount');
            Route::post('webhook', 'PaymentController@stripeWebhook');
        });

        Route::group(['prefix' => 'apple-pay'], function () {
            Route::post('webhook', 'PaymentController@applePayWebhook');
        });
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

        Route::group(['prefix' => 'users'], function () {
            Route::patch('{id}/follow', 'UserController@followUser')->name('follow-user');
            Route::patch('{id}/unfollow', 'UserController@unfollowUser')->name('unfollow-user');
            Route::post('{id}/tip', 'UserController@tipUser')->name('tip-user');
        });

        Route::group(['prefix' => 'analytics'], function () {
            Route::group(['prefix' => 'sales'], function () {
                Route::get('daily', 'AnalyticsController@listDailySales')->name('list-daily-sales');
            });
        });

        Route::group(['prefix' => 'account'], function () {
            Route::get('/', 'UserController@showAccount')->name('show-account');
            Route::delete('/', 'UserController@deleteAccount')->name('delete-account');
            Route::get('dashboard', 'UserController@showDashboardDetails')->name('show-dashboard-details');
            Route::get('digiverses', 'CollectionController@listUserCreatedDigiverses')->name('list-user-created-digiverses');
            Route::get('notifications', 'UserController@getNotifications');
            Route::patch('notifications', 'UserController@markAllNotificationsAsRead');
            // Route::get('approval-requests', 'ApprovalController@getUserRequests');
            Route::get('subscriptions', 'SubscriptionController@getUserSubscriptions');
            Route::patch('fund-wallet', 'WalletController@fundWallet')->name('fund-wallet');
            Route::patch('withdraw-from-wallet', 'WalletController@withdrawFromWallet')->name('withdraw-from-wallet');
            Route::post('wallet-pay', 'WalletController@payViaWallet');
            Route::get('wallet-transactions', 'WalletController@getTransactions');
            Route::post('profile', 'UserController@updateBasicData');
            Route::put('password', 'UserController@updatePassword');
            Route::patch('token', 'Auth\AuthController@refreshToken');
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
            Route::get('revenues', 'UserController@listRevenues')->name('list-revenues');
            Route::post('referrer', 'UserController@addReferrer');
        });

        Route::group(['prefix' => 'subscriptions'], function () {
            Route::patch('{id}', 'SubscriptionController@toggleAutorenew')->name('toggle-auto-renew');
        });

        Route::group(['prefix' => 'contents'], function () {
            Route::post('/', 'ContentController@create')->name('create-content');
            Route::patch('{id}', 'ContentController@update')->name('update-content');
            Route::delete('{id}/archive', 'ContentController@archive');
            Route::delete('{id}', 'ContentController@delete');

            Route::get('{id}/insights', 'ContentController@getContentInsights');

            Route::post('{id}/issues', 'ContentController@createIssue');
            Route::put('{id}/issues', 'ContentController@updateIssue');
            Route::patch('{id}/issues', 'ContentController@publishIssue');
            Route::get('{id}/issues', 'ContentController@getIssues');

            Route::post('{id}/subscription', 'ContentController@subscribeToContent')->name('subscribe-to-content');
            Route::delete('{id}/subscription', 'ContentController@unsubscribeFromContent');

            Route::post('{id}/live', 'ContentController@startLive')->name('start-live');
            Route::patch('{id}/live', 'ContentController@joinLive')->name('join-live');
            Route::patch('{id}/leave-live', 'ContentController@leaveLive')->name('leave-live');
            Route::delete('{id}/live', 'ContentController@endLive')->name('end-live');

            Route::patch('{id}/respond-to-challenge', 'ContentController@respondToChallenge')->name('respond-to-challenge');
            Route::patch('{id}/contribute-to-challenge', 'ContentController@contributeToChallenge')->name('contribute-to-challenge');
            Route::patch('{id}/vote-on-challenge', 'ContentController@voteOnChallenge')->name('vote-on-challenge');
            Route::post('{id}/attach-media', 'ContentController@attachMediaToContent');
            Route::post('{id}/poll', 'ContentPollController@createPoll')->name('create-poll');
        });
        Route::group(['prefix' => 'polls'], function () {
            Route::patch('{id}', 'ContentPollController@updatePoll')->name('update-poll');
            Route::delete('{id}', 'ContentPollController@deletePoll')->name('delete-poll');
            Route::get('{id}', 'ContentPollController@get')->name('get-poll');
            Route::post('{id}/vote', 'ContentPollController@votePoll')->name('vote-poll');
        });

        Route::group(['prefix' => 'issues'], function () {
            Route::get('{id}', 'ContentController@getSingleIssue');
        });

        Route::group(['prefix' => 'digiverses'], function () {
            Route::post('/', 'CollectionController@createDigiverse')->name('create-digiverse');
            Route::patch('{id}', 'CollectionController@updateDigiverse')->name('update-digiverse');

            Route::delete('{id}/archive', 'CollectionController@archive');
            Route::delete('{id}', 'CollectionController@delete');
        });

        Route::group(['prefix' => 'collections'], function () {
            Route::post('/', 'CollectionController@createCollection');
            Route::patch('{id}', 'CollectionController@updateCollection');
            Route::patch('{id}/contents', 'CollectionController@addOrRemoveContent');

            Route::delete('{id}/archive', 'CollectionController@archive');
            Route::delete('{id}', 'CollectionController@delete');
        });

        Route::group(['prefix' => 'payments'], function () {
            Route::post('free', 'PaymentController@freeItems');
        });
    });
});
