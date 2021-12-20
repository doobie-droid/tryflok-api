<?php

namespace Tests\Feature\User;

use App\Constants\Constants;
use App\Mail\User\TippedMail;
use App\Models\User;
use App\Models\Userable;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

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
                'id' => [
                    'The selected id is invalid.',
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
        Mail::fake();
        $user1 = User::factory()->create();
        $wallet = Wallet::factory()
        ->for($user1, 'walletable')
        ->create();
        $wallet_initial_balance = $wallet->balance;
        $user2 = User::factory()->create();
        $wallet2 = Wallet::factory()
        ->for($user2, 'walletable')
        ->create();
        $wallet2_initial_balance = $wallet2->balance;
        

        $this->be($user1);
        $amount = 1000;
        $amount_in_dollars = bcdiv($amount, 100, 6);
        $response = $this->json('POST', "/api/v1/users/{$user2->id}/tip", [
            'amount_in_flk' => "{$amount}",
        ]);
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

        $platform_share = bcmul($amount_in_dollars, Constants::TIPPING_CHARGE, 6);
        $platform_charge = Constants::TIPPING_CHARGE;
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
    }
}
