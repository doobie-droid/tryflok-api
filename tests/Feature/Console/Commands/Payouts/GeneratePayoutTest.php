<?php

namespace Tests\Feature\Console\Commands\Payouts;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class GeneratePayoutTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_generate_payout_works()
    {
        $user1 = Models\User::factory()->create();
        $user1_number_of_sales = 13;
        $user1_unit_price = $this->faker->randomFloat(0, 5, 100);
        $user1_total = bcmul($user1_unit_price, $user1_number_of_sales, 2);
        $user1_final_share = bcmul($user1_total, 100 - Constants\Constants::NORMAL_CREATOR_CHARGE, 6);
        
        Models\Revenue::factory()
        ->for($user1)
        ->customAmount($user1_unit_price)
        ->count($user1_number_of_sales)
        ->create();

        $user2 = Models\User::factory()->create();
        $user2_number_of_sales = 6;
        $user2_unit_price = $this->faker->randomFloat(0, 5, 100);
        $user2_total = ($user2_unit_price * $user2_number_of_sales);
        $user2_final_share = bcmul($user2_total, 100 - Constants\Constants::NORMAL_CREATOR_CHARGE, 6);
        Models\Revenue::factory()
        ->for($user2)
        ->customAmount($user2_unit_price)
        ->count($user2_number_of_sales)
        ->create();
        Models\Revenue::factory()
        ->for($user2)
        ->addedToPayout()
        ->customAmount($user2_unit_price)
        ->count($user2_number_of_sales)
        ->create();

        $user3 = Models\User::factory()->create();
        $user3_number_of_sales = 8;
        $user3_unit_price = $this->faker->randomFloat(0, 5, 100);
        $user3_total = 0;
        $user3_final_share = 0;
        Models\Revenue::factory()
        ->for($user3)
        ->addedToPayout()
        ->customAmount($user3_unit_price)
        ->count($user3_number_of_sales)
        ->create();

        $user5 = Models\User::factory()->create();
        $user5_number_of_sales = 8;
        $user5_unit_price = 0;
        $user5_total = 0;
        $user5_final_share = 0;
        Models\Revenue::factory()
        ->for($user5)
        ->customAmount($user5_unit_price)
        ->count($user5_number_of_sales)
        ->create();

        $user4 = Models\User::factory()->create();
        $user4_total = 0;
        $user4_final_share = 0;

        $this->artisan('flok:generate-payouts')->assertSuccessful();

        $this->assertDatabaseHas('revenues', [
            'user_id' => $user1->id,
            'added_to_payout' => 1,
        ]);
        $this->assertDatabaseMissing('revenues', [
            'user_id' => $user1->id,
            'added_to_payout' => 0,
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $user2->id,
            'added_to_payout' => 1,
        ]);
        $this->assertDatabaseMissing('revenues', [
            'user_id' => $user2->id,
            'added_to_payout' => 0,
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $user3->id,
            'added_to_payout' => 1,
        ]);
        $this->assertDatabaseMissing('revenues', [
            'user_id' => $user3->id,
            'added_to_payout' => 0,
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $user5->id,
            'added_to_payout' => 1,
        ]);
        $this->assertDatabaseMissing('revenues', [
            'user_id' => $user5->id,
            'added_to_payout' => 0,
        ]);

        $this->assertDatabaseMissing('revenues', [
            'user_id' => $user4->id,
        ]);

        $this->assertDatabaseHas('payouts', [
            'user_id' => $user1->id,
            'claimed' => 0,
            'amount' => $user1_final_share,
        ]);

        $this->assertDatabaseHas('payouts', [
            'user_id' => $user2->id,
            'claimed' => 0,
            'amount' => $user2_final_share,
        ]);

        $this->assertDatabaseMissing('payouts', [
            'user_id' => $user3->id,
        ]);

        $this->assertDatabaseMissing('payouts', [
            'user_id' => $user4->id,
        ]);

        $this->assertDatabaseMissing('payouts', [
            'user_id' => $user5->id,
        ]);
    }
}
