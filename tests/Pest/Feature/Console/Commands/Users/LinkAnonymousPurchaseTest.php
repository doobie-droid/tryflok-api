<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;

test('link anonymous purchase works', function()
{
    $user1 = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $user3 = Models\User::factory()->create();
    $user4 = Models\User::factory()->create();
    $user5 = Models\User::factory()->create();
    $content = Models\Content::factory()->create();
    $access_token1 = Str::random(10) . date('Ymd');
    $access_token2 = Str::random(10) . date('Ymd');
    $access_token3 = Str::random(10) . date('Ymd');
    $access_token4 = Str::random(10) . date('Ymd');
    $access_token5 = Str::random(10) . date('Ymd');

    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $user1->email,
        'name' => 'John Doe',
        'access_token' => $access_token1,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available'
    ]);

    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $user2->email,
        'name' => 'John Doe',
        'access_token' => $access_token2,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available',
        'link_user_id' => $user2->id,
    ]);

    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $user3->email,
        'name' => 'John Doe',
        'access_token' => $access_token3,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available'
    ]);
    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $user4->email,
        'name' => 'John Doe',
        'access_token' => $access_token4,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available'
    ]);
    $anonymousPurchase = Models\AnonymousPurchase::create([
        'email' => $user5->email,
        'name' => 'John Doe',
        'access_token' => $access_token5,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available',
        'link_user_id' => $user5->id,
    ]);
    $this->artisan('flok:link-anonymous-purchases-to-users')->assertSuccessful();
    $this->assertDatabaseHas('userables', [
        'user_id' => $user1->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $content->id,
    ]);
    $this->assertDatabaseHas('anonymous_purchases', [
        'email' => $user1->email,
        'name' => 'John Doe',
        'access_token' => $access_token1,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available',
        'link_user_id' => $user1->id,
    ]);

    $this->assertDatabaseMissing('userables', [
        'user_id' => $user2->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $content->id,
    ]);

    $this->assertDatabaseHas('userables', [
        'user_id' => $user3->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $content->id,
    ]);
    $this->assertDatabaseHas('anonymous_purchases', [
        'email' => $user3->email,
        'name' => 'John Doe',
        'access_token' => $access_token3,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available',
        'link_user_id' => $user3->id,
    ]);

    $this->assertDatabaseHas('userables', [
        'user_id' => $user4->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $content->id,
    ]);
    $this->assertDatabaseHas('anonymous_purchases', [
        'email' => $user4->email,
        'name' => 'John Doe',
        'access_token' => $access_token4,
        'anonymous_purchaseable_type' => 'content',
        'anonymous_purchaseable_id' => $content->id,
        'status' => 'available',
        'link_user_id' => $user4->id,
    ]);

    $this->assertDatabaseMissing('userables', [
        'user_id' => $user5->id,
        'status' => 'available',
        'userable_type' => 'content',
        'userable_id' => $content->id,
    ]);
});