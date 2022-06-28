<?php 

use App\Constants;
use App\Models;

test('withdraw from wallet fails when invalid data is passed', function(){
    $user = Models\User::factory()->create();
        Models\Wallet::factory()
        ->for($user, 'walletable')
        ->create();
        $user->paymentAccounts()->create([
            'identifier' => '0123456789',
            'provider' => 'flutterwave',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
        ]);
        $this->be($user);

        /**
 * when no amount is  passed
*/
         $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', []);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'Invalid or missing input fields',
            'errors' => [
                'amount_in_flk' => [],
            ],
        ]);

        /**
 * when no amount is less than minimum
*/
        $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', [
            'amount_in_flk' => '99',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'Invalid or missing input fields',
            'errors' => [
                'amount_in_flk' => [],
            ],
        ]);
});

test('withdraw from wallet fails when payment account does not exist', function(){
        $user = Models\User::factory()->create();
        Models\Wallet::factory()
        ->for($user, 'walletable')
        ->create();
        $this->be($user);

        $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', [
            'amount_in_flk' => '10000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'You need to add a payment account before you can withdraw from your wallet',
        ]);
});

test('withdraw from wallet fails when amount exceeds wallet balance', function(){
        
    $user = Models\User::factory()->create();
        Models\Wallet::factory()
        ->for($user, 'walletable')
        ->create();
        $user->paymentAccounts()->create([
            'identifier' => '0123456789',
            'provider' => 'flutterwave',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
        ]);
        $this->be($user);

        $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', [
            'amount_in_flk' => '800000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'Your wallet balance is too low to make this withdrawal',
        ]);
});

test('withdraw from wallet works', function()
{
    $user = Models\User::factory()->create();
        $wallet = Models\Wallet::factory()
        ->for($user, 'walletable')
        ->create();
        $user->paymentAccounts()->create([
            'identifier' => '0123456789',
            'provider' => 'flutterwave',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
        ]);
        $this->be($user);
        
        $amount_to_withdraw = 100000;
        $wallet_intial_balance = $wallet->balance;
        $expected_wallet_balance_after_withdrawal = $wallet_intial_balance - $amount_to_withdraw;
        $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', [
            'amount_in_flk' => $amount_to_withdraw,
        ]);

        $response->assertStatus(202)->assertJson([
            'status' => true,
            'message' => 'Withdrawal successful. You should receive your money soon.',
        ]);

        // assert that wallet balance reduced
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => $expected_wallet_balance_after_withdrawal,
        ]);
        // assert that transaction was created
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => $amount_to_withdraw,
            'transaction_type' => 'deduct',
            'balance' => $expected_wallet_balance_after_withdrawal,
        ]);
        // assert that payout was created with deduction taken
        $amount_to_withdraw_in_dollars = bcdiv($amount_to_withdraw, 100, 6);
        $this->assertDatabaseHas('payouts', [
            'amount' => bcmul($amount_to_withdraw_in_dollars, 1 - Constants\Constants::WALLET_WITHDRAWAL_CHARGE),
            'claimed' => 0,
            'payout_date' => now()->addMonth()->startOfMonth()->addDays(6)->format('Y-m-d H:i:s'),
        ]);
});