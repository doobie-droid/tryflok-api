<?php 

use App\Constants;
use App\Mail\User\TippedMail;
use App\Models;
use Illuminate\Support\Facades\Mail;

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
    $amount = 10;
    $amount_in_dollars = bcdiv($amount, 100, 6);

    $userTip = Models\UserTip::create([
        'tipper_user_id' => $user1->id,
        'tipper_email' => $user1->email,
        'tippee_user_id' => $user2->id,
        'provider' => 'wallet',
        'tip_frequency' => 'daily',
        'last_tip' => now()->subDay(),
        'amount_in_flk' => $amount,
    ]);
    
    $this->artisan('flok:tip-users-recurrently')->assertSuccessful();
   
    // Mail::assertSent(TippedMail::class);

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
});