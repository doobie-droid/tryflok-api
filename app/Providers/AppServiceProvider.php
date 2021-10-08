<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
            'user' => 'App\Models\User',
            'content' => 'App\Models\Content',
            'collection' => 'App\Models\Collection',
            'approval' => 'App\Models\Approval',
            'approval_message' => 'App\Models\ApprovalMessage',
            'asset' => 'App\Models\Asset',
            'wallet' => 'App\Models\Wallet',
            'subscription' => 'App\Models\Subscription',
            'sale' => 'App\Models\Sale',
            'review' => 'App\Models\Review',
            'price' => 'App\Models\Price',
            'payout' => 'App\Models\Payout',
            'payment' => 'App\Models\Payment',
            'otp' => 'App\Models\Otp',
            'language' => 'App\Models\Language',
            'country' => 'App\Models\Country',
            'continent' => 'App\Models\Continent',
            'configuration' => 'App\Models\Configuration',
            'category' => 'App\Models\Category',
            'cart' => 'App\Models\Cart',
            'tag' => 'App\Models\Tag',
        ]);
    }
}
