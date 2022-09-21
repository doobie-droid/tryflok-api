<?php

use App\Constants;
use App\Models;
use App\Mail\User\TippedMail;
use Illuminate\Support\Facades\Mail;
use Tests\MockData;

defined('FUNDER_NAME') or define('FUNDER_NAME', 'John Doe');
defined('FUND_NOTE') or define('FUND_NOTE', 'We support you');

test('webhook fails for easy fund wallet if webhook secret is not valid', function () {
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
            'meta' => [
                'username' => $user->username,
                'fund_type' => 'tip',
                'funder_name' => FUNDER_NAME,
                'fund_note' => FUND_NOTE,
                'expected_flk_amount' => 100,
                'payment_for' => 'cowry_purchase',
            ],
        ],
    ]);

    $response = $this->json('POST', '/api/v1/payments/flutterwave/webhook', [
        'event' => 'charge.completed',
        'data' => [
            'id' => $transaction_id,
        ],
    ], [
        'verif-hash' => 'dd323h323h2j3',
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    Mail::assertNotSent(TippedMail::class);

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

test('webhook fails for easy fund wallet if transaction ID is not valid', function () {
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
        'status' => 'error',
        'data' => [
            'status' => 'error',
            'id' => $transaction_id,
            'app_fee' => $fee_in_naira,
            'amount' => $amount_spent,
            'meta' => [
                'username' => $user->username,
                'fund_type' => 'tip',
                'funder_name' => FUNDER_NAME,
                'fund_note' => FUND_NOTE,
                'expected_flk_amount' => 100,
                'payment_for' => 'cowry_purchase',
            ],
        ],
    ]);

    $response = $this->json('POST', '/api/v1/payments/flutterwave/webhook', [
        'event' => 'charge.completed',
        'data' => [
            'id' => $transaction_id,
        ],
    ], [
        'verif-hash' => config('payment.providers.flutterwave.webhook_secret'),
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Payment received successfully',
    ]);

    Mail::assertNotSent(TippedMail::class);

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

test('webhook from easy fund wallet works', function () {
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
            'meta' => [
                'username' => $user->username,
                'fund_type' => 'tip',
                'funder_name' => FUNDER_NAME,
                'fund_note' => FUND_NOTE,
                'expected_flk_amount' => 100,
                'payment_for' => 'cowry_purchase',
            ],
        ],
    ]);

    $response = $this->json('POST', '/api/v1/payments/flutterwave/webhook', [
        'event' => 'charge.completed',
        'data' => [
            'id' => $transaction_id,
        ],
    ], [
        'verif-hash' => config('payment.providers.flutterwave.webhook_secret'),
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