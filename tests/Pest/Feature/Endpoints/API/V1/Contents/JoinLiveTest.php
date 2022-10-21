<?php 

use App\Models;
use App\Services\LiveStream\Agora\RtcTokenBuilder as AgoraRtcToken;
use App\Services\LiveStream\Agora\RtmTokenBuilder as AgoraRtmToken;

it('fails if channel not started', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);
});

test('join live works', function()
{
        $user = Models\User::factory()->create();
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $this->be($user2);
        $this->json('POST', "/api/v1/contents/{$content->id}/live");

        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'rtc_token',
                'rtm_token',
                'channel_name',
                'uid',
                'subscribers_count',
            ],
        ]);
});

test('join live works for anonymous user', function ()
{
        $anonymousUserEmail = 'charlesagate3@gmail.com';
        $accessToken = Str::random(20);
        $user2 = Models\User::factory()->create();
        $content = Models\Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $anonymousPurchase = Models\AnonymousPurchase::create([
            'email' => $anonymousUserEmail,
            'access_token' => $accessToken,
            'anonymous_purchaseable_type' => 'content',
            'anonymous_purchaseable_id' => $content->id,
            'status' => 'available'
        ]);
        $channel = $content->metas()->create([
            'key' => 'channel_name',
            'value' => "{$content->id}-" . date('Ymd'),
        ]);
        $expires = time() + (24 * 60 * 60); // let token last for 24hrs
        $agora_rtc_token = AgoraRtcToken::buildTokenWithUid(config('services.agora.id'), config('services.agora.certificate'), $channel->value, 0, AgoraRtcToken::ROLE_PUBLISHER, $expires);
        $agora_rtm_token = AgoraRtmToken::buildToken(config('services.agora.id'), config('services.agora.certificate'), $channel->value, 0, AgoraRtmToken::ROLE_RTM_USER, $expires);

        
        $content->metas()->createMany([
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
            [
                'key' => 'rtm_token',
                'value' => $agora_rtm_token,
            ],
            [
                'key' => 'join_count',
                'value' => 1,
            ],
        ]);
        $content->metas()->create([
                'key' => 'rtc_token',
                'value' => $agora_rtc_token,
        ]);

        $content->live_status = 'active';
        $content->scheduled_date = now();
        $content->save();
        
        $request = [
            'access_token' => $accessToken,
        ];

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live", $request);
        // dd($response->getData());
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'rtc_token',
                'rtm_token',
                'channel_name',
                'uid',
                'subscribers_count',
            ],
        ]);
        $this->assertDatabaseHas('content_subscriber', [
            'content_id' => $content->id,
            'access_token' => $request['access_token']
        ]);
})->skip();