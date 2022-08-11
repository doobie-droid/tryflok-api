<?php

use App\Constants;
use App\Models;
use App\Mail\User\TippedMail;
use Illuminate\Support\Facades\Mail;
use Tests\MockData;

defined('FUNDER_NAME') or define('FUNDER_NAME', 'John Doe');
defined('FUND_NOTE') or define('FUND_NOTE', 'We support you');


test('tipping fan fails via flutterwave when charged amount is not up to expected amount for flok cowries', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $amount_spent = Constants\Constants::NAIRA_TO_DOLLAR * 1.03;
    $charged_amount = 200;
    $charged_amount_in_dollars = bcdiv($charged_amount, Constants\Constants::NAIRA_TO_DOLLAR, 2);
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

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'flutterwave',
        'expected_flk_amount' => $expected_flok,
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
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

test('tipping fan fails via flutterwave when transaction id is not valid', function () {
    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $amount_spent = Constants\Constants::NAIRA_TO_DOLLAR * 1.03;

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'error',
        'data' => [
            'status' => 'error',
            'id' => $transaction_id,
            'app_fee' => bcmul($amount_spent, .015, 2),
            'amount' => $amount_spent,
        ],
    ]);

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'flutterwave',
        'expected_flk_amount' => $expected_flok,
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
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

test('tipping fan works via flutterwave when parameters are valid', function () {
    Mail::fake();

    $user = Models\User::factory()->create();
    $user_wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();

    $initial_wallet_balance = (int) $user_wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $amount_spent = Constants\Constants::NAIRA_TO_DOLLAR * 1.03;
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

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'flutterwave',
        'expected_flk_amount' => $expected_flok,
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    Mail::assertSent(TippedMail::class);

    $this->assertDatabaseHas('payments', [
        'provider' => 'flutterwave',
        'provider_id' => $transaction_id,
        'currency' => 'USD',
        'amount' => 1.03,
        'payment_processor_fee' => bcdiv($fee_in_naira, Constants\Constants::NAIRA_TO_DOLLAR, 2),
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

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $user->id,
        'notifier_id' => $user->id,
        'notificable_type' => 'wallet_transaction',
        'message' => sprintf("You just got a gift of %d Flok Cowries from %s with the note '%s'", $expected_flok, FUNDER_NAME, FUND_NOTE),
    ]);
});

test('tipping fan fails via stripe when amount paid is less than expected', function () {
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

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'stripe',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'id' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
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

test('tipping fan fails via stripe when transaction is invalid', function () {
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

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'stripe',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'id' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
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

test('tipping fan works via stripe when parameters are valid', function () {
    Mail::fake();

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

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'stripe',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'id' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
    ]);
    dd($response->getData());

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    Mail::assertSent(TippedMail::class);

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

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $user->id,
        'notifier_id' => $user->id,
        'notificable_type' => 'wallet_transaction',
        'message' => sprintf("You just got a gift of %d Flok Cowries from %s with the note '%s'", $expected_flok, FUNDER_NAME, FUND_NOTE),
    ]);
})->only();

test('tipping fan works via apple pay when parameters are valid', function () {
    Mail::fake();

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

    $response = $this->json('PATCH', '/api/v1/payments/easy-fund-wallet', [
        'provider' => 'apple',
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => (int) ($amount_spent * 100),
        'provider_response' => [
            'product_id' => $product_id,
            'receipt_data' => $transaction_id,
        ],
        'username' => $user->username,
        'fund_type' => 'tip',
        'funder_name' => FUNDER_NAME,
        'fund_note' => FUND_NOTE,
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    Mail::assertSent(TippedMail::class);

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

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $user->id,
        'notifier_id' => $user->id,
        'notificable_type' => 'wallet_transaction',
        'message' => sprintf("You just got a gift of %d Flok Cowries from %s with the note '%s'", $expected_flok, FUNDER_NAME, FUND_NOTE),
    ]);
});