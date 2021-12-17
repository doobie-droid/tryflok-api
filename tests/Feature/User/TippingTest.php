<?php

namespace Tests\Feature\User;

use App\Constants\Constants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Userable;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TippingTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_tipping_fails_for_invalid_data()
    {
        $user1 = User::factory()->create();
        Wallet::factory()
        ->for($user1, 'walletable')
        ->create();
        $user2 = User::factory()->create();

        $this->be($user1);

        // when user is invalid
        $response = $this->json('POST', '/api/v1/users/sdsdsd/tip', [
            'amount_in_flk' => '800000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'Invalid or missing input fields',
            'errors' => [
                'user_id' => [
                    'The selected user id is invalid.',
                ],
            ],
        ]);

        // when wallet balance is less than amount
        $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", [
            'amount_in_flk' => '800000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'You do not have enough Flok cowries to send 800000 FLK',
            'errors' => null,
        ]);
    }

    public function test_tipping_works()
    {
        $user1 = User::factory()->create();
        $wallet = Wallet::factory()
        ->for($user1, 'walletable')
        ->create();
        $wallet_initial_balance = $wallet->balance;
        $user2 = User::factory()->create();
        Wallet::factory()
        ->for($user2, 'walletable')
        ->create();
        

        $this->be($user1);
        $amount = 1000;
        $amount_in_dollars = bcdiv($amount, 100, 6);
        $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", [
            'amount_in_flk' => "{$amount}",
        ]);
        $response->assertStatus(200);

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
            'details' => 'Deduct from wallet to tip a user',
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

        $platform_share = bcmul($amount_in_dollars, Constants::NORMAL_CREATOR_CHARGE, 6);
        $platform_charge = Constants::NORMAL_CREATOR_CHARGE;
        $creator_share = bcmul($amount_in_dollars, 100 - $platform_charge, 6);
        $this->assertDatabaseHas('revenues', [
            'revenueable_type' => 'user',
            'revenueable_id' => $user2->id,
            'amount' => $amount_in_dollars,
            'payment_processor_fee' => 0,
            'platform_share' => $platform_share,
            'benefactor_share' => $creator_share,
            'referral_bonus' => 0,
            'revenue_from' => 'tip',
        ]);
    }
}
