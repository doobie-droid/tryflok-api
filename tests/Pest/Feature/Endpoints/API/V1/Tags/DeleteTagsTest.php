<?php

use App\Models;
use Tests\MockData;
use App\Constants\Roles;

test('delete tags works', function()
{
        $user = Models\User::factory()->create();
        $user->assignRole(Roles::ADMIN);
        $this->be($user);
        $tag = Models\Tag::create([
            'name' => 'xena',
            'tag_priority' => 1,
        ]);
        $response = $this->json('DELETE', "/api/v1/tags/{$tag->id}");
        $response->assertStatus(200);
});

test('delete tags does not work if user is not admin', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);
        $tag = Models\Tag::factory()->create();
        $response = $this->json('DELETE', "/api/v1/tags/{$tag->id}");
        $response->assertStatus(400);
        $this->assertDatabaseHas('tags', [
            'name' => $tag->name,
        ]);
});