<?php

use App\Models;
use Tests\MockData;

test('create tags works', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $tag = $user->tags()->create([
            'name' => 'xena',
            'tag_priority' => 1,
        ]);
        $response = $this->json('DELETE', "/api/v1/tags/{$tag->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('tags', [
            'name' => $tag->name,
        ]);
});

test('delete tags does not work if user is not owner', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);
        $tag = Models\Tag::factory()->create();
        $response = $this->json('DELETE', "/api/v1/tags/{$tag->id}");
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'name' => $tag->name,
        ]);
});