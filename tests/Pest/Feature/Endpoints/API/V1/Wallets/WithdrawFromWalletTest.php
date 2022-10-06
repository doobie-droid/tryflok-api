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
        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(202);
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
        
        $response = $this->json('PATCH', "/api/v1/account/withdraw-from-wallet", $request);
        $response->assertStatus(202);
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