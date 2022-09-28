<?php 

use App\Models;

test('wallets are generated', function()
{
    $user1 = Models\User::factory()->create();
    $user1->wallet()->create([]);
    $user2 = Models\User::factory()->create();

    $this->assertTrue(is_null($user2->wallet()->first()));
    $this->assertTrue($user1->wallet()->count() == 1);
    $this->artisan('flok:generate-user-wallet')->assertSuccessful();
    $this->assertTrue($user1->wallet()->count() == 1);
    $this->assertTrue($user2->wallet()->count() == 1);
    $this->assertTrue($user2->wallet->balance == 0);

});