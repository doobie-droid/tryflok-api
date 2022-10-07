<?php

use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;

beforeEach(function(){
        $this->user = Models\User::factory()->create();
        $this->be($this->user);

        Models\PaymentAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Models\Wallet::factory()->create([
            'walletable_type' =>  'user',
            'walletable_id' => $this->user->id,
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