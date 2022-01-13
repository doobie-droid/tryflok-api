<?php

namespace Tests\Feature\Console\Commands\Payments;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class CashoutPayoutTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_unwanted_payouts_do_not_get_processed()
    {
        $completed_payout = Models\Payout::factory()
                                ->state([
                                    'claimed' => 1,
                                ])
                                ->create();
        $initial_completed_payout_attempts = $completed_payout->cashout_attempts;

        $admin_cancelled_payout = Models\Payout::factory()
                                ->state([
                                    'cancelled_by_admin' => 1,
                                ])
                                ->create();
        $initial_admin_cancelled_payout_attempts = $admin_cancelled_payout->cashout_attempts;

        $newly_attempted_payout = Models\Payout::factory()
                                ->state([
                                    'last_payment_request' => now(),
                                ])
                                ->create();
        $initial_newly_attempted_payout_attempts = $newly_attempted_payout->cashout_attempts;
        $this->artisan('flok:cashout-payouts')->assertSuccessful();

        $this->assertEquals($initial_completed_payout_attempts, $completed_payout->refresh()->cashout_attempts);
        $this->assertEquals($initial_admin_cancelled_payout_attempts, $admin_cancelled_payout->refresh()->cashout_attempts);
        $this->assertEquals($initial_newly_attempted_payout_attempts, $newly_attempted_payout->refresh()->cashout_attempts);
    }
}
