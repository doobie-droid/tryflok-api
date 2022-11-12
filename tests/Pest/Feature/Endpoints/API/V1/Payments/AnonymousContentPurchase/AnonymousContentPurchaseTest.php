<?php 

use App\Constants;
use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;

test('non signed up user can purchase a content', function () 
{
    $user = Models\User::factory()->create();
    $anonymousUserEmail = 'charlesagate3@gmail.com';
    $name = 'John Doe';
    $content = Models\Content::factory()->create([
        'user_id' => $user->id,
    ]);
    $price = Models\Price::factory()->create([
        'priceable_id' => $content->id,
        'priceable_type' => 'content',
        'amount' => 10,
    ]);
    $transaction_id = date('YmdHis');
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $content_price_in_naira = $price->amount * $naira_to_dollar->value;
    $amount_spent = $content_price_in_naira * 1.03;
    $fee_in_naira = bcmul($amount_spent, .015, 2);

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'success',
        'data' => [
            'status' => 'successful',
            'id' => $transaction_id,
            'app_fee' => $fee_in_naira,
            'amount' => $amount_spent,
        ],
    ]);

    $request = [
        'items' => [
            [
            'id' => $content->id,
            'type' => 'content',
            'price' => [
                'amount' => 10,
                'id' => $price->id,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
        ]
        ],
        'email' => $anonymousUserEmail,
        'name' => $name,
        'provider' => 'flutterwave',
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
    ];

    $response = $this->json('POST', '/api/v1/payments/anonymous-purchases', $request);
    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    $this->assertDatabaseHas('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => 10,
        'payment_processor_fee' => bcdiv($fee_in_naira, $naira_to_dollar->value, 2),
        'payer_email' => $anonymousUserEmail,
        'payee_id' => $user->id,
        'paymentable_type' => 'content',
        'paymentable_id' => $content->id
    ]);

    $this->assertDatabaseHas('anonymous_purchases', [
        'email' => $anonymousUserEmail,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,

    ]);

    $this->assertDatabaseHas('revenues', [
        'revenueable_type' => 'content',
        'revenueable_id' => $content->id,
        'user_id' => $user->id,
        'revenue_from' => 'sale',
    ]);
});

test('non signed up user can purchase a collection', function () 
{
    $user = Models\User::factory()->create();
    $anonymousUserEmail = 'charlesagate3@gmail.com';
    $name = 'John Doe';
    $collection = Models\Collection::factory()->create([
        'user_id' => $user->id,
    ]);
    $price = Models\Price::factory()->create([
        'priceable_id' => $collection->id,
        'priceable_type' => 'collection',
        'interval' => 'monthly',
        'amount' => 100
    ]);
    $transaction_id = date('YmdHis');
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $content_price_in_naira = $price->amount * $naira_to_dollar->value;
    $amount_spent = $content_price_in_naira * 1.03;
    $fee_in_naira = bcmul($amount_spent, .015, 2);

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'success',
        'data' => [
            'status' => 'successful',
            'id' => $transaction_id,
            'app_fee' => $fee_in_naira,
            'amount' => $amount_spent,
        ],
    ]);

    $request = [
        'items' => [
            [
            'id' => $collection->id,
            'type' => 'collection',
            'price' => [
                'amount' => 100,
                'id' => $price->id,
                'interval' => 'monthly',
                'interval_amount' => 1,
            ],
        ]
        ],
        'email' => $anonymousUserEmail,
        'name' => $name,
        'provider' => 'flutterwave',
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
    ];

    $response = $this->json('POST', '/api/v1/payments/anonymous-purchases', $request);
    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    $this->assertDatabaseHas('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => 100,
        // 'payment_processor_fee' => bcdiv($fee_in_naira, $naira_to_dollar->value, 2),
        'payer_email' => $anonymousUserEmail,
        'payee_id' => $user->id,
        'paymentable_type' => 'collection',
        'paymentable_id' => $collection->id
    ]);

    $this->assertDatabaseHas('anonymous_purchases', [
        'email' => $anonymousUserEmail,
        'anonymous_purchaseable_type' => 'collection',
        'anonymous_purchaseable_id' => $collection->id,

    ]);

    $this->assertDatabaseHas('revenues', [
        'revenueable_type' => 'collection',
        'revenueable_id' => $collection->id,
        'user_id' => $user->id,
        'revenue_from' => 'sale',
    ]);
    //subscription is created
    $this->assertDatabaseHas('subscriptions', [
        'subscriptionable_type' => 'collection',
        'subscriptionable_id' => $collection->id,
        'status' => 'active',
    ]);
});

