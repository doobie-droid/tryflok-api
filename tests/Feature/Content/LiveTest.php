<?php

namespace Tests\Feature\Content;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LiveTest extends TestCase
{
    use DatabaseTransactions, WithFaker;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_start_live_works()
    {
        $user = User::factory()->create();
        $this->be($user);
        $content = Content::factory()
        ->for($user, 'owner')
        ->liveAudio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'inactive',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'channel_name',
                'uid'
            ]
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'live_status',
            'value' => 'active',
        ]);
    }

    public function test_start_live_does_not_work_for_non_live_content()
    {
        $user = User::factory()->create();
        $this->be($user);
        $content = Content::factory()
        ->for($user, 'owner')
        ->audio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'inactive',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);

        $this->assertDatabaseMissing('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'live_status',
            'value' => 'active',
        ]);
    }

    public function test_start_live_does_not_work_if_not_creator_live_content()
    {
        $user = User::factory()->create();
        $this->be($user);
        $user2 = User::factory()->create();
        $content = Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'inactive',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('POST', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);

        $this->assertDatabaseMissing('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'live_status',
            'value' => 'active',
        ]);
    }

    public function test_join_live_does_not_work_if_channel_not_started()
    {
        $user = User::factory()->create();
        $this->be($user);
        $user2 = User::factory()->create();
        $content = Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'inactive',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);
    }

    public function test_join_live_does_works()
    {
        $user = User::factory()->create();
        $this->be($user);
        $user2 = User::factory()->create();
        $content = Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'active',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
                'channel_name',
                'uid'
            ]
        ]);
    }

    public function test_end_live_does_not_work_if_not_creator_live_content()
    {
        $user = User::factory()->create();
        $this->be($user);
        $user2 = User::factory()->create();
        $content = Content::factory()
        ->for($user2, 'owner')
        ->liveAudio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'active',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('DELETE', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(400);

        $this->assertDatabaseMissing('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'live_status',
            'value' => 'inactive',
        ]);
    }

    public function test_end_live_works()
    {
        $user = User::factory()->create();
        $this->be($user);
        $content = Content::factory()
        ->for($user, 'owner')
        ->liveAudio()
        ->create();
        $content->metas()->createMany([
            [
                'key' => 'live_status',
                'value' => 'active',
            ],
            [
                'key' => 'channel_name',
                'value' => "{$content->id}-" . date('Ymd'),
            ],
        ]);

        $response = $this->json('DELETE', "/api/v1/contents/{$content->id}/live");
        $response->assertStatus(200);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'live_status',
            'value' => 'inactive',
        ]);
    }
}
