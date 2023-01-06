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
    $id = 'cus_'.date('YmdHis');
    $expected_flok = 100;
    $naira_to_dollar = Models\Configuration::where('name', 'naira_to_dollar')->where('type', 'exchange_rate')->first();
    $amount_spent = $naira_to_dollar->value * 1.03;
    $fee_in_naira = bcmul($amount_spent, .015, 2);
    $card_token = date('YmdHis');

    stub_request("https://api.stripe.com/v1/customers", [
        'id' => $id,
        'email' => $user2->email,
    ]);

    $response = $this->json('POST', "/api/v1/payments/anonymous-user-tip", [
        'provider' => 'stripe',
        'provider_response' => [
            'id' => 'tok_visa',
        ],
        'id' => $user->id,
        'expected_flk_amount' => $expected_flok,
        'amount_in_cents' => 103,
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
        // 'customer_id' => $id,
        'provider' => 'stripe',
        'amount_in_flk' => $expected_flok,
        'tip_frequency' => 'daily',
        'status' => 'active',
        'originating_content_id' => $content->id,
    ]);
});