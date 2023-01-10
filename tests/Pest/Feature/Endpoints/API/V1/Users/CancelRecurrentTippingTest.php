<?php 

use App\Models;

test('cancel recurrent tipping works for non-signed in user', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $customer_id = 'cus_'.date('YmdHis');
    $last_tip = now();
    $userTip = Models\UserTip::create([
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);

    $request = [
        'email' => $user2->email,
    ];

    $response = $this->json('PATCH', "/api/v1/users/{$user->id}/tip", $request);
    $response->assertStatus(200);

    $this->assertDatabaseHas('user_tips', [
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 0,
    ]);
});

test('cancel recurrent tipping works for signed in user', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $this->be($user2);
    $customer_id = 'cus_'.date('YmdHis');
    $last_tip = now();
    $userTip = Models\UserTip::create([
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);

    $response = $this->json('PATCH', "/api/v1/users/{$user->id}/tip");
    $response->assertStatus(200);

    $this->assertDatabaseHas('user_tips', [
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 0,
    ]);
});

test('cancel recurrent tipping does not work for non-signed in user if email does not match', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $user3 = Models\User::factory()->create();
    $customer_id = 'cus_'.date('YmdHis');
    $last_tip = now();
    $email = $user3->email;
    $userTip = Models\UserTip::create([
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);

    $request = [
        'email' => $user3->email,
    ];

    $response = $this->json('PATCH', "/api/v1/users/{$user->id}/tip", $request);
    $response->assertStatus(400);

    $this->assertDatabaseHas('user_tips', [
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);
});

test('cancel recurrent tipping does not work for signed in user if user id does not match', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $user3 = Models\User::factory()->create();
    $this->be($user3);
    $customer_id = 'cus_'.date('YmdHis');
    $last_tip = now();
    $email = $user3->email;
    $userTip = Models\UserTip::create([
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);

    $response = $this->json('PATCH', "/api/v1/users/{$user->id}/tip");
    $response->assertStatus(400);

    $this->assertDatabaseHas('user_tips', [
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);
});

it('does not work if user is not signed in and does not provide an email', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $user3 = Models\User::factory()->create();
    $customer_id = 'cus_'.date('YmdHis');
    $last_tip = now();
    $userTip = Models\UserTip::create([
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);

    $response = $this->json('PATCH', "/api/v1/users/{$user->id}/tip");
    $response->assertStatus(400);

    $this->assertDatabaseHas('user_tips', [
        'tipper_user_id' => $user2->id,
        'tipper_email' => $user2->email,
        'tippee_user_id' => $user->id,
        'customer_id' => $customer_id,
        'provider' => 'stripe',
        'amount_in_flk' => 100,
        'tip_frequency' => 'daily',
        'last_tip' => $last_tip,
        'is_active' => 1,
    ]);
});