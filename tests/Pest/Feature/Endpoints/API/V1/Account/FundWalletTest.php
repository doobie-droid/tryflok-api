<?php

use App\Constants;
use App\Models;
use Tests\MockData;

test('fund wallet fails via flutterwave when charged amount is not up to expected amount for flok cowries', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $amount_spent = $naira_to_dollar->value * 1.03;
    $charged_amount = 200;
    $charged_amount_in_dollars = bcdiv($charged_amount, $naira_to_dollar->value, 2);
    $expected_flk_based_on_amount = bcdiv($charged_amount_in_dollars, 1.03, 2) * 100;

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'success',
        'data' => [
            'status' => 'successful',
            'id' => $transaction_id,
            'app_fee' => bcmul($charged_amount, .015, 2),
            'amount' => $charged_amount,
        ],
    ]);

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'flutterwave',
        'expected_flk_amount' => $expected_flok,
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
    ]);

    $response->assertStatus(400)->assertJson([
        'message' => "Flok Cowrie conversion is not correct. Expects +/-3% of {$expected_flk_based_on_amount} for \${$charged_amount_in_dollars} but got {$expected_flok}",
    ]);

    $this->assertDatabaseMissing('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => $initial_wallet_balance,
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseMissing('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'transaction_type' => 'fund',
    ]);
});

test('fund wallet fails via flutterwave when transaction id is not valid', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $amount_spent = $naira_to_dollar->value * 1.03;

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'error',
        'data' => [
            'status' => 'error',
            'id' => $transaction_id,
            'app_fee' => bcmul($amount_spent, .015, 2),
            'amount' => $amount_spent,
        ],
    ]);

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'flutterwave',
        'expected_flk_amount' => $expected_flok,
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
    ]);

    $response->assertStatus(400)->assertJson([
        'message' => 'Payment provider did not verify payment',
    ]);

    $this->assertDatabaseMissing('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => $initial_wallet_balance,
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseMissing('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'transaction_type' => 'fund',
    ]);
});

test('fund wallet works via flutterwave when parameters are valid', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $amount_spent = $naira_to_dollar->value * 1.03;
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

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'flutterwave',
        'expected_flk_amount' => $expected_flok,
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    $this->assertDatabaseHas('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => 1.03,
        'payment_processor_fee' => bcdiv($fee_in_naira, $naira_to_dollar->value, 2),
        'payer_id' => $user->id,
        'payee_id' => $user->id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 0),
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'amount' => $expected_flok,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 2),
        'transaction_type' => 'fund',
        'currency' => 'FLK',
        'details' => "Fund wallet with {$expected_flok} FLK via flutterwave",
    ]);
});

test('fund wallet fails via stripe when amount paid is less than expected', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $amount_spent = 1.03; // $1.03
    $charged_amount = .50;
    $expected_flk_based_on_amount = bcdiv($charged_amount, 1.03, 2) * 100;

    stub_request("https://api.stripe.com/v1/charges", [
        'id' => $transaction_id,
        'status' => 'succeeded',
        'paid' => true,
        'amount' => (int) ($charged_amount * 100),
    ]);

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'stripe',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'id' => $transaction_id,
        ],
    ]);

    $charged_amount = bcadd($charged_amount, 0, 2);
    $response->assertStatus(400)->assertJson([
        'message' => "Flok Cowrie conversion is not correct. Expects +/-3% of {$expected_flk_based_on_amount} for \${$charged_amount} but got {$expected_flok}",
    ]);

    $this->assertDatabaseMissing('payments', [
        'provider' => 'stripe',
        'provider_id' => $transaction_id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => bcadd($initial_wallet_balance, 0),
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseMissing('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'transaction_type' => 'fund',
    ]);
});

test('fund wallet fails via stripe when transaction is invalid', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $amount_spent = 1.03; // $1.03

    stub_request("https://api.stripe.com/v1/charges", [
        'status' => 'failed',
        'paid' => false,
    ]);

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'stripe',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'id' => $transaction_id,
        ],
    ]);

    $response->assertStatus(400)->assertJson([
        'message' => 'Payment provider did not verify payment',
    ]);

    $this->assertDatabaseMissing('payments', [
        'provider' => 'stripe',
        'provider_id' => $transaction_id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => bcadd($initial_wallet_balance, 0),
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseMissing('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'transaction_type' => 'fund',
    ]);
});

test('fund wallet works via stripe when parameters are valid', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $amount_spent = 1.03; // $1.03

    stub_request("https://api.stripe.com/v1/charges", [
        'id' => $transaction_id,
        'status' => 'succeeded',
        'paid' => true,
        'amount' => (int) ($amount_spent * 100),
    ]);

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'stripe',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'id' => $transaction_id,
        ],
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    $this->assertDatabaseHas('payments', [
        'provider' => 'stripe',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => 1.03,
        'payment_processor_fee' => bcadd(bcmul(0.029, $amount_spent, 2), 0.3, 2),
        'payer_id' => $user->id,
        'payee_id' => $user->id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 0),
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'amount' => $expected_flok,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 2),
        'transaction_type' => 'fund',
        'currency' => 'FLK',
        'details' => "Fund wallet with {$expected_flok} FLK via stripe",
    ]);
});

test('fund wallet works via apple pay when parameters are valid', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 1000;
    $amount_spent = 12; // $12
    $product_id = '1000_flc';
    $fee = bcdiv(bcmul(30, $amount_spent, 2), 100, 2);

    stub_request("https://sandbox.itunes.apple.com/verifyReceipt", [
        'status' => 0,
        'receipt' => [
            'in_app' => [
                [
                    'product_id' => $product_id,
                    'transaction_id' => $transaction_id,
                ]
            ]
        ],
    ]);

    $this->be($user);

    $response = $this->json('PATCH', '/api/v1/account/fund-wallet', [
        'provider' => 'apple',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'product_id' => $product_id,
            'receipt_data' => $transaction_id,
        ],
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    $this->assertDatabaseHas('payments', [
        'provider' => 'apple',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => $amount_spent,
        'payment_processor_fee' => $fee,
        'payer_id' => $user->id,
        'payee_id' => $user->id,
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user_wallet->id,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 0),
        'currency' => 'FLK',
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user_wallet->id,
        'amount' => $expected_flok,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 2),
        'transaction_type' => 'fund',
        'currency' => 'FLK',
        'details' => "Fund wallet with {$expected_flok} FLK via apple",
    ]);
});