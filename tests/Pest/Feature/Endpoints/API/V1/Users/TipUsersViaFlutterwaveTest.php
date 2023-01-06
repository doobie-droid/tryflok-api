<?php 

use App\Constants;
use App\Mail\User\TippedMail;
use App\Models;
use Illuminate\Support\Facades\Mail;

test('tipping works', function()
{
    Mail::fake();
    $user = Models\User::factory()->create();
    $wallet = Models\Wallet::factory()
    ->for($user, 'walletable')
    ->create();
    $initial_wallet_balance = $wallet->balance;
    $user2 = Models\User::factory()->create();
    
    $content = Models\Content::factory()
    ->for($user, 'owner')
    ->create();

    $initial_wallet_balance = (int) $wallet->balance;
    $transaction_id = date('YmdHis');
    $expected_flok = 100;
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $amount_spent = $naira_to_dollar->value * 1.03;
    $fee_in_naira = bcmul($amount_spent, .015, 2);
    $card_token = date('YmdHis');

    stub_request("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify", [
        'status' => 'success',
        'data' => [
            'status' => 'successful',
            'id' => $transaction_id,
            'app_fee' => $fee_in_naira,
            'amount' => $amount_spent,
                'card' => [
                    'token' => $card_token,
                ],
        ],
    ]);

    $response = $this->json('POST', "/api/v1/payments/anonymous-user-tip", [
        'provider' => 'flutterwave',
        'provider_response' => [
            'transaction_id' => $transaction_id,
        ],
        'id' => $user->id,
        'expected_flk_amount' => $expected_flok,
        'email' => $user2->email,
        'tip_frequency' => 'daily',
        'originating_client_source' => 'ios',
        'originating_currency' => 'NGN',
        'originating_content_id' => $content->id,       
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Tip sent successfully',
    ]);

    $this->assertDatabaseHas('user_tips', [
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'card_token' => $card_token,
        'provider' => 'flutterwave',
        'amount_in_flk' => $expected_flok,
        'tip_frequency' => 'daily',
        'status' => 'active',
        'originating_content_id' => $content->id,
    ]);
    
   
    // Mail::assertSent(TippedMail::class);

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'balance' => bcadd($initial_wallet_balance, $expected_flok, 0),
        'currency' => 'FLK',
    ]);

    $amount_in_dollar = bcdiv($expected_flok, 100, 2);
    $platform_share = bcmul($amount_in_dollar, Constants\Constants::TIPPING_CHARGE, 6);
    $platform_charge = Constants\Constants::TIPPING_CHARGE;
    $creator_share = bcmul($amount_in_dollar, 1 - $platform_charge, 6);
    $this->assertDatabaseHas('revenues', [
        'revenueable_type' => 'user',
        'revenueable_id' => $user->id,
        'amount' => $amount_in_dollar,
        'payment_processor_fee' => 0,
        'platform_share' => $platform_share,
        'benefactor_share' => $creator_share,
        'referral_bonus' => 0,
        'revenue_from' => 'tip',
        'added_to_payout' => 1,
        'originating_currency' => 'NGN',
        'originating_client_source' => 'ios',
        'originating_content_id' => $content->id,
    ]);

    $creator_share_in_flk = $creator_share * 100;
    $this->assertDatabaseHas('wallets', [
        'walletable_type' => 'user',
        'walletable_id' => $user->id,
        'balance' => bcadd($initial_wallet_balance, $creator_share_in_flk, 0),
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet->id,
        'amount' => $creator_share_in_flk,
        'balance' => bcadd($initial_wallet_balance, $creator_share_in_flk, 0),
        'transaction_type' => 'fund',
    ]);
});