test('non signed up users can access anonymously purchased content asset', function ()
{
    $anonymousUserEmail = 'charlesagate3@gmail.com';
    $name = 'John Doe';
    $accessToken = Str::random(20);
    $content = Models\Content::factory()->create();
    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $anonymousUserEmail,
        'name' => $name,
        'access_token' => $accessToken,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available'
    ]);

    $request = [
        'access_token' => $accessToken,
    ];

    $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets", $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());
});

test('non signed in user can access anonymously purchased content via ancestor', function ()
{
    $digiverse = Models\Collection::factory()
    ->digiverse()
    ->setPriceAmount(100)
    ->create();
    $content = Models\Content::factory()
    ->setDigiverse($digiverse)
    ->setTags([Models\Tag::factory()->create()])
    ->create();

    $anonymousUserEmail = 'charlesagate3@gmail.com';
    $name = 'John Doe';
    $accessToken = Str::random(20);
    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $anonymousUserEmail,
        'name' => $name,
        'access_token' => $accessToken,
        'anonymous_purchaseable_type' => 'collection',
        'anonymous_purchaseable_id' => $digiverse->id,
        'status' => 'available'
    ]);

    $request = [
        'access_token' => $accessToken,
    ];

    $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets", $request);
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\Content::generateGetAssetsResponse());

})->skip();

test('non signed up user cannot access purchased content with invalid access token', function ()
{
    $anonymousUserEmail = 'charlesagate3@gmail.com';
    $name = 'John Doe';
    $accessToken = Str::random(20);
    $content = Models\Content::factory()->create();
    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $anonymousUserEmail,
        'name' => $name,
        'access_token' => $accessToken,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available'
    ]);

    $request = [
        'access_token' => '1234mnxyrt243567atgf',
    ];

    $response = $this->json('GET', "/api/v1/contents/{$content->id}/assets", $request);
    $response->assertStatus(400);
});

it('does not work if price does not match', function()
{
    $user = Models\User::factory()->create();
    $anonymousUserEmail = 'charlesagate3@gmail.com';
    $name = 'John Doe';
    $content = Models\Content::factory()->create([
        'user_id' => $user->id,
    ]);
    $price = Models\Price::factory()->create([
        'priceable_id' => $content->id,
        'priceable_type' => 'content',
        'amount' => 10,
    ]);
    $transaction_id = date('YmdHis');
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $content_price_in_naira = $price->amount * $naira_to_dollar->value;
    $amount_spent = $content_price_in_naira * 1.03;
    $fee_in_naira = bcmul($amount_spent, .015, 2);

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'success',
        'data' => [
            'status' => 'successful',
            'id' => $transaction_id,
            'app_fee' => $fee_in_naira,
            'amount' => $amount_spent,
        ],
    ]);

    $request = [
        'items' => [
            [
            'id' => $content->id,
            'type' => 'content',
            'number_of_tickets' => 2,
            'price' => [
                'amount' => 10,
                'id' => $price->id,
                'interval' => 'one-off',
                'interval_amount' => 1,
            ],
        ]
        ],
        'email' => $anonymousUserEmail,
        'name' => $name,
        'provider' => 'flutterwave',
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
    ];

    $response = $this->json('POST', '/api/v1/payments/anonymous-purchases', $request);
    $response->assertStatus(400);

    $this->assertDatabaseMissing('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => 10,
        'payment_processor_fee' => bcdiv($fee_in_naira, $naira_to_dollar->value, 2),
        'payer_email' => $anonymousUserEmail,
        'payee_id' => $user->id,
        'paymentable_type' => 'content',
        'paymentable_id' => $content->id
    ]);

    $this->assertDatabaseMissing('anonymous_purchases', [
        'email' => $anonymousUserEmail,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,

    ]);

    $this->assertDatabaseMissing('revenues', [
        'revenueable_type' => 'content',
        'revenueable_id' => $content->id,
        'user_id' => $user->id,
        'revenue_from' => 'sale',
    ]);
});