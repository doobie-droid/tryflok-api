<?php 

use App\Constants;
use App\Mail\User\TippedMail;
use App\Models;
use Illuminate\Support\Facades\Mail;

test('tipping fails for invalid data', function()
{
    $user1 = Models\User::factory()->create();
        Models\Wallet::factory()
        ->for($user1, 'walletable')
        ->create();
        $user2 = Models\User::factory()->create();

        $this->be($user1);

        /**
 * when user is invalid
*/
        $response = $this->json('POST', '/api/v1/users/sdsdsd/tip', [
            'amount_in_flk' => '800000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'Invalid or missing input fields',
            'errors' => [
                'id' => [
                    'The selected id is invalid.',
                ],
            ],
        ]);

        /**
 * when wallet balance is less than amount
*/
        $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", [
            'amount_in_flk' => '800000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'You do not have enough Flok cowries to send 800000 FLK',
            'errors' => null,
        ]);
});

test('tipping works', function()
{
    Mail::fake();
    $user1 = Models\User::factory()->create();
    $wallet = Models\Wallet::factory()
    ->for($user1, 'walletable')
    ->create();
    $wallet_initial_balance = $wallet->balance;
    $user2 = Models\User::factory()->create();
    $wallet2 = Models\Wallet::factory()
    ->for($user2, 'walletable')
    ->create();
    $wallet2_initial_balance = $wallet2->balance;
    $content = Models\Content::factory()
    ->for($user2, 'owner')
    ->create();
    
    $this->be($user1);
    $amount = 1000;
    $amount_in_dollars = bcdiv($amount, 100, 6);

    $request = [
        'amount_in_flk' => "{$amount}",
        'originating_client_source' => 'ios',
        'originating_currency' => 'NGN',
        'originating_content_id' => $content->id,
    ];

    $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", $request);
    $response->assertStatus(200);
    Mail::assertSent(TippedMail::class);

    $this->assertDatabaseHas('wallets', [
        'walletable_type' => 'user',
        'walletable_id' => $user1->id,
        'balance' => $wallet_initial_balance - $amount,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user1->wallet->id,
        'amount' => $amount,
        'balance' => $wallet_initial_balance - $amount,
        'transaction_type' => 'deduct',
    ]);

    $this->assertDatabaseHas('payments', [
        'payer_id' => $user1->id,
        'payee_id' => $user2->id,
        'amount' => $amount_in_dollars,
        'payment_processor_fee' => 0,
        'provider' => 'wallet',
        'provider_id' => $wallet->transactions()->first()->id,
        'paymentable_type' => 'wallet_transaction',
        'paymentable_id' => $wallet->transactions()->first()->id,
    ]);

    $platform_share = bcmul($amount_in_dollars, Constants\Constants::TIPPING_CHARGE, 6);
    $platform_charge = Constants\Constants::TIPPING_CHARGE;
    $creator_share = bcmul($amount_in_dollars, 1 - $platform_charge, 6);
    $this->assertDatabaseHas('revenues', [
        'revenueable_type' => 'user',
        'revenueable_id' => $user2->id,
        'amount' => $amount_in_dollars,
        'payment_processor_fee' => 0,
        'platform_share' => $platform_share,
        'benefactor_share' => $creator_share,
        'referral_bonus' => 0,
        'revenue_from' => 'tip',
        'added_to_payout' => 1,
        'originating_currency' => $request['originating_currency'],
        'originating_client_source' => $request['originating_client_source'],
        'originating_content_id' => $request['originating_content_id'],
    ]);

    $creator_share_in_flk = $creator_share * 100;
    $this->assertDatabaseHas('wallets', [
        'walletable_type' => 'user',
        'walletable_id' => $user2->id,
        'balance' => bcadd($wallet2_initial_balance, $creator_share_in_flk, 0),
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user2->wallet->id,
        'amount' => $creator_share_in_flk,
        'balance' => bcadd($wallet2_initial_balance, $creator_share_in_flk, 0),
        'transaction_type' => 'fund',
    ]);

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $user2->id,
        'notifier_id' => $user1->id,
        'notificable_type' => 'wallet_transaction',
        'message' => "@{$user1->username} just gifted you {$creator_share_in_flk} Flok Cowries",
    ]);
});

