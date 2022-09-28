<?php

test('list tags works', function()
{
    $response = $this->json('GET', '/api/v1/tags');
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'tags' => [
                    [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);
})->skip();