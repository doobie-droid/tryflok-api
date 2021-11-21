<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;
use Tests\MockData\Content as ContentMock;

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
}
