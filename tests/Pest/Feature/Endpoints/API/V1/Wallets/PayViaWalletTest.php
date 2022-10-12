<?php 

use App\Constants;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Mail\User\SaleMade;
use App\Models;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function()
{
    $this->user = Models\User::factory()->create();
    Models\Wallet::factory()->create([
        'walletable_id' => $this->user->id,
        'walletable_type' => 'user'
    ]);
    $this->be($this->user);
});
test('subscription is not created when digiverse subscribers limit is reached', function()
{
        $creator = Models\User::factory()->create();
        $buyer = Models\User::factory()->create();
        $free_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->create();

        $free_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->create();

        $paid_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->setPriceAmount(100)
        ->create();

        $paid_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->setPriceAmount(100)
        ->create([
            'max_subscribers' => 1,
        ]);
        $price = Models\Price::factory()->create();
        $paid_digiverse->subscriptions()->create([
            'id' => Str::uuid(),
            'userable_id' => Models\Userable::factory()->create()->id,
            'price_id' => $price->id,
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => Models\Collection::factory()->create()->id,
            'status' => 'active',
            'auto_renew' => 1,
            'start' => now(),
            'end' => now()->add(1, 'month'),
        ]);
        $request = [
            'items' => [
                [
                    'id' => $paid_digiverse->id,
                    'type' => 'collection',
                    'price' => [
                        'amount' => 74.58,
                        'id' => $price->id,
                        'interval' => 'monthly',
                        'interval_amount' => 1
                    ]
                ]
            ]
        ];
            
        $response = $this->json('POST', "/api/v1/account/wallet-pay", $request);
        $response->assertStatus(400);
});

test('subscription is created when digiverse subscribers limit is not reached', function()
{
        $creator = Models\User::factory()->create();
        $buyer = Models\User::factory()->create();
        $free_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->create();

        $free_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->create();

        $paid_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->setPriceAmount(100)
        ->create();

        $paid_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->setPriceAmount(100)
        ->create([
            'max_subscribers' => 5,
        ]);
        $price = Models\Price::factory()->create();
        $paid_digiverse->subscriptions()->create([
            'id' => Str::uuid(),
            'userable_id' => Models\Userable::factory()->create()->id,
            'price_id' => $price->id,
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => Models\Collection::factory()->create()->id,
            'status' => 'active',
            'auto_renew' => 1,
            'start' => now(),
            'end' => now()->add(1, 'month'),
        ]);
        $request = [
            'items' => [
                [
                    'id' => $paid_digiverse->id,
                    'type' => 'collection',
                    'price' => [
                        'amount' => 74.58,
                        'id' => $price->id,
                        'interval' => 'monthly',
                        'interval_amount' => 1
                    ]
                ]
            ]
        ];
            
        $response = $this->json('POST', "/api/v1/account/wallet-pay", $request);
        $response->assertStatus(202);
});