<?php

use App\Models;
use Tests\MockData;
use App\Constants\Roles;

test('create tags works', function()
{
    $user = Models\User::factory()->create();
    $user->assignRole(Roles::ADMIN);
    $this->be($user);
    $request = [
        'tags' => [
            [
                'name' => 'Lifestyle',
            ],
            [
                'name' => 'Money',
            ],
            [
                'name' => 'Yolo',
            ],
        ]
        ];
        $response = $this->json('POST', '/api/v1/tags', $request);
        $response->assertStatus(200);
});