test('tipping works without optional  parameters', function()
{
    Mail::fake();
    $user1 = Models\User::factory()->create();
    $wallet = Models\Wallet::factory()
    ->for($user1, 'walletable')
    ->create();
    $wallet_initial_balance = $wallet->balance;
    $user2 = Models\User::factory()->create();
    $wallet2 = Models\Wallet::factory()
    ->for($user2, 'walletable')
    ->create();
    $wallet2_initial_balance = $wallet2->balance;
    
    $this->be($user1);
    $amount = 1000;
    $amount_in_dollars = bcdiv($amount, 100, 6);

    $request = [
        'amount_in_flk' => "{$amount}",
    ];

    $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", $request);
    $response->assertStatus(200);
    Mail::assertSent(TippedMail::class);

    $this->assertDatabaseHas('wallets', [
        'walletable_type' => 'user',
        'walletable_id' => $user1->id,
        'balance' => $wallet_initial_balance - $amount,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user1->wallet->id,
        'amount' => $amount,
        'balance' => $wallet_initial_balance - $amount,
        'transaction_type' => 'deduct',
    ]);

    $this->assertDatabaseHas('payments', [
        'payer_id' => $user1->id,
        'payee_id' => $user2->id,
        'amount' => $amount_in_dollars,
        'payment_processor_fee' => 0,
        'provider' => 'wallet',
        'provider_id' => $wallet->transactions()->first()->id,
        'paymentable_type' => 'wallet_transaction',
        'paymentable_id' => $wallet->transactions()->first()->id,
    ]);

    $platform_share = bcmul($amount_in_dollars, Constants\Constants::TIPPING_CHARGE, 6);
    $platform_charge = Constants\Constants::TIPPING_CHARGE;
    $creator_share = bcmul($amount_in_dollars, 1 - $platform_charge, 6);
    $this->assertDatabaseHas('revenues', [
        'revenueable_type' => 'user',
        'revenueable_id' => $user2->id,
        'amount' => $amount_in_dollars,
        'payment_processor_fee' => 0,
        'platform_share' => $platform_share,
        'benefactor_share' => $creator_share,
        'referral_bonus' => 0,
        'revenue_from' => 'tip',
        'added_to_payout' => 1,
    ]);

    $creator_share_in_flk = $creator_share * 100;
    $this->assertDatabaseHas('wallets', [
        'walletable_type' => 'user',
        'walletable_id' => $user2->id,
        'balance' => bcadd($wallet2_initial_balance, $creator_share_in_flk, 0),
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $user2->wallet->id,
        'amount' => $creator_share_in_flk,
        'balance' => bcadd($wallet2_initial_balance, $creator_share_in_flk, 0),
        'transaction_type' => 'fund',
    ]);

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $user2->id,
        'notifier_id' => $user1->id,
        'notificable_type' => 'wallet_transaction',
        'message' => "@{$user1->username} just gifted you {$creator_share_in_flk} Flok Cowries",
    ]);
});

it('does not work with invalid client originating source', function()
{
        Mail::fake();
        $user1 = Models\User::factory()->create();
        $wallet = Models\Wallet::factory()
        ->for($user1, 'walletable')
        ->create();
        $wallet_initial_balance = $wallet->balance;
        $user2 = Models\User::factory()->create();
        $wallet2 = Models\Wallet::factory()
        ->for($user2, 'walletable')
        ->create();
        $wallet2_initial_balance = $wallet2->balance;
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->create();
        
        $this->be($user1);
        $amount = 1000;
        $amount_in_dollars = bcdiv($amount, 100, 6);

        $request = [
            'amount_in_flk' => "{$amount}",
            'originating_client_source' => 'java',
            'originating_currency' => 'NGN',
            'originating_content_id' => $content->id,
        ];

        $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", $request);
        $response->assertStatus(400);
});