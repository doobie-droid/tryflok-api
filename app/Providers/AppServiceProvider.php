<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'approval' => 'App\Models\Approval',
            'approval_message' => 'App\Models\ApprovalMessage',
            'asset' => 'App\Models\Asset',
            'asset_resolution' => 'App\Models\AssetResolution',
            'benefactor' => 'App\Models\Benefactor',
            'cart' => 'App\Models\Cart',
            'category' => 'App\Models\Category',
            'collection' => 'App\Models\Collection',
            'configuration' => 'App\Models\Configuration',
            'content' => 'App\Models\Content',
            'content_issue' => 'App\Models\ContentIssue',
            'continent' => 'App\Models\Continent',
            'country' => 'App\Models\Country',
            'language' => 'App\Models\Language',
            'notification' => 'App\Models\Notification',
            'notification_token' => 'App\Models\NotificationToken',
            'otp' => 'App\Models\Otp',
            'payment' => 'App\Models\Payment',
            'payment_account' => 'App\Models\PaymentAccount',
            'payout' => 'App\Models\Payout',
            'price' => 'App\Models\Price',
            'review' => 'App\Models\Review',
            'sale' => 'App\Models\Sale',
            'subscription' => 'App\Models\Subscription',
            'tag' => 'App\Models\Tag',
            'user' => 'App\Models\User',
            'userable' => 'App\Models\Userable',
            'view' => 'App\Models\View',
            'wallet' => 'App\Models\Wallet',
            'wallet_transaction' => 'App\Models\WalletTransaction',
        ]);
    }
}
