<?php 

use App\Constants;
use App\Models;

beforeEach(function()
{
        $this->user = Models\User::factory()->create();
        $this->be($this->user);

        Models\PaymentAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->wallet = Models\Wallet::factory()->create([
            'walletable_type' =>  'user',
            'walletable_id' => $this->user->id,
        ]);
});

        /**
 * when no amount is  passed
*/
it('fails when no amount is passed', function()
{
        $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', []);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'Invalid or missing input fields',
            'errors' => [
                'amount_in_flk' => [],
            ],
        ]);
});
     

        /**
 * when no amount is less than minimum
*/
it('fails when amount is less than minimum', function()
{
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

it('fails when payment account does not exist', function(){
        
        $user = Models\User::factory()->create();
        Models\Wallet::factory()
        ->for($user, 'walletable')
        ->create();
        $this->be($user);

        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $user->id,
        ]);
        $collection = Models\Collection::factory()
        ->collection()
        ->create([
            'user_id' => $user->id,
        ]);
        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);
        $content = Models\Content::factory()
        ->setCollection($collection)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $user->id,
        ]);  
        $response = $this->json('PATCH', '/api/v1/account/withdraw-from-wallet', [
            'amount_in_flk' => '10000',
        ]);

        $response->assertStatus(400)->assertJson([
            'status' => false,
            'message' => 'You need to add a payment account before you can withdraw from your wallet',
        ]);
});

it('fails when amount exceeds wallet balance', function(){
   
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
        $collection = Models\Collection::factory()
        ->collection()
        ->create([
            'user_id' => $this->user->id,
        ]);
        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);
        $content = Models\Content::factory()
        ->setCollection($collection)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
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
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
        $collection = Models\Collection::factory()
        ->collection()
        ->create([
            'user_id' => $this->user->id,
        ]);
        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);
        $content = Models\Content::factory()
        ->setCollection($collection)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        $amount_to_withdraw = 100000;
        $wallet_intial_balance = $this->wallet->balance;
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
            'id' => $this->wallet->id,
            'balance' => $expected_wallet_balance_after_withdrawal,
        ]);
        // assert that transaction was created
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'amount' => $amount_to_withdraw,
            'transaction_type' => 'deduct',
            'balance' => $expected_wallet_balance_after_withdrawal,
        ]);
        // assert that payout was created with deduction taken
        $amount_to_withdraw_in_dollars = bcdiv($amount_to_withdraw, 100, 6);
        $this->assertDatabaseHas('payouts', [
            'amount' => bcmul($amount_to_withdraw_in_dollars, 1 - Constants\Constants::WALLET_WITHDRAWAL_CHARGE),
            'claimed' => 0,
        ]);
});

test('user with at least one published content in a collection under a published can withdraw', function()
{
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
        $collection = Models\Collection::factory()
        ->collection()
        ->create([
            'user_id' => $this->user->id,
        ]);
        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);
        $content = Models\Content::factory()
        ->setCollection($collection)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        $amount_in_dollars = bcdiv($request['amount_in_flk'], 100, 6);
        $amount_to_withdraw_in_dollars = bcmul($amount_in_dollars, 1 - 0.05);
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(202);
        $this->assertDatabaseHas('payouts', [
            'user_id' => $this->user->id,
            'generated_from' => 'wallet',
            'amount' => $amount_to_withdraw_in_dollars,
        ]);
});

test('user with at least one published content in a digiverse can withdraw', function()
{

        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
       
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        $amount_in_dollars = bcdiv($request['amount_in_flk'], 100, 6);
        $amount_to_withdraw_in_dollars = bcmul($amount_in_dollars, 1 - 0.05);
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(202);
        $this->assertDatabaseHas('payouts', [
            'user_id' => $this->user->id,
            'generated_from' => 'wallet',
            'amount' => $amount_to_withdraw_in_dollars,
        ]);
});

test('user with a payout within the last 24 hours cannot withdraw', function()
{
        $payout = Models\Payout::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(25),
            'generated_from' => 'wallet'
        ]);
        $payout = Models\Payout::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(20),
            'generated_from' => 'wallet'
        ]);
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
       
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(400);
});

test('user with a payout more than 24 hours ago can withdraw', function()
{
        $payout = Models\Payout::factory()->create([
            'user_id' => $this->user->id,
            'generated_from' => 'wallet',
            'created_at' => now()->subHours(25)
        ]);
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
       
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        $amount_in_dollars = bcdiv($request['amount_in_flk'], 100, 6);
        $amount_to_withdraw_in_dollars = bcmul($amount_in_dollars, 1 - 0.05);
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(202);
        $this->assertDatabaseHas('payouts', [
            'user_id' => $this->user->id,
            'generated_from' => 'wallet',
            'amount' => $amount_to_withdraw_in_dollars,
        ]);
});

test('user with at least a content under an unpublished digiverse cannot withdraw', function()
{
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
            'is_available' => 0,
            'approved_by_admin' => 0,
        ]);
    
        $content = Models\Content::factory()
        ->setDigiverse($digiverse)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(400);
});

test('user with at least a content in a collection in an unpublished digiverse cannot withdraw', function()
{
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
            'is_available' => 0,
            'approved_by_admin' => 0,
        ]);
        $collection = Models\Collection::factory()
        ->collection()
        ->create([
            'user_id' => $this->user->id,
        ]);
        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);
        $content = Models\Content::factory()
        ->setCollection($collection)
        ->setTags([Models\Tag::factory()->create()])
        ->create([
            'user_id' => $this->user->id,
        ]);  
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        dd($response);
        $response->assertStatus(400);
})->skip();

test('user with no content in a collection or a digiverse cannot withdraw', function()
{
        $digiverse = Models\Collection::factory()->digiverse()->create([
            'user_id' => $this->user->id,
        ]);
        $collection = Models\Collection::factory()
        ->collection()
        ->create([
            'user_id' => $this->user->id,
        ]);
        $digiverse->childCollections()->attach($collection->id, [
            'id' => Str::uuid(),
        ]);
        
        $request = [
            'amount_in_flk' => 100,
        ];        
        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(400);
});