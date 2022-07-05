<?php 

use App\Mail\User\NoPaymentAccountMail;
use App\Models;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;

uses(WithFaker::class);

test('unwanted payouts do not get processed', function()
{
    $completed_payout = Models\Payout::factory()
                                ->state([
                                    'claimed' => 1,
                                ])
                                ->create();

        $admin_cancelled_payout = Models\Payout::factory()
                                ->state([
                                    'cancelled_by_admin' => 1,
                                ])
                                ->create();

        $newly_attempted_payout = Models\Payout::factory()
                                ->state([
                                    'last_payment_request' => now(),
                                ])
                                ->create();
        
        $payout_date_still_in_future = Models\Payout::factory()
                                        ->state([
                                            'payout_date' => now()->addMonth(),
                                        ])
                                        ->create();
        $this->artisan('flok:cashout-payouts')->assertSuccessful();

        $this->assertEquals(0, $completed_payout->refresh()->cashout_attempts);
        $this->assertEquals(0, $admin_cancelled_payout->refresh()->cashout_attempts);
        $this->assertEquals(0, $newly_attempted_payout->refresh()->cashout_attempts);
        $this->assertEquals(0, $payout_date_still_in_future->refresh()->cashout_attempts);
});

test('no payment account notification works correctly', function()
{
    $user = Models\User::factory()->create();
        $payout = Models\Payout::factory()
                    ->for($user)
                    ->create();
        Mail::fake();
        $this->artisan('flok:cashout-payouts')->assertSuccessful();
        Mail::assertSent(NoPaymentAccountMail::class);
        $this->assertEquals(1, $payout->refresh()->cashout_attempts);
        $this->assertFalse($payout->refresh()->failed_notification_sent == null);
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $user->id,
            'notifier_id' => $user->id,
            'notificable_type' => 'payout',
            'message' => "We tried paying out USD {$payout->amount} to you but were unable to because you have no payment account. Please add a payment account and you would recieve your payout whithin the next 24hrs",
        ]);

        $last_failed_notification_sent = $payout->refresh()->failed_notification_sent;
        $payout->last_payment_request = now()->subHours(4);
        $payout->save();
        Mail::fake();
        $this->artisan('flok:cashout-payouts')->assertSuccessful();
        Mail::assertNotSent(NoPaymentAccountMail::class);
        $this->assertEquals(2, $payout->refresh()->cashout_attempts);
        $this->assertEquals($last_failed_notification_sent->format('Y-m-d H:i:s'), $payout->refresh()->failed_notification_sent->format('Y-m-d H:i:s'));

        $payout->last_payment_request = now()->subHours(4);
        $payout->failed_notification_sent = now()->subHours(13);
        $payout->save();
        Mail::fake();
        $this->artisan('flok:cashout-payouts')->assertSuccessful();
        Mail::assertSent(NoPaymentAccountMail::class);
        $this->assertEquals(3, $payout->refresh()->cashout_attempts);

});