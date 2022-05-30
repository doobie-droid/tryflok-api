<?php

namespace Tests\Feature\Console\Commands\Users;

use App\Models;
use Tests\TestCase;

class GenerateWalletTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_wallets_are_generated()
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
    }
}
