<?php

use App\Models;
use Tests\MockData;
use App\Mail\User\SendReferralMail;
use Illuminate\Support\Facades\Mail;

test('send referral emails works', function()
{
    Mail::fake();
    $user = Models\User::factory()->create();
    $this->be($user);
    $request = [
        'emails' => [
            [
                'email' => 'charlesagate3@gmail.com',
            ],
            [
                'email' => 'agatecharles@gmail.com',
            ]
        ],
    ];
    $response = $this->json('POST', "/api/v1/account/refer", $request);
    $response->assertStatus(200);
    Mail::assertSent(SendReferralMail::class);